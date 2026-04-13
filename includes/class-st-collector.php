<?php
/**
 * ST手游接口采集管理器
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('YYK_ST_Collector')) {

    class YYK_ST_Collector {
        
        private static $instance = null;
        private $api_domain = '';
        private $cps_id = '';
        private $db;
        private $log_file;
        
        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        private function __construct() {
        $this->db = $GLOBALS['wpdb'];
        $this->init_settings();
        $this->define_constants();
        
        $this->log_file = WP_CONTENT_DIR . '/yyk_st_debug.log';
    }
    
    private function log($message) {
        $timestamp = date('Y-m-d H:i:s');
        $log_message = "[$timestamp] $message\n";
        file_put_contents($this->log_file, $log_message, FILE_APPEND);
    }
        
        private function init_settings() {
            $this->api_domain = get_option('yyk_st_api_domain', 'https://www.steamsy.com');
            $this->cps_id = get_option('yyk_st_cps_id', '15907108869');
        }
        
        private function define_constants() {
            if (!defined('YYK_ST_TABLE_GAMES')) {
                define('YYK_ST_TABLE_GAMES', $this->db->prefix . 'yyk_games');
            }
        }
        
        public function init() {
            add_action('admin_menu', [$this, 'add_admin_menu']);
            add_action('wp_ajax_yyk_st_collect', [$this, 'ajax_collect']);
            add_action('wp_ajax_yyk_st_publish', [$this, 'ajax_publish']);
            add_action('wp_ajax_yyk_st_delete_all', [$this, 'ajax_delete_all']);
            add_action('wp_ajax_yyk_st_delete_single', [$this, 'ajax_delete_single']);
            add_action('wp_ajax_yyk_st_fix_database', [$this, 'ajax_fix_database']);
        }
        
        public function ajax_fix_database() {
            check_ajax_referer('yyk_st_fix_database', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => '权限不足']);
            }
            
            $result = $this->check_and_add_columns();
            
            if ($result['success']) {
                wp_send_json_success(['message' => $result['message']]);
            } else {
                wp_send_json_error(['message' => $result['message']]);
            }
        }
        
        public function activate() {
            $this->create_table();
            update_option('yyk_st_api_domain', 'https://www.steamsy.com');
            update_option('yyk_st_cps_id', '15907108869');
        }
        
        private function create_table() {
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            $charset_collate = $this->db->get_charset_collate();
            
            $table_name = YYK_ST_TABLE_GAMES;
            
            $sql = "CREATE TABLE IF NOT EXISTS $table_name (
                id int(11) unsigned NOT NULL AUTO_INCREMENT,
                game_id varchar(50) NOT NULL,
                post_id bigint(20) unsigned DEFAULT NULL,
                game_name varchar(255) DEFAULT NULL,
                game_icon varchar(500) DEFAULT NULL,
                game_size varchar(50) DEFAULT NULL,
                download_url varchar(500) DEFAULT NULL,
                category_name varchar(100) DEFAULT NULL,
                data longtext NOT NULL,
                created_at datetime DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                UNIQUE KEY idx_game_id (game_id),
                KEY idx_post_id (post_id)
            ) $charset_collate;";
            
            dbDelta($sql);
            
            $this->log('数据库表创建/更新完成');
            
            $this->check_and_add_columns();
        }
        
        private function check_and_add_columns() {
            $table_name = YYK_ST_TABLE_GAMES;
            
            $columns = $this->db->get_col("SHOW COLUMNS FROM $table_name");
            $this->log('当前表字段: ' . implode(', ', $columns));
            
            $required_columns = [
                'data' => "ALTER TABLE $table_name ADD COLUMN data longtext NOT NULL",
                'game_name' => "ALTER TABLE $table_name ADD COLUMN game_name varchar(255) DEFAULT NULL",
                'game_icon' => "ALTER TABLE $table_name ADD COLUMN game_icon varchar(500) DEFAULT NULL",
                'game_size' => "ALTER TABLE $table_name ADD COLUMN game_size varchar(50) DEFAULT NULL",
                'download_url' => "ALTER TABLE $table_name ADD COLUMN download_url varchar(500) DEFAULT NULL",
                'category_name' => "ALTER TABLE $table_name ADD COLUMN category_name varchar(100) DEFAULT NULL",
                'created_at' => "ALTER TABLE $table_name ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP"
            ];
            
            $added = 0;
            foreach ($required_columns as $column => $alter_sql) {
                if (!in_array($column, $columns)) {
                    $this->log('添加缺失字段: ' . $column);
                    $result = $this->db->query($alter_sql);
                    if ($result !== false) {
                        $this->log('字段添加完成: ' . $column);
                        $added++;
                    } else {
                        $this->log('字段添加失败: ' . $column . ' - ' . $this->db->last_error);
                        return ['success' => false, 'message' => '字段添加失败: ' . $column];
                    }
                } else {
                    $this->log('字段已存在: ' . $column);
                }
            }
            
            if ($added > 0) {
                return ['success' => true, 'message' => "成功添加 $added 个字段"];
            } else {
                return ['success' => true, 'message' => '所有字段已存在，无需修复'];
            }
        }
        
        private function api_get($endpoint, $params = []) {
            $url = rtrim($this->api_domain, '/') . $endpoint;
            $params['cpsId'] = $this->cps_id;
            $url = add_query_arg($params, $url);
            
            $this->log('API请求 - URL: ' . $url);
            
            $response = wp_remote_get($url, [
                'timeout' => 30,
                'headers' => ['User-Agent' => 'WordPress/ST-Collector']
            ]);
            
            if (is_wp_error($response)) {
                $this->log('API请求错误 - ' . $response->get_error_message());
                return ['success' => false, 'error' => $response->get_error_message()];
            }
            
            $body = wp_remote_retrieve_body($response);
            $this->log('API响应 - 状态码: ' . wp_remote_retrieve_response_code($response) . ', 内容: ' . $body);
            
            $data = json_decode($body, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                $this->log('JSON解析错误 - ' . json_last_error_msg());
                return ['success' => false, 'error' => 'JSON解析失败'];
            }
            
            return ['success' => true, 'data' => $data];
        }
        
        private function fix_url($url) {
            if (empty($url)) return '';
            return str_replace('qudao.guazisy.com', 'qudao.steamsy.com', $url);
        }
        
        // 从接口数据提取字段
        private function extract_post_data($item) {
            $post_data = [
                'post_title' => '',
                'post_content' => '',
                'post_excerpt' => '',
                'game_name' => '',
                'game_icon' => '',
                'game_size' => '',
                'download_url' => '',
                'category_name' => '',
                'meta_fields' => []
            ];
            
            // 游戏ID
            $game_id = $item['id'] ?? '';
            
            $this->log('提取游戏数据 - game_id: ' . $game_id . ', 原始数据: ' . json_encode($item, JSON_UNESCAPED_UNICODE));
            
            // 游戏名称 (接口返回 gamename)
            if (isset($item['gamename']) && !empty($item['gamename'])) {
                $name = trim($item['gamename']);
                $post_data['post_title'] = $name;
                $post_data['game_name'] = $name;
            }
            
            // 图标 (接口返回 pic1)
            if (isset($item['pic1']) && !empty($item['pic1'])) {
                $icon = $this->fix_url($item['pic1']);
                $post_data['game_icon'] = $icon;
                $post_data['meta_fields']['_yyk_app_icon_url'] = $icon;
            }
            
            // 下载地址 (接口可能返回 url 或 Url)
            if (isset($item['url']) && !empty($item['url'])) {
                $url = $this->fix_url($item['url']);
                $post_data['download_url'] = $url;
                $post_data['meta_fields']['_yyk_app_download_url'] = $url;
                $post_data['meta_fields']['_yyk_app_android_url'] = $url;
                $post_data['meta_fields']['_yyk_app_ios_url'] = $url;
            }
            if (isset($item['Url']) && !empty($item['Url'])) {
                $url = $this->fix_url($item['Url']);
                $post_data['download_url'] = $url;
                $post_data['meta_fields']['_yyk_app_download_url'] = $url;
                $post_data['meta_fields']['_yyk_app_android_url'] = $url;
                $post_data['meta_fields']['_yyk_app_ios_url'] = $url;
            }
            
            // 游戏大小 (接口返回 gamesize)
            if (isset($item['gamesize']) && !empty($item['gamesize'])) {
                $size = $item['gamesize'];
                $post_data['game_size'] = $size;
                $post_data['meta_fields']['_yyk_app_size'] = $size;
            }
            
            // 平台类型 (接口返回 device_type)
            if (isset($item['device_type'])) {
                $device_type = intval($item['device_type']);
                $platform = 'all';
                if ($device_type == 0) $platform = 'android';
                if ($device_type == 1) $platform = 'ios';
                $post_data['meta_fields']['_yyk_app_platform'] = $platform;
            }
            
            // 游戏类型 (接口返回 typeword)
            if (isset($item['typeword']) && !empty($item['typeword'])) {
                $post_data['meta_fields']['_yyk_st_game_type'] = $item['typeword'];
                
                $this->log('处理游戏类型 - 原始typeword: ' . $item['typeword']);
                
                // 获取所有有效的分类名称
                $all_category_names = get_option('yyk_st_all_category_names', []);
                $this->log('所有有效分类: ' . json_encode($all_category_names, JSON_UNESCAPED_UNICODE));
                
                // 智能拆分分类：只按逗号拆分，然后匹配有效分类
                $typeword = $item['typeword'];
                // 先替换中文逗号为英文逗号
                $typeword = str_replace('，', ',', $typeword);
                
                // 只按逗号拆分
                $parts = explode(',', $typeword);
                $parts = array_map('trim', $parts);
                $parts = array_filter($parts);
                
                $this->log('拆分后的部分: ' . json_encode($parts, JSON_UNESCAPED_UNICODE));
                
                // 智能匹配：检查每个部分是否在有效分类列表中
                $categories = [];
                foreach ($parts as $part) {
                    if (in_array($part, $all_category_names)) {
                        $categories[] = $part;
                        $this->log('匹配到分类: ' . $part);
                    } else {
                        $this->log('未匹配到分类: ' . $part);
                    }
                }
                
                // 如果没有匹配到分类，使用第一个部分
                if (empty($categories) && !empty($parts)) {
                    $categories = $parts;
                    $this->log('没有匹配到分类，使用拆分结果');
                }
                
                $this->log('最终分类: ' . json_encode($categories, JSON_UNESCAPED_UNICODE));
                
                if (!empty($categories)) {
                    // 第一个分类作为主分类
                    $post_data['category_name'] = $categories[0];
                    // 保存所有分类
                    $post_data['meta_fields']['_yyk_st_all_categories'] = $categories;
                }
            }
            
            // 折扣 (接口返回 discount)
            if (isset($item['discount']) && !empty($item['discount'])) {
                $post_data['meta_fields']['_yyk_st_discount'] = floatval($item['discount']);
            }
            
            // 福利标签 (接口返回 fuli)
            if (isset($item['fuli']) && !empty($item['fuli'])) {
                $fuli = is_array($item['fuli']) ? $item['fuli'] : explode(',', $item['fuli']);
                $post_data['meta_fields']['_yyk_st_welfare_tags'] = maybe_serialize($fuli);
            }
            
            // 一句话简介 (接口返回 Welfare)
            if (isset($item['Welfare']) && !empty($item['Welfare'])) {
                $post_data['meta_fields']['_yyk_st_short_intro'] = $item['Welfare'];
            }
            
            // 游戏介绍 (接口返回 box_content)
            if (isset($item['box_content']) && !empty($item['box_content'])) {
                $post_data['post_content'] = $item['box_content'];
            }
            
            // 福利简介 (接口返回 excerpt)
            if (isset($item['excerpt']) && !empty($item['excerpt'])) {
                $post_data['post_excerpt'] = $item['excerpt'];
            }
            
            // 返利介绍 (接口返回 fanli)
            if (isset($item['fanli']) && !empty($item['fanli'])) {
                $post_data['meta_fields']['_yyk_st_fanli'] = $item['fanli'];
            }
            
            // VIP介绍 (接口返回 Vip)
            if (isset($item['Vip']) && !empty($item['Vip'])) {
                $post_data['meta_fields']['_yyk_st_vip_intro'] = $item['Vip'];
            }
            
            // 五宣图 (接口返回 photo)
            if (isset($item['photo']) && !empty($item['photo'])) {
                $photos = is_array($item['photo']) ? $item['photo'] : json_decode($item['photo'], true);
                
                // 处理photo可能是嵌套数组的情况
                if ($photos) {
                    $processed_photos = [];
                    foreach ($photos as $photo) {
                        if (is_string($photo)) {
                            $processed_photos[] = $photo;
                        } elseif (is_array($photo)) {
                            // 如果是数组，尝试获取url字段或第一个元素
                            if (isset($photo['url'])) {
                                $processed_photos[] = $photo['url'];
                            } elseif (isset($photo[0]) && is_string($photo[0])) {
                                $processed_photos[] = $photo[0];
                            }
                        }
                    }
                    
                    if (!empty($processed_photos)) {
                        $post_data['meta_fields']['_yyk_st_photos'] = json_encode($processed_photos);
                        $this->log('处理后的截图数量: ' . count($processed_photos));
                    }
                }
            }
            
            // 版本 (接口返回 edition)
            if (isset($item['edition']) && !empty($item['edition'])) {
                $post_data['meta_fields']['_yyk_app_version'] = $item['edition'];
            }
            
            // 更新时间 (接口返回 updatetime)
            if (isset($item['updatetime']) && !empty($item['updatetime'])) {
                $post_data['meta_fields']['_yyk_app_update_date'] = $item['updatetime'];
            }
            
            // 下载次数 (接口返回 downloadnum)
            if (isset($item['downloadnum']) && !empty($item['downloadnum'])) {
                $post_data['meta_fields']['_yyk_app_download_count'] = intval($item['downloadnum']);
            }
            
            return $post_data;
        }
        
        private function save_game($game_id, $post_data) {
            $table_name = YYK_ST_TABLE_GAMES;
            
            $this->log('开始保存游戏 - game_id: ' . $game_id);
            
            $exists = $this->db->get_var($this->db->prepare(
                "SELECT id FROM $table_name WHERE game_id = %s",
                $game_id
            ));
            
            $data_json = json_encode($post_data, JSON_UNESCAPED_UNICODE);
            
            $insert_data = [
                'game_id' => $game_id,
                'data' => $data_json,
                'game_name' => $post_data['game_name'] ?? '',
                'game_icon' => $post_data['game_icon'] ?? '',
                'game_size' => $post_data['game_size'] ?? '',
                'download_url' => $post_data['download_url'] ?? '',
                'category_name' => $post_data['category_name'] ?? ''
            ];
            
            if ($exists) {
                $this->log('游戏已存在，跳过 - game_id: ' . $game_id);
                return 'exists';
            } else {
                $this->log('插入游戏 - game_id: ' . $game_id);
                $result = $this->db->insert($table_name, $insert_data);
                $this->log('插入结果 - ' . ($result !== false ? '成功' : '失败') . ', 插入ID: ' . $this->db->insert_id);
                return $result !== false;
            }
        }
        
        private function get_game($game_id) {
            $table_name = YYK_ST_TABLE_GAMES;
            $row = $this->db->get_row($this->db->prepare(
                "SELECT * FROM $table_name WHERE game_id = %s",
                $game_id
            ));
            if (!$row) return null;
            return [
                'post_data' => json_decode($row->data, true),
                'post_id' => $row->post_id,
                'game_name' => $row->game_name,
                'category_name' => $row->category_name,
                'download_url' => $row->download_url
            ];
        }
        
        // ==================== 采集接口 ====================
        
        // 同步分类
        public function fetch_categories() {
            $result = $this->api_get('/v2/', []);
            if (!$result['success']) {
                return ['success' => false, 'error' => $result['error']];
            }
            
            $data = $result['data'];
            $saved = 0;
            $all_category_names = [];
            
            $this->log('开始同步分类 - API数据: ' . json_encode($data, JSON_UNESCAPED_UNICODE));
            
            if (!empty($data['type']) && is_array($data['type'])) {
                foreach ($data['type'] as $item) {
                    if (empty($item['name']) || $item['name'] === '全部') continue;
                    
                    $this->log('处理分类项 - 原始名称: ' . $item['name']);
                    
                    // 保存所有分类名称到数组，用于后续匹配
                    $all_category_names[] = $item['name'];
                    
                    // 分类名称可能包含多个分类（只按逗号分隔，不按空格分隔）
                    $category_names = $item['name'];
                    // 先替换中文逗号为英文逗号
                    $category_names = str_replace('，', ',', $category_names);
                    // 只按逗号分隔
                    $categories = explode(',', $category_names);
                    $categories = array_map('trim', $categories);
                    $categories = array_filter($categories);
                    
                    $this->log('拆分后的分类: ' . json_encode($categories, JSON_UNESCAPED_UNICODE));
                    
                    // 为每个分类创建term
                    foreach ($categories as $category_name) {
                        if (empty($category_name)) continue;
                        $this->log('创建/检查分类 - 分类名称: ' . $category_name);
                        $term = get_term_by('name', $category_name, 'yyk_app_category');
                        if (!$term) {
                            $this->log('分类不存在，创建新分类');
                            wp_insert_term($category_name, 'yyk_app_category', [
                                'slug' => sanitize_title($category_name)
                            ]);
                            $saved++;
                        } else {
                            $this->log('分类已存在 - term_id: ' . $term->term_id);
                        }
                    }
                }
            }
            
            // 保存所有分类名称到选项中，用于智能匹配
            update_option('yyk_st_all_category_names', $all_category_names);
            $this->log('保存所有分类名称到选项: ' . json_encode($all_category_names, JSON_UNESCAPED_UNICODE));
            
            return ['success' => true, 'saved' => $saved];
        }
        
        // 采集游戏列表
        public function fetch_games($page = 1, $limit = 20) {
            $this->log('开始采集游戏 - 页码: ' . $page . ', 限制: ' . $limit);
            
            $result = $this->api_get('/v1/', [
                'pagecode' => $page,
                'pagenum' => $limit
            ]);
            
            if (!$result['success']) {
                $this->log('API请求失败 - ' . $result['error']);
                return ['success' => false, 'error' => $result['error']];
            }
            
            $data = $result['data'];
            $saved = 0;
            $failed = 0;
            $skipped = 0;
            
            $this->log('API返回数据 - ' . json_encode($data, JSON_UNESCAPED_UNICODE));
            
            // 检查是否有 lists 数据
            if (empty($data['lists']) || !is_array($data['lists'])) {
                $this->log('没有游戏数据');
                return ['success' => true, 'saved' => 0, 'failed' => 0, 'total' => 0, 'message' => '没有游戏数据'];
            }
            
            $this->log('找到 ' . count($data['lists']) . ' 个游戏');
            
            foreach ($data['lists'] as $item) {
                try {
                    $game_id = $item['id'] ?? '';
                    if (empty($game_id)) {
                        $this->log('游戏ID为空，跳过');
                        $failed++;
                        continue;
                    }
                    
                    $post_data = $this->extract_post_data($item);
                    $result = $this->save_game($game_id, $post_data);
                    
                    if ($result === true) {
                        $saved++;
                    } elseif ($result === 'exists') {
                        $skipped++;
                        $this->log('游戏已存在，跳过 - game_id: ' . $game_id);
                    } else {
                        $failed++;
                    }
                    
                } catch (Exception $e) {
                    $this->log('保存游戏异常 - ' . $e->getMessage());
                    $failed++;
                }
            }
            
            $this->log('采集完成 - 成功: ' . $saved . ', 跳过: ' . $skipped . ', 失败: ' . $failed);
            
            return [
                'success' => true,
                'saved' => $saved,
                'failed' => $failed,
                'skipped' => $skipped,
                'total' => count($data['lists']),
                'api_total' => $data['total_num'] ?? 0,
                'total_page' => $data['total_page'] ?? 0,
                'now_page' => $data['now_page'] ?? $page
            ];
        }
        
        // 采集游戏列表（单次采集50个）
        public function fetch_games_all($page = 1) {
            $limit = 50;
            
            $this->log('开始采集游戏列表 - 页码: ' . $page . ', 限制: ' . $limit);
            
            $result = $this->fetch_games($page, $limit);
            
            if (!$result['success']) {
                $this->log('采集失败: ' . ($result['error'] ?? '未知错误'));
                return $result;
            }
            
            $this->log('采集完成 - 保存: ' . ($result['saved'] ?? 0) . ', 跳过: ' . ($result['skipped'] ?? 0) . ', 失败: ' . ($result['failed'] ?? 0));
            
            // 添加页码信息
            $result['page'] = $page;
            
            return $result;
        }
        
        // 采集游戏详情（包含photo五宣图）
        public function fetch_game_detail($game_id) {
            $this->log('开始采集游戏详情 - game_id: ' . $game_id);
            
            $result = $this->api_get('/v3/', [
                'gid' => $game_id
            ]);
            
            if (!$result['success']) {
                $this->log('游戏详情API请求失败 - ' . $result['error']);
                return ['success' => false, 'error' => $result['error']];
            }
            
            $data = $result['data'];
            $this->log('游戏详情API返回数据 - ' . json_encode($data, JSON_UNESCAPED_UNICODE));
            
            // 检查返回状态
            if (empty($data['A']) || $data['A'] != 1) {
                $this->log('游戏详情获取失败 - ' . ($data['B'] ?? '未知错误'));
                return ['success' => false, 'error' => $data['B'] ?? '未知错误'];
            }
            
            $game_detail = $data['C'] ?? [];
            if (empty($game_detail)) {
                $this->log('游戏详情数据为空');
                return ['success' => false, 'error' => '游戏详情数据为空'];
            }
            
            // 更新数据库中的游戏详情
            $table_name = YYK_ST_TABLE_GAMES;
            $exists = $this->db->get_var($this->db->prepare(
                "SELECT id FROM $table_name WHERE game_id = %s",
                $game_id
            ));
            
            if ($exists) {
                // 获取现有数据
                $row = $this->db->get_row($this->db->prepare(
                    "SELECT data FROM $table_name WHERE game_id = %s",
                    $game_id
                ));
                
                if ($row) {
                    $post_data = json_decode($row->data, true);
                    
                    // 更新photo字段
                    if (isset($game_detail['photo']) && !empty($game_detail['photo'])) {
                        $photos = is_array($game_detail['photo']) ? $game_detail['photo'] : json_decode($game_detail['photo'], true);
                        
                        if ($photos) {
                            $processed_photos = [];
                            foreach ($photos as $photo) {
                                if (is_string($photo)) {
                                    $processed_photos[] = $photo;
                                } elseif (is_array($photo)) {
                                    if (isset($photo['url'])) {
                                        $processed_photos[] = $photo['url'];
                                    } elseif (isset($photo[0]) && is_string($photo[0])) {
                                        $processed_photos[] = $photo[0];
                                    }
                                }
                            }
                            
                            if (!empty($processed_photos)) {
                                $post_data['meta_fields']['_yyk_st_photos'] = json_encode($processed_photos);
                                $this->log('更新游戏截图 - game_id: ' . $game_id . ', 截图数: ' . count($processed_photos));
                            }
                        }
                    }
                    
                    // 更新其他字段
                    $post_data = array_merge($post_data, $this->extract_post_data($game_detail));
                    
                    // 更新数据库
                    $data_json = json_encode($post_data, JSON_UNESCAPED_UNICODE);
                    $this->db->update(
                        $table_name,
                        ['data' => $data_json],
                        ['game_id' => $game_id]
                    );
                    
                    return ['success' => true, 'message' => '游戏详情更新成功'];
                }
            }
            
            return ['success' => false, 'error' => '游戏不存在'];
        }
        
        // 采集预约游戏列表
        public function fetch_reserve_games($page = 1, $limit = 50) {
            $this->log('开始采集预约游戏 - 页码: ' . $page . ', 限制: ' . $limit);
            
            $result = $this->api_get('/v4/', [
                'pagecode' => $page,
                'pagenum' => $limit
            ]);
            
            if (!$result['success']) {
                $this->log('预约游戏API请求失败 - ' . $result['error']);
                return ['success' => false, 'error' => $result['error']];
            }
            
            $data = $result['data'];
            $saved = 0;
            $failed = 0;
            
            $this->log('预约游戏API返回数据 - ' . json_encode($data, JSON_UNESCAPED_UNICODE));
            
            if (empty($data['lists']) || !is_array($data['lists'])) {
                $this->log('没有预约游戏数据');
                return ['success' => true, 'saved' => 0, 'failed' => 0, 'total' => 0, 'message' => '没有预约游戏数据'];
            }
            
            $this->log('找到 ' . count($data['lists']) . ' 个预约游戏');
            
            foreach ($data['lists'] as $item) {
                try {
                    $game_id = $item['id'] ?? '';
                    if (empty($game_id)) {
                        $this->log('预约游戏ID为空，跳过');
                        $failed++;
                        continue;
                    }
                    
                    // 添加预约标记
                    $post_data = $this->extract_post_data($item);
                    $post_data['meta_fields']['_yyk_st_is_reserve'] = true;
                    
                    $result = $this->save_game($game_id, $post_data);
                    
                    if ($result) {
                        $saved++;
                    } else {
                        $this->log('预约游戏已存在，跳过 - game_id: ' . $game_id);
                    }
                    
                } catch (Exception $e) {
                    $this->log('保存预约游戏异常 - ' . $e->getMessage());
                    $failed++;
                }
            }
            
            $this->log('预约游戏采集完成 - 成功: ' . $saved . ', 失败: ' . $failed);
            
            return [
                'success' => true,
                'saved' => $saved,
                'failed' => $failed,
                'total' => count($data['lists']),
                'api_total' => $data['total_num'] ?? 0
            ];
        }
        
        // 采集排行榜游戏列表
        public function fetch_ranking_games($toptype = 0, $diynum = 1, $totalnum = 50) {
            $this->log('开始采集排行榜游戏 - 类型: ' . $toptype . ', 天数: ' . $diynum . ', 数量: ' . $totalnum);
            
            $result = $this->api_get('/v5/', [
                'toptype' => $toptype,
                'diynum' => $diynum,
                'totalnum' => $totalnum
            ]);
            
            if (!$result['success']) {
                $this->log('排行榜API请求失败 - ' . $result['error']);
                return ['success' => false, 'error' => $result['error']];
            }
            
            $data = $result['data'];
            $saved = 0;
            $failed = 0;
            
            $this->log('排行榜API返回数据 - ' . json_encode($data, JSON_UNESCAPED_UNICODE));
            
            if (empty($data['lists']) || !is_array($data['lists'])) {
                $this->log('没有排行榜数据');
                return ['success' => true, 'saved' => 0, 'failed' => 0, 'total' => 0, 'message' => '没有排行榜数据'];
            }
            
            $this->log('找到 ' . count($data['lists']) . ' 个排行榜游戏');
            
            foreach ($data['lists'] as $item) {
                try {
                    $game_id = $item['id'] ?? '';
                    if (empty($game_id)) {
                        $this->log('排行榜游戏ID为空，跳过');
                        $failed++;
                        continue;
                    }
                    
                    $post_data = $this->extract_post_data($item);
                    $post_data['meta_fields']['_yyk_st_ranking_type'] = $toptype;
                    $post_data['meta_fields']['_yyk_st_ranking_days'] = $diynum;
                    
                    $result = $this->save_game($game_id, $post_data);
                    
                    if ($result) {
                        $saved++;
                    } else {
                        $this->log('排行榜游戏已存在，跳过 - game_id: ' . $game_id);
                    }
                    
                } catch (Exception $e) {
                    $this->log('保存排行榜游戏异常 - ' . $e->getMessage());
                    $failed++;
                }
            }
            
            $this->log('排行榜采集完成 - 成功: ' . $saved . ', 失败: ' . $failed);
            
            return [
                'success' => true,
                'saved' => $saved,
                'failed' => $failed,
                'total' => count($data['lists'])
            ];
        }
        
        // 采集游戏礼包列表
        public function fetch_game_gifts($game_id, $page = 1) {
            $this->log('开始采集游戏礼包 - game_id: ' . $game_id . ', 页码: ' . $page);
            
            $result = $this->api_get('/v6/', [
                'gid' => $game_id,
                'pagecode' => $page
            ]);
            
            if (!$result['success']) {
                $this->log('礼包API请求失败 - ' . $result['error']);
                return ['success' => false, 'error' => $result['error']];
            }
            
            $data = $result['data'];
            $saved = 0;
            
            $this->log('礼包API返回数据 - ' . json_encode($data, JSON_UNESCAPED_UNICODE));
            
            if (empty($data['lists']) || !is_array($data['lists'])) {
                $this->log('没有礼包数据');
                return ['success' => true, 'saved' => 0, 'total' => 0, 'message' => '没有礼包数据'];
            }
            
            $this->log('找到 ' . count($data['lists']) . ' 个礼包');
            
            // 保存礼包数据到游戏meta
            $table_name = YYK_ST_TABLE_GAMES;
            $exists = $this->db->get_var($this->db->prepare(
                "SELECT id FROM $table_name WHERE game_id = %s",
                $game_id
            ));
            
            if ($exists) {
                // 获取现有数据
                $row = $this->db->get_row($this->db->prepare(
                    "SELECT data FROM $table_name WHERE game_id = %s",
                    $game_id
                ));
                
                if ($row) {
                    $post_data = json_decode($row->data, true);
                    
                    // 保存礼包数据
                    $post_data['meta_fields']['_yyk_st_gifts'] = json_encode($data['lists']);
                    $saved = count($data['lists']);
                    
                    // 更新数据库
                    $data_json = json_encode($post_data, JSON_UNESCAPED_UNICODE);
                    $this->db->update(
                        $table_name,
                        ['data' => $data_json],
                        ['game_id' => $game_id]
                    );
                    
                    $this->log('更新游戏礼包 - game_id: ' . $game_id . ', 礼包数: ' . $saved);
                }
            }
            
            return [
                'success' => true,
                'saved' => $saved,
                'total' => count($data['lists']),
                'api_total' => $data['total_num'] ?? 0
            ];
        }
        
        // 一键采集
        public function collect_all() {
            $results = [];
            $results['categories'] = $this->fetch_categories();
            
            $total_saved = 0;
            $total_failed = 0;
            $page = 1;
            $limit = 20;
            $has_more = true;
            
            while ($has_more) {
                $this->log('采集第 ' . $page . ' 页游戏数据');
                $result = $this->fetch_games($page, $limit);
                
                if (!$result['success']) {
                    $this->log('第 ' . $page . ' 页采集失败: ' . ($result['error'] ?? '未知错误'));
                    $has_more = false;
                    break;
                }
                
                $saved = $result['saved'] ?? 0;
                $failed = $result['failed'] ?? 0;
                $total_saved += $saved;
                $total_failed += $failed;
                
                $this->log('第 ' . $page . ' 页采集完成 - 保存: ' . $saved . ', 失败: ' . $failed);
                
                // 检查是否还有更多数据
                if ($result['total'] < $limit) {
                    $this->log('第 ' . $page . ' 页数据量少于 ' . $limit . '，已到最后一页');
                    $has_more = false;
                } else {
                    $page++;
                }
                
                if ($page > 100) {
                    $this->log('已采集100页，停止采集');
                    $has_more = false;
                }
            }
            
            $results['games'] = [
                'saved' => $total_saved,
                'failed' => $total_failed,
                'total' => $total_saved + $total_failed,
                'pages' => $page - 1
            ];
            
            $this->log('采集全部完成 - 总保存: ' . $total_saved . ', 总失败: ' . $total_failed . ', 总页数: ' . ($page - 1));
            
            return $results;
        }
        
        public function get_all_games($page = 1, $limit = 20) {
            $table_name = YYK_ST_TABLE_GAMES;
            $offset = ($page - 1) * $limit;
            
            $this->log('查询游戏列表 - 表名: ' . $table_name . ', 页码: ' . $page . ', 限制: ' . $limit);
            
            $sql = "SELECT * FROM $table_name ORDER BY CASE WHEN post_id IS NULL THEN 0 ELSE 1 END, id DESC LIMIT %d OFFSET %d";
            $this->log('SQL语句: ' . $sql);
            
            $games = $this->db->get_results($this->db->prepare(
                $sql,
                $limit, $offset
            ));
            
            $this->log('查询结果 - 游戏数: ' . count($games ?: []) . ', 数据: ' . json_encode($games, JSON_UNESCAPED_UNICODE));
            
            $total = $this->db->get_var("SELECT COUNT(*) FROM $table_name");
            
            $this->log('查询结果 - 游戏数: ' . count($games ?: []) . ', 总数: ' . $total);
            
            return ['games' => $games ?: [], 'total' => intval($total)];
        }
        
        // ==================== 发布功能 ====================
        
        private function ensure_category($category_name) {
            if (empty($category_name)) return 0;
            
            $this->log('确保分类存在 - 分类名称: ' . $category_name);
            
            $term = get_term_by('name', $category_name, 'yyk_app_category');
            if (!$term) {
                $this->log('分类不存在，创建新分类 - 分类名称: ' . $category_name);
                $result = wp_insert_term($category_name, 'yyk_app_category', [
                    'slug' => sanitize_title($category_name)
                ]);
                if (is_wp_error($result)) {
                    $this->log('创建分类失败 - 错误: ' . $result->get_error_message());
                    return 0;
                }
                $this->log('分类创建成功 - term_id: ' . $result['term_id']);
                return $result['term_id'];
            }
            $this->log('分类已存在 - term_id: ' . $term->term_id);
            return $term->term_id;
        }
        
        public function publish_game($game_id) {
            $game = $this->get_game($game_id);
            if (!$game) {
                return ['success' => false, 'error' => '游戏不存在'];
            }
            
            if (!empty($game['post_id'])) {
                return ['success' => false, 'error' => '游戏已发布'];
            }
            
            $post_data = $game['post_data'];
            $meta_fields = $post_data['meta_fields'] ?? [];
            $download_url = $game['download_url'] ?: ($meta_fields['_yyk_app_download_url'] ?? '');
            
            // 构建内容
            $content = $post_data['post_content'] ?? '';
            if (!empty($meta_fields['_yyk_st_fanli'])) {
                $content .= "\n\n<h3>返利介绍</h3>\n" . $meta_fields['_yyk_st_fanli'];
            }
            if (!empty($meta_fields['_yyk_st_vip_intro'])) {
                $content .= "\n\n<h3>VIP介绍</h3>\n" . $meta_fields['_yyk_st_vip_intro'];
            }
            
            // 创建文章
            $post_id = wp_insert_post([
                'post_title' => $post_data['post_title'] ?: ($game['game_name'] ?: '未命名游戏'),
                'post_content' => $content,
                'post_excerpt' => $post_data['post_excerpt'] ?? '',
                'post_status' => 'publish',
                'post_type' => 'yyk_app_download',
                'post_author' => get_current_user_id(),
            ]);
            
            if (is_wp_error($post_id)) {
                return ['success' => false, 'error' => $post_id->get_error_message()];
            }
            
            // 保存自定义字段
            foreach ($meta_fields as $key => $value) {
                if (!empty($value)) {
                    update_post_meta($post_id, $key, $value);
                }
            }
            
            // 确保下载地址被保存
            if (!empty($download_url)) {
                update_post_meta($post_id, '_yyk_app_download_url', $download_url);
            }
            
            update_post_meta($post_id, '_yyk_st_game_id', $game_id);
            
            // 设置分类（支持多个分类）
            $all_categories = $meta_fields['_yyk_st_all_categories'] ?? [];
            
            $this->log('设置分类 - 所有分类: ' . json_encode($all_categories, JSON_UNESCAPED_UNICODE));
            
            if (empty($all_categories)) {
                // 如果没有多个分类，使用主分类
                $category_name = $post_data['category_name'] ?: $game['category_name'];
                $this->log('使用主分类 - 分类名称: ' . $category_name);
                if (!empty($category_name)) {
                    $term_id = $this->ensure_category($category_name);
                    if ($term_id) {
                        wp_set_post_terms($post_id, [$term_id], 'yyk_app_category');
                        $this->log('设置主分类成功 - term_id: ' . $term_id);
                    }
                }
            } else {
                // 关联所有分类
                $term_ids = [];
                foreach ($all_categories as $category) {
                    if (empty($category)) continue;
                    $this->log('处理分类 - 分类名称: ' . $category);
                    $term_id = $this->ensure_category($category);
                    if ($term_id) {
                        $term_ids[] = $term_id;
                        $this->log('分类关联成功 - term_id: ' . $term_id);
                    }
                }
                
                if (!empty($term_ids)) {
                    wp_set_post_terms($post_id, $term_ids, 'yyk_app_category');
                    $this->log('设置所有分类成功 - term_ids: ' . json_encode($term_ids));
                }
            }
            
            $table_name = YYK_ST_TABLE_GAMES;
            $this->db->update($table_name, ['post_id' => $post_id], ['game_id' => $game_id]);
            
            return ['success' => true, 'post_id' => $post_id];
        }
        
        public function publish_all() {
            $table_name = YYK_ST_TABLE_GAMES;
            $games = $this->db->get_results(
                "SELECT * FROM $table_name WHERE post_id IS NULL OR post_id = 0 LIMIT 50"
            );
            
            $published = 0;
            $failed = 0;
            $start_time = time();
            
            foreach ($games as $game) {
                if (time() - $start_time > 25) {
                    break;
                }
                
                $result = $this->publish_game($game->game_id);
                if ($result['success']) {
                    $published++;
                } else {
                    $failed++;
                }
            }
            
            return ['published' => $published, 'failed' => $failed];
        }
        
        // ==================== 删除功能 ====================
        
        public function delete_game($game_id) {
            $game = $this->get_game($game_id);
            if (!$game) return ['success' => false, 'error' => '游戏不存在'];
            
            if (!empty($game['post_id'])) {
                wp_delete_post($game['post_id'], true);
            }
            
            $table_name = YYK_ST_TABLE_GAMES;
            $this->db->delete($table_name, ['game_id' => $game_id]);
            return ['success' => true];
        }
        
        public function delete_all() {
            $table_name = YYK_ST_TABLE_GAMES;
            $games = $this->db->get_results("SELECT * FROM $table_name");
            $deleted = 0;
            
            foreach ($games as $game) {
                if (!empty($game->post_id)) {
                    wp_delete_post($game->post_id, true);
                }
                $this->db->delete($table_name, ['id' => $game->id]);
                $deleted++;
            }
            
            return ['deleted' => $deleted, 'failed' => 0];
        }
        
        // ==================== AJAX ====================
        
        public function ajax_collect() {
            check_ajax_referer('yyk_st_collect', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => '权限不足']);
            }
            
            $type = isset($_POST['type']) ? sanitize_text_field($_POST['type']) : '';
            
            switch ($type) {
                case 'categories':
                    $result = $this->fetch_categories();
                    break;
                case 'games':
                    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
                    $result = $this->fetch_games($page, 20);
                    break;
                case 'games_all':
                    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
                    $result = $this->fetch_games_all($page);
                    wp_send_json_success($result);
                    return;
                case 'detail':
                    $game_id = isset($_POST['game_id']) ? sanitize_text_field($_POST['game_id']) : '';
                    $result = $this->fetch_game_detail($game_id);
                    break;
                case 'reserve':
                    $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
                    $result = $this->fetch_reserve_games($page, 20);
                    break;
                case 'ranking':
                    $result = $this->fetch_ranking_games(0, 1, 50);
                    break;
                case 'gifts':
                    $game_id = isset($_POST['game_id']) ? sanitize_text_field($_POST['game_id']) : '';
                    $result = $this->fetch_game_gifts($game_id, 1);
                    break;
                case 'all':
                    $result = $this->collect_all();
                    wp_send_json_success($result);
                    return;
                default:
                    wp_send_json_error(['message' => '未知类型']);
                    return;
            }
            
            if ($result['success']) {
                wp_send_json_success($result);
            } else {
                wp_send_json_error(['message' => $result['error'] ?? '采集失败']);
            }
        }
        
        public function ajax_publish() {
            check_ajax_referer('yyk_st_publish', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => '权限不足']);
            }
            
            $action_type = isset($_POST['action_type']) ? sanitize_text_field($_POST['action_type']) : '';
            
            if ($action_type === 'list') {
                $page = isset($_POST['page']) ? intval($_POST['page']) : 1;
                $result = $this->get_all_games($page, 50);
                
                $this->log('AJAX返回 - 成功: true, 游戏数: ' . count($result['games']) . ', 总数: ' . $result['total']);
                
                wp_send_json_success($result);
                return;
            }
            
            if ($action_type === 'single') {
                $game_id = isset($_POST['game_id']) ? sanitize_text_field($_POST['game_id']) : '';
                $result = $this->publish_game($game_id);
                if ($result['success']) {
                    wp_send_json_success(['message' => '发布成功']);
                } else {
                    wp_send_json_error(['message' => $result['error']]);
                }
                return;
            }
            
            if ($action_type === 'all') {
                $result = $this->publish_all();
                wp_send_json_success(['message' => "发布完成: 成功{$result['published']}个，失败{$result['failed']}个"]);
                return;
            }
            
            wp_send_json_error(['message' => '未知操作']);
        }
        
        public function ajax_delete_single() {
            check_ajax_referer('yyk_st_delete_single', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => '权限不足']);
            }
            
            $game_id = isset($_POST['game_id']) ? sanitize_text_field($_POST['game_id']) : '';
            $result = $this->delete_game($game_id);
            
            if ($result['success']) {
                wp_send_json_success(['message' => '删除成功']);
            } else {
                wp_send_json_error(['message' => $result['error']]);
            }
        }
        
        public function ajax_delete_all() {
            check_ajax_referer('yyk_st_delete', 'nonce');
            if (!current_user_can('manage_options')) {
                wp_send_json_error(['message' => '权限不足']);
            }
            
            $result = $this->delete_all();
            wp_send_json_success(['message' => "删除完成: 成功{$result['deleted']}个，失败{$result['failed']}个"]);
        }
        
        // ==================== 后台页面 ====================
        
        public function add_admin_menu() {
            add_submenu_page(
                'edit.php?post_type=yyk_app_download',
                'ST游戏采集',
                'ST游戏采集',
                'manage_options',
                'yyk-st-collector',
                [$this, 'render_admin_page']
            );
        }
        
        public function render_admin_page() {
            if (isset($_POST['save_settings'])) {
                check_admin_referer('yyk_st_settings');
                update_option('yyk_st_api_domain', sanitize_text_field($_POST['api_domain']));
                update_option('yyk_st_cps_id', sanitize_text_field($_POST['cps_id']));
                echo '<div class="notice notice-success"><p>设置已保存</p></div>';
            }
            
            $api_domain = get_option('yyk_st_api_domain', 'https://www.steamsy.com');
            $cps_id = get_option('yyk_st_cps_id', '15907108869');
            
            $table_name = YYK_ST_TABLE_GAMES;
            $table_exists = $this->db->get_var("SHOW TABLES LIKE '$table_name'");
            $game_count = $table_exists ? $this->db->get_var("SELECT COUNT(*) FROM $table_name") : 0;
            
            ?>
            <div class="wrap">
                <h1>ST游戏采集</h1>
                
                <div class="notice notice-info" style="margin: 20px 0;">
                    <strong>数据库状态:</strong> 
                    表名: <?php echo esc_html($table_name); ?> | 
                    表状态: <?php echo $table_exists ? '✅ 存在' : '❌ 不存在'; ?> | 
                    游戏数量: <?php echo intval($game_count); ?>
                    <button type="button" class="button button-secondary" id="fix_database" style="margin-left: 20px;">🔧 修复数据库</button>
                </div>
                
                <div class="nav-tab-wrapper">
                    <a href="#settings" class="nav-tab nav-tab-active">⚙️ 设置</a>
                    <a href="#collect" class="nav-tab">📥 采集</a>
                    <a href="#publish" class="nav-tab">📦 发布管理</a>
                </div>
                
                <div id="settings" class="tab-content" style="display:block">
                    <form method="post" style="background:#fff;padding:20px;margin-top:20px;border-radius:8px">
                        <?php wp_nonce_field('yyk_st_settings'); ?>
                        <table class="form-table">
                            <tr><th><label>API域名</label></th>
                                <td><input type="text" name="api_domain" value="<?php echo esc_attr($api_domain); ?>" class="regular-text"><br>
                                <span class="description">默认: https://www.steamsy.com</span></th>
                            </tr>
                            <tr><th><label>渠道ID</label></th>
                                <td><input type="text" name="cps_id" value="<?php echo esc_attr($cps_id); ?>" class="regular-text"><br>
                                <span class="description">您的ST手游渠道账号</span></th>
                            </tr>
                        </table>
                        <p class="submit"><button type="submit" name="save_settings" class="button button-primary">保存设置</button></p>
                    </form>
                </div>
                
                <div id="collect" class="tab-content" style="display:none">
                    <div style="background:#fff;padding:20px;margin-top:20px;border-radius:8px">
                        <h3>采集接口</h3>
                        <div class="yyk-collect-buttons">
                            <div class="collect-group">
                                <h4>📁 分类管理</h4>
                                <button type="button" class="button" id="collect_categories">同步分类 (v2)</button>
                            </div>
                            <div class="collect-group">
                                <h4>🎮 游戏采集</h4>
                                <button type="button" class="button button-primary" id="collect_games">采集游戏列表 (v1)</button>
                            </div>
                            <div class="collect-group">
                                <h4>📋 游戏详情</h4>
                                <button type="button" class="button" id="collect_details">采集游戏详情 (v3)</button>
                            </div>
                            <div class="collect-group">
                                <h4>📅 预约游戏</h4>
                                <button type="button" class="button" id="collect_reserve">采集预约游戏 (v4)</button>
                            </div>
                            <div class="collect-group">
                                <h4>🏆 排行榜</h4>
                                <button type="button" class="button" id="collect_ranking">采集排行榜 (v5)</button>
                            </div>
                            <div class="collect-group">
                                <h4>🎁 游戏礼包</h4>
                                <button type="button" class="button" id="collect_gifts">采集游戏礼包 (v6)</button>
                            </div>
                            <div class="collect-group">
                                <h4>🚀 批量操作</h4>
                                <button type="button" class="button button-secondary" id="collect_all">一键采集全部</button>
                            </div>
                        </div>
                        <div id="collect_status" style="margin-top:20px;padding:15px;background:#f5f5f5;border-radius:4px;display:none"></div>
                        <div id="collect_result" style="margin-top:15px"></div>
                    </div>
                </div>
                
                <div id="publish" class="tab-content" style="display:none">
                    <div style="background:#fff;padding:20px;margin-top:20px;border-radius:8px">
                        <div class="yyk-publish-header">
                            <h3>游戏管理</h3>
                            <div class="yyk-batch-actions">
                                <button type="button" class="button button-primary" id="publish_all">📤 发布所有未发布游戏</button>
                                <button type="button" class="button button-danger" id="delete_all" style="background:#dc3545;color:white;border-color:#dc3545">🗑️ 删除所有游戏</button>
                            </div>
                        </div>
                        <div id="game_list_stats" style="margin:15px 0;padding:10px;background:#e8f4fc;border-radius:4px"></div>
                        <div id="game_list"></div>
                        <div id="game_pagination" class="yyk-pagination"></div>
                    </div>
                </div>
            </div>
            
            <style>
            .yyk-collect-buttons { display: flex; flex-wrap: wrap; gap: 30px; margin: 20px 0; }
            .collect-group { background: #f8f9fa; padding: 15px; border-radius: 8px; min-width: 200px; }
            .collect-group h4 { margin: 0 0 10px 0; color: #0073aa; }
            .collect-group button { margin: 5px 5px 5px 0; }
            .yyk-publish-header { display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; }
            .yyk-batch-actions { display: flex; gap: 10px; }
            .button-danger:hover { background: #c82333 !important; }
            .yyk-game-status { padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 500; }
            .yyk-game-status.published { background: #d4edda; color: #155724; }
            .yyk-game-status.unpublished { background: #fff3cd; color: #856404; }
            .yyk-pagination { display: flex; justify-content: center; gap: 8px; margin-top: 20px; }
            .yyk-pagination a { display: inline-block; padding: 6px 12px; background: #f0f0f0; border-radius: 4px; text-decoration: none; color: #333; cursor: pointer; }
            .yyk-pagination a.active { background: #0073aa; color: #fff; }
            .collect-result-item { padding: 8px 12px; margin: 5px 0; border-radius: 4px; }
            .collect-result-item.success { background: #d4edda; color: #155724; border-left: 4px solid #28a745; }
            .collect-result-item.error { background: #f8d7da; color: #721c24; border-left: 4px solid #dc3545; }
            </style>
            
            <script>
            jQuery(document).ready(function($) {
                $('.nav-tab').click(function(e) {
                    e.preventDefault();
                    $('.nav-tab').removeClass('nav-tab-active');
                    $(this).addClass('nav-tab-active');
                    $('.tab-content').hide();
                    $($(this).attr('href')).show();
                });
                
                function showStatus(msg, isSuccess) {
                    $('#collect_status').show().html('<span style="color:' + (isSuccess ? 'green' : 'red') + '">' + msg + '</span>');
                    setTimeout(function() { $('#collect_status').fadeOut(); }, 5000);
                }
                
                function showResult(data, type) {
                    var html = '<div class="collect-result-item ' + (data.success ? 'success' : 'error') + '">';
                    html += '<strong>' + type + '</strong><br>';
                    if (data.success) {
                        if (data.saved !== undefined) html += '✅ 成功: ' + data.saved + ' 个<br>';
                        if (data.failed !== undefined && data.failed > 0) html += '❌ 失败: ' + data.failed + ' 个<br>';
                        if (data.total !== undefined) html += '📊 总计: ' + data.total + ' 个';
                    } else {
                        html += '❌ 失败: ' + (data.message || data.error || '未知错误');
                    }
                    html += '</div>';
                    $('#collect_result').prepend(html);
                    setTimeout(function() { $('#collect_result').empty(); }, 10000);
                }
                
                // 同步分类
                $('#collect_categories').click(function() {
                    $(this).prop('disabled', true).text('同步中...');
                    $.post(ajaxurl, { action: 'yyk_st_collect', type: 'categories', nonce: '<?php echo wp_create_nonce("yyk_st_collect"); ?>' }, function(r) {
                        $(this).prop('disabled', false).text('同步分类');
                        if (r.success) {
                            showStatus('同步成功，新增 ' + (r.data.saved || 0) + ' 个分类', true);
                            showResult(r.data, '分类同步');
                        } else {
                            showStatus(r.data.message || '同步失败', false);
                        }
                    }.bind(this));
                });
                
                // 采集游戏列表
                var currentPage = 1;
                $('#collect_games').click(function() {
                    $(this).prop('disabled', true).text('采集中...');
                    $('#collect_result').empty();
                    $.post(ajaxurl, { action: 'yyk_st_collect', type: 'games_all', page: currentPage, nonce: '<?php echo wp_create_nonce("yyk_st_collect"); ?>' }, function(r) {
                        $(this).prop('disabled', false).text('采集游戏列表');
                        if (r.success) {
                            var data = r.data;
                            var pageInfo = '第 ' + (data.now_page || data.page || 1) + ' 页';
                            if (data.total_page) {
                                pageInfo += ' / 共 ' + data.total_page + ' 页';
                            }
                            if (data.api_total) {
                                pageInfo += ' (总计 ' + data.api_total + ' 个游戏)';
                            }
                            
                            var html = '<div class="collect-result-item success">🎮 ' + pageInfo + '<br>成功 ' + (data.saved || 0) + ' 个，跳过 ' + (data.skipped || 0) + ' 个，失败 ' + (data.failed || 0) + ' 个</div>';
                            $('#collect_result').html(html);
                            showStatus('第 ' + (data.now_page || data.page || 1) + ' 页采集完成: 成功 ' + (data.saved || 0) + ' 个，跳过 ' + (data.skipped || 0) + ' 个', true);
                            loadGameList();
                            
                            // 检查是否到达最后一页
                            var isLastPage = false;
                            if (data.total_page && data.now_page >= data.total_page) {
                                isLastPage = true;
                            }
                            
                            if (!isLastPage) {
                                $('#collect_result').append('<div style="margin-top:10px;padding:10px;background:#fff3cd;border-radius:4px">💡 <button type="button" class="button button-small" id="collect_next_page" style="margin-right:10px">采集下一页</button>当前页已采集完成</div>');
                            } else {
                                $('#collect_result').append('<div style="margin-top:10px;padding:10px;background:#d4edda;border-radius:4px">✅ 所有页面采集完成！</div>');
                            }
                        } else {
                            showStatus(r.data.message || '采集失败', false);
                        }
                    }.bind(this));
                });
                
                // 采集下一页
                $(document).on('click', '#collect_next_page', function() {
                    currentPage++;
                    $('#collect_games').click();
                });
                
                // 采集游戏详情
                $('#collect_details').click(function() {
                    if (!confirm('确定要采集所有游戏的详情吗？这可能需要较长时间。')) return;
                    
                    $(this).prop('disabled', true).text('采集中...');
                    $('#collect_result').empty();
                    
                    // 先获取所有游戏ID
                    $.post(ajaxurl, { action: 'yyk_st_publish', action_type: 'list', page: 1, nonce: '<?php echo wp_create_nonce("yyk_st_publish"); ?>' }, function(r) {
                        if (r.success && r.data.games && r.data.games.length > 0) {
                            var gameIds = r.data.games.map(function(g) { return g.game_id; });
                            var processed = 0;
                            var failed = 0;
                            
                            function processNext(index) {
                                if (index >= gameIds.length) {
                                    $('#collect_details').prop('disabled', false).text('采集游戏详情');
                                    showStatus('游戏详情采集完成: 成功 ' + processed + ' 个，失败 ' + failed + ' 个', true);
                                    loadGameList();
                                    return;
                                }
                                
                                $.post(ajaxurl, { 
                                    action: 'yyk_st_collect', 
                                    type: 'detail', 
                                    game_id: gameIds[index], 
                                    nonce: '<?php echo wp_create_nonce("yyk_st_collect"); ?>' 
                                }, function(r) {
                                    if (r.success) {
                                        processed++;
                                    } else {
                                        failed++;
                                    }
                                    
                                    $('#collect_status').show().html('<span>处理进度: ' + (index + 1) + '/' + gameIds.length + '</span>');
                                    setTimeout(function() { processNext(index + 1); }, 500);
                                });
                            }
                            
                            processNext(0);
                        } else {
                            $('#collect_details').prop('disabled', false).text('采集游戏详情');
                            showStatus('没有游戏数据，请先采集游戏列表', false);
                        }
                    });
                });
                
                // 采集预约游戏
                $('#collect_reserve').click(function() {
                    $(this).prop('disabled', true).text('采集中...');
                    $.post(ajaxurl, { action: 'yyk_st_collect', type: 'reserve', page: 1, nonce: '<?php echo wp_create_nonce("yyk_st_collect"); ?>' }, function(r) {
                        $(this).prop('disabled', false).text('采集预约游戏');
                        if (r.success) {
                            showStatus('采集完成: 成功 ' + (r.data.saved || 0) + ' 个，失败 ' + (r.data.failed || 0) + ' 个', true);
                            showResult(r.data, '预约游戏采集');
                            loadGameList();
                        } else {
                            showStatus(r.data.message || '采集失败', false);
                        }
                    }.bind(this));
                });
                
                // 采集排行榜
                $('#collect_ranking').click(function() {
                    $(this).prop('disabled', true).text('采集中...');
                    $.post(ajaxurl, { action: 'yyk_st_collect', type: 'ranking', nonce: '<?php echo wp_create_nonce("yyk_st_collect"); ?>' }, function(r) {
                        $(this).prop('disabled', false).text('采集排行榜');
                        if (r.success) {
                            showStatus('采集完成: 成功 ' + (r.data.saved || 0) + ' 个，失败 ' + (r.data.failed || 0) + ' 个', true);
                            showResult(r.data, '排行榜采集');
                            loadGameList();
                        } else {
                            showStatus(r.data.message || '采集失败', false);
                        }
                    }.bind(this));
                });
                
                // 采集游戏礼包
                $('#collect_gifts').click(function() {
                    if (!confirm('确定要采集所有游戏的礼包吗？这可能需要较长时间。')) return;
                    
                    $(this).prop('disabled', true).text('采集中...');
                    $('#collect_result').empty();
                    
                    // 先获取所有游戏ID
                    $.post(ajaxurl, { action: 'yyk_st_publish', action_type: 'list', page: 1, nonce: '<?php echo wp_create_nonce("yyk_st_publish"); ?>' }, function(r) {
                        if (r.success && r.data.games && r.data.games.length > 0) {
                            var gameIds = r.data.games.map(function(g) { return g.game_id; });
                            var processed = 0;
                            var failed = 0;
                            var totalGifts = 0;
                            
                            function processNext(index) {
                                if (index >= gameIds.length) {
                                    $('#collect_gifts').prop('disabled', false).text('采集游戏礼包');
                                    showStatus('游戏礼包采集完成: 处理 ' + processed + ' 个游戏，共 ' + totalGifts + ' 个礼包', true);
                                    loadGameList();
                                    return;
                                }
                                
                                $.post(ajaxurl, { 
                                    action: 'yyk_st_collect', 
                                    type: 'gifts', 
                                    game_id: gameIds[index], 
                                    nonce: '<?php echo wp_create_nonce("yyk_st_collect"); ?>' 
                                }, function(r) {
                                    if (r.success) {
                                        processed++;
                                        totalGifts += (r.data.saved || 0);
                                    } else {
                                        failed++;
                                    }
                                    
                                    $('#collect_status').show().html('<span>处理进度: ' + (index + 1) + '/' + gameIds.length + '</span>');
                                    setTimeout(function() { processNext(index + 1); }, 500);
                                });
                            }
                            
                            processNext(0);
                        } else {
                            $('#collect_gifts').prop('disabled', false).text('采集游戏礼包');
                            showStatus('没有游戏数据，请先采集游戏列表', false);
                        }
                    });
                });
                
                // 一键采集
                $('#collect_all').click(function() {
                    $(this).prop('disabled', true).text('一键采集中...');
                    $('#collect_result').empty();
                    $.post(ajaxurl, { action: 'yyk_st_collect', type: 'all', nonce: '<?php echo wp_create_nonce("yyk_st_collect"); ?>' }, function(r) {
                        $(this).prop('disabled', false).text('一键采集全部');
                        if (r.success) {
                            var data = r.data;
                            var html = '';
                            if (data.categories) html += '<div class="collect-result-item success">📁 分类: ' + (data.categories.saved || 0) + ' 个</div>';
                            if (data.games) html += '<div class="collect-result-item success">🎮 游戏: 成功 ' + (data.games.saved || 0) + ' 个，失败 ' + (data.games.failed || 0) + ' 个，共 ' + (data.games.pages || 1) + ' 页</div>';
                            $('#collect_result').html(html);
                            showStatus('一键采集完成', true);
                            loadGameList();
                        } else {
                            showStatus(r.data.message || '采集失败', false);
                        }
                    }.bind(this));
                });
                
                // 修复数据库
                $('#fix_database').click(function() {
                    if (!confirm('确定要修复数据库表结构吗？')) return;
                    
                    var btn = $(this);
                    btn.prop('disabled', true).text('修复中...');
                    
                    $.post(ajaxurl, { action: 'yyk_st_fix_database', nonce: '<?php echo wp_create_nonce("yyk_st_fix_database"); ?>' }, function(r) {
                        btn.prop('disabled', false).text('🔧 修复数据库');
                        if (r.success) {
                            alert(r.data.message);
                            location.reload();
                        } else {
                            alert(r.data.message);
                        }
                    });
                });
                
                var currentPage = 1;
                
                function loadGameList(page) {
                    page = page || currentPage;
                    $('#game_list').html('<p style="text-align:center;padding:40px">加载中...</p>');
                    
                    console.log('YYK: 开始加载游戏列表，页码:', page);
                    
                    $.post(ajaxurl, { action: 'yyk_st_publish', action_type: 'list', page: page, nonce: '<?php echo wp_create_nonce("yyk_st_publish"); ?>' }, function(r) {
                        console.log('YYK: 收到响应', r);
                        
                        if (r.success) {
                            console.log('YYK: 游戏数据', r.data.games);
                            console.log('YYK: 总数', r.data.total);
                            renderGameList(r.data.games, r.data.total, page);
                        } else {
                            console.error('YYK: 加载失败', r.data.message);
                            $('#game_list').html('<p style="color:red;text-align:center;padding:40px">加载失败: ' + (r.data.message || '未知错误') + '</p>');
                        }
                    });
                }
                
                function renderGameList(games, total, page) {
                    if (!games || games.length === 0) {
                        $('#game_list').html('<p style="text-align:center;padding:40px">暂无游戏数据，请先采集</p>');
                        $('#game_list_stats').html('');
                        $('#game_pagination').html('');
                        return;
                    }
                    
                    var publishedCount = 0;
                    var unpublishedCount = 0;
                    var html = '<table class="wp-list-table widefat fixed striped table-view-list">';
                    html += '<thead><tr>';
                    html += '<th style="width:60px">ID</th>';
                    html += '<th style="width:80px">图标</th>';
                    html += '<th>游戏名称</th>';
                    html += '<th style="width:100px">大小</th>';
                    html += '<th style="width:150px">分类</th>';
                    html += '<th style="width:200px">下载地址</th>';
                    html += '<th style="width:80px">状态</th>';
                    html += '<th style="width:150px">操作</th>';
                    html += '</tr></thead>';
                    html += '<tbody>';
                    
                    games.forEach(function(game) {
                        try {
                            var data = JSON.parse(game.data);
                        } catch (e) {
                            console.error('YYK: 解析游戏数据失败', game, e);
                            return;
                        }
                        
                        var title = data.post_title || game.game_name || '未知游戏';
                        var icon = data.game_icon || (data.meta_fields && data.meta_fields._yyk_app_icon_url) || '';
                        var size = game.game_size || (data.meta_fields && data.meta_fields._yyk_app_size) || '-';
                        
                        // 获取所有分类
                        var category = game.category_name || '-';
                        if (data.meta_fields && data.meta_fields._yyk_st_all_categories && Array.isArray(data.meta_fields._yyk_st_all_categories)) {
                            category = data.meta_fields._yyk_st_all_categories.join('、');
                        }
                        
                        var downloadUrl = game.download_url || (data.meta_fields && data.meta_fields._yyk_app_download_url) || '-';
                        var published = game.post_id ? true : false;
                        
                        if (published) publishedCount++;
                        else unpublishedCount++;
                        
                        html += '<tr>';
                        html += '<td>' + game.game_id + '</td>';
                        html += '<td>' + (icon ? '<img src="' + icon + '" style="width:50px;height:50px;object-fit:cover;border-radius:4px">' : '-') + '</td>';
                        html += '<td><strong>' + escapeHtml(title) + '</strong>';
                        if (data.meta_fields && data.meta_fields._yyk_st_game_type) {
                            html += '<br><small style="color:#666">类型: ' + escapeHtml(data.meta_fields._yyk_st_game_type) + '</small>';
                        }
                        if (data.meta_fields && data.meta_fields._yyk_st_discount) {
                            html += '<br><small style="color:#e67e22">折扣: ' + data.meta_fields._yyk_st_discount + '折</small>';
                        }
                        html += '</td>';
                        html += '<td>' + escapeHtml(size) + '</td>';
                        html += '<td>' + escapeHtml(category) + '</td>';
                        html += '<td><a href="' + downloadUrl + '" target="_blank" style="color:#0073aa;text-decoration:none">下载</a></td>';
                        html += '<td><span class="yyk-game-status ' + (published ? 'published' : 'unpublished') + '">' + (published ? '已发布' : '未发布') + '</span></td>';
                        html += '<td>';
                        if (!published) {
                            html += '<button type="button" class="button button-primary publish-single" data-id="' + game.game_id + '" style="margin-right:5px">发布</button>';
                        } else {
                            html += '<a href="post.php?post=' + game.post_id + '&action=edit" class="button" target="_blank" style="margin-right:5px">编辑</a>';
                        }
                        html += '<button type="button" class="button delete-single" data-id="' + game.game_id + '" style="background:#dc3545;color:white">删除</button>';
                        html += '</td>';
                        html += '</tr>';
                    });
                    
                    html += '</tbody></table>';
                    
                    $('#game_list').html(html);
                    $('#game_list_stats').html('<strong>统计:</strong> 总计 ' + total + ' 个游戏，已发布 ' + publishedCount + ' 个，未发布 ' + unpublishedCount + ' 个');
                }
                
                function escapeHtml(str) {
                    if (!str) return '';
                    return String(str).replace(/[&<>]/g, function(m) {
                        if (m === '&') return '&amp;';
                        if (m === '<') return '&lt;';
                        if (m === '>') return '&gt;';
                        return m;
                    });
                }
                
                $(document).on('click', '.publish-single', function() {
                    var btn = $(this);
                    var gameId = btn.data('id');
                    
                    btn.prop('disabled', true).text('发布中...');
                    
                    $.post(ajaxurl, { 
                        action: 'yyk_st_publish', 
                        action_type: 'single', 
                        game_id: gameId, 
                        nonce: '<?php echo wp_create_nonce("yyk_st_publish"); ?>' 
                    }, function(r) {
                        if (r.success) {
                            loadGameList(currentPage);
                        } else {
                            alert(r.data.message);
                            btn.prop('disabled', false).text('发布');
                        }
                    });
                });
                
                $(document).on('click', '.delete-single', function() {
                    if (!confirm('确定要删除这个游戏吗？此操作不可恢复！')) return;
                    
                    var btn = $(this);
                    var gameId = btn.data('id');
                    
                    btn.prop('disabled', true).text('删除中...');
                    
                    $.post(ajaxurl, { 
                        action: 'yyk_st_delete_single', 
                        game_id: gameId, 
                        nonce: '<?php echo wp_create_nonce("yyk_st_delete_single"); ?>' 
                    }, function(r) {
                        if (r.success) {
                            loadGameList(currentPage);
                        } else {
                            alert(r.data.message);
                            btn.prop('disabled', false).text('删除');
                        }
                    });
                });
                
                $('#publish_all').click(function() {
                    if (!confirm('确定发布所有未发布的游戏吗？')) return;
                    var btn = $(this);
                    btn.text('发布中...').prop('disabled', true);
                    $.post(ajaxurl, { action: 'yyk_st_publish', action_type: 'all', nonce: '<?php echo wp_create_nonce("yyk_st_publish"); ?>' }, function(r) {
                        btn.text('发布所有未发布游戏').prop('disabled', false);
                        if (r.success) {
                            alert(r.data.message);
                            loadGameList(currentPage);
                        } else {
                            alert(r.data.message);
                        }
                    });
                });
                
                $('#delete_all').click(function() {
                    if (!confirm('⚠️ 警告：这将删除所有采集的游戏数据以及已发布的文章！此操作不可恢复！确定要继续吗？')) return;
                    var btn = $(this);
                    btn.text('删除中...').prop('disabled', true);
                    $.post(ajaxurl, { action: 'yyk_st_delete_all', nonce: '<?php echo wp_create_nonce("yyk_st_delete"); ?>' }, function(r) {
                        btn.text('删除所有游戏').prop('disabled', false);
                        if (r.success) {
                            alert(r.data.message);
                            loadGameList(1);
                        } else {
                            alert(r.data.message);
                        }
                    });
                });
                
                loadGameList(1);
            });
            </script>
            <?php
        }
    }
}