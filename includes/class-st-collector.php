<?php
/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：ST采集模块
 =  📄 文件：class-st-collector.php
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：ST手游接口采集管理器，负责从SteamSy接口采集游戏数据并管理
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

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
            $api_source = get_option('yyk_st_api_source');
            if ($api_source === false) {
                $api_source = 'steamsy';
            }
            if ($api_source === 'hehesy') {
                $this->api_domain = 'http://box.hehesy.com';
            } else {
                $this->api_domain = 'https://www.steamsy.com';
            }
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
            update_option('yyk_st_api_source', 'steamsy');
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
                'created_at' => "ALTER TABLE $table_name ADD COLUMN created_at datetime DEFAULT CURRENT_TIMESTAMP",
                'pic2' => "ALTER TABLE $table_name ADD COLUMN pic2 varchar(500) DEFAULT NULL",
                'device_type' => "ALTER TABLE $table_name ADD COLUMN device_type tinyint(1) DEFAULT NULL",
                'typeword' => "ALTER TABLE $table_name ADD COLUMN typeword varchar(255) DEFAULT NULL",
                'discount' => "ALTER TABLE $table_name ADD COLUMN discount decimal(5,2) DEFAULT NULL",
                'updatetime' => "ALTER TABLE $table_name ADD COLUMN updatetime varchar(50) DEFAULT NULL",
                'excerpt' => "ALTER TABLE $table_name ADD COLUMN excerpt text DEFAULT NULL",
                'fanli' => "ALTER TABLE $table_name ADD COLUMN fanli text DEFAULT NULL",
                'vip' => "ALTER TABLE $table_name ADD COLUMN vip text DEFAULT NULL",
                'welfare' => "ALTER TABLE $table_name ADD COLUMN welfare text DEFAULT NULL",
                'apkname' => "ALTER TABLE $table_name ADD COLUMN apkname varchar(255) DEFAULT NULL",
                'ios_apkname' => "ALTER TABLE $table_name ADD COLUMN ios_apkname varchar(255) DEFAULT NULL",
                'downloadnum' => "ALTER TABLE $table_name ADD COLUMN downloadnum int(11) DEFAULT NULL",
                'edition' => "ALTER TABLE $table_name ADD COLUMN edition varchar(50) DEFAULT NULL",
                'game_tag' => "ALTER TABLE $table_name ADD COLUMN game_tag varchar(255) DEFAULT NULL",
                'gametype' => "ALTER TABLE $table_name ADD COLUMN gametype varchar(255) DEFAULT NULL",
                'gametype1' => "ALTER TABLE $table_name ADD COLUMN gametype1 int(11) DEFAULT NULL",
                'gametype2' => "ALTER TABLE $table_name ADD COLUMN gametype2 int(11) DEFAULT NULL",
                'photo1' => "ALTER TABLE $table_name ADD COLUMN photo1 varchar(500) DEFAULT NULL",
                'video' => "ALTER TABLE $table_name ADD COLUMN video varchar(500) DEFAULT NULL",
                'game_bbs' => "ALTER TABLE $table_name ADD COLUMN game_bbs varchar(500) DEFAULT NULL",
                'gamenotice' => "ALTER TABLE $table_name ADD COLUMN gamenotice text DEFAULT NULL",
                'detail_synced' => "ALTER TABLE $table_name ADD COLUMN detail_synced tinyint(1) DEFAULT 0"
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
            $this->init_settings();
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
            $api_source = get_option('yyk_st_api_source');
            if ($api_source === false) {
                $api_source = 'steamsy';
            }
            if ($api_source === 'hehesy') {
                return $url;
            } else {
                $url = str_replace('qudao.guazisy.com', 'qudao.steamsy.com', $url);
                return $url;
            }
        }
        
        // 从接口数据提取字段 - 兼容不同接口的字段格式
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
            
            // 游戏ID - 兼容 id 或 game_id
            $game_id = $item['id'] ?? $item['game_id'] ?? '';
            
            $this->log('提取游戏数据 - game_id: ' . $game_id . ', 原始数据: ' . json_encode($item, JSON_UNESCAPED_UNICODE));
            
            // 游戏名称 - 兼容 gamename 或 name
            $game_name = '';
            if (isset($item['gamename']) && !empty($item['gamename'])) {
                $game_name = trim($item['gamename']);
            } elseif (isset($item['name']) && !empty($item['name'])) {
                $game_name = trim($item['name']);
            }
            if (!empty($game_name)) {
                $post_data['post_title'] = $game_name;
                $post_data['game_name'] = $game_name;
            }
            
            // 图标 - 兼容 pic1 或 icon
            $icon_url = '';
            if (isset($item['pic1']) && !empty($item['pic1'])) {
                $icon_url = $this->fix_url($item['pic1']);
            } elseif (isset($item['icon']) && !empty($item['icon'])) {
                $icon_url = $this->fix_url($item['icon']);
            }
            if (!empty($icon_url)) {
                $post_data['game_icon'] = $icon_url;
                $post_data['meta_fields']['_yyk_app_icon_url'] = $icon_url;
            }
            
            // 下载地址 - 兼容 url 或 Url 或 download_url
            $download_url = '';
            if (isset($item['download_url']) && !empty($item['download_url'])) {
                $download_url = $this->fix_url($item['download_url']);
            } elseif (isset($item['url']) && !empty($item['url'])) {
                $download_url = $this->fix_url($item['url']);
            } elseif (isset($item['Url']) && !empty($item['Url'])) {
                $download_url = $this->fix_url($item['Url']);
            }
            if (!empty($download_url)) {
                $post_data['download_url'] = $download_url;
                $post_data['meta_fields']['_yyk_app_download_url'] = $download_url;
                $post_data['meta_fields']['_yyk_app_android_url'] = $download_url;
                $post_data['meta_fields']['_yyk_app_ios_url'] = $download_url;
            }
            
            // 游戏大小 - 兼容 gamesize 或 size
            $game_size = '';
            if (isset($item['gamesize']) && !empty($item['gamesize'])) {
                $game_size = $item['gamesize'];
            } elseif (isset($item['size']) && !empty($item['size'])) {
                $game_size = $item['size'];
            }
            if (!empty($game_size)) {
                $post_data['game_size'] = $game_size;
                $post_data['meta_fields']['_yyk_app_size'] = $game_size;
            }
            
            // 平台类型 (接口返回 device_type)
            if (isset($item['device_type'])) {
                $device_type = intval($item['device_type']);
                $platform = 'all';
                if ($device_type == 0) $platform = 'android';
                if ($device_type == 1) $platform = 'ios';
                if ($device_type == 2) $platform = 'all';
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
            
            // 备用图 (接口返回 pic2)
            if (isset($item['pic2']) && !empty($item['pic2'])) {
                $pic2 = $this->fix_url($item['pic2']);
                $post_data['meta_fields']['_yyk_st_pic2'] = $pic2;
            }
            
            // 平台类型 (接口返回 device_type) - 已存在，再兼容一下
            if (isset($item['device_type'])) {
                $device_type = intval($item['device_type']);
                $platform = 'all';
                if ($device_type == 0) $platform = 'android';
                if ($device_type == 1) $platform = 'ios';
                if ($device_type == 2) $platform = 'all';
                $post_data['meta_fields']['_yyk_app_platform'] = $platform;
            }
            
            // 游戏类型 (接口返回 typeword) - 已存在，再保存一下原始值
            if (isset($item['typeword']) && !empty($item['typeword'])) {
                $post_data['meta_fields']['_yyk_st_typeword'] = $item['typeword'];
            }
            
            // 折扣 (接口返回 discount) - 已存在
            if (isset($item['discount']) && !empty($item['discount'])) {
                $post_data['meta_fields']['_yyk_st_discount'] = floatval($item['discount']);
            }
            
            // 更新时间 (接口返回 updatetime) - 已存在
            if (isset($item['updatetime']) && !empty($item['updatetime'])) {
                $post_data['meta_fields']['_yyk_app_update_date'] = $item['updatetime'];
            }
            
            // 福利简介 (接口返回 excerpt) - 同时存入 post_excerpt
            if (isset($item['excerpt']) && !empty($item['excerpt'])) {
                $post_data['post_excerpt'] = $item['excerpt'];
                $post_data['meta_fields']['_yyk_st_excerpt'] = $item['excerpt'];
            }
            
            // 返利介绍 (接口返回 fanli) - 已存在
            if (isset($item['fanli']) && !empty($item['fanli'])) {
                $post_data['meta_fields']['_yyk_st_fanli'] = $item['fanli'];
            }
            
            // VIP介绍 (接口返回 Vip/vip - 兼容大小写)
            $vip_value = '';
            if (isset($item['Vip']) && !empty($item['Vip'])) {
                $vip_value = $item['Vip'];
            } elseif (isset($item['vip']) && !empty($item['vip'])) {
                $vip_value = $item['vip'];
            }
            if (!empty($vip_value)) {
                $post_data['meta_fields']['_yyk_st_vip_intro'] = $vip_value;
            }
            
            // 一句话简介 (接口返回 Welfare/welfare - 兼容大小写)
            $welfare_value = '';
            if (isset($item['Welfare']) && !empty($item['Welfare'])) {
                $welfare_value = $item['Welfare'];
            } elseif (isset($item['welfare']) && !empty($item['welfare'])) {
                $welfare_value = $item['welfare'];
            }
            if (!empty($welfare_value)) {
                $post_data['meta_fields']['_yyk_st_short_intro'] = $welfare_value;
            }
            
            // 安卓包名 (接口返回 Apkname/apkname - 兼容大小写)
            $apkname_value = '';
            if (isset($item['Apkname']) && !empty($item['Apkname'])) {
                $apkname_value = $item['Apkname'];
            } elseif (isset($item['apkname']) && !empty($item['apkname'])) {
                $apkname_value = $item['apkname'];
            }
            if (!empty($apkname_value)) {
                $post_data['meta_fields']['_yyk_st_apkname'] = $apkname_value;
            }
            
            // iOS包名 (接口返回 Ios_apkname/ios_apkname - 兼容大小写)
            $ios_apkname_value = '';
            if (isset($item['Ios_apkname']) && !empty($item['Ios_apkname'])) {
                $ios_apkname_value = $item['Ios_apkname'];
            } elseif (isset($item['ios_apkname']) && !empty($item['ios_apkname'])) {
                $ios_apkname_value = $item['ios_apkname'];
            }
            if (!empty($ios_apkname_value)) {
                $post_data['meta_fields']['_yyk_st_ios_apkname'] = $ios_apkname_value;
            }
            
            // 版本类型 (接口返回 edition) - 已存在
            if (isset($item['edition']) && !empty($item['edition'])) {
                $post_data['meta_fields']['_yyk_app_version'] = $item['edition'];
            }
            
            // 游戏标签 (接口返回 game_tag)
            if (isset($item['game_tag']) && !empty($item['game_tag'])) {
                $post_data['meta_fields']['_yyk_st_game_tag'] = $item['game_tag'];
            }
            
            // 类型标签 (接口返回 gametype)
            if (isset($item['gametype']) && !empty($item['gametype'])) {
                $post_data['meta_fields']['_yyk_st_gametype'] = $item['gametype'];
            }
            
            // 分类ID1 (接口返回 gametype1)
            if (isset($item['gametype1']) && !empty($item['gametype1'])) {
                $post_data['meta_fields']['_yyk_st_gametype1'] = $item['gametype1'];
            }
            
            // 分类ID2 (接口返回 gametype2)
            if (isset($item['gametype2']) && !empty($item['gametype2'])) {
                $post_data['meta_fields']['_yyk_st_gametype2'] = $item['gametype2'];
            }
            
            // 首图 (接口返回 photo1)
            if (isset($item['photo1']) && !empty($item['photo1'])) {
                $photo1 = $this->fix_url($item['photo1']);
                $post_data['meta_fields']['_yyk_st_photo1'] = $photo1;
            }
            
            return $post_data;
        }
        
        private function save_game($game_id, $post_data) {
            $table_name = YYK_ST_TABLE_GAMES;
            
            $this->log('开始保存游戏 - game_id: ' . $game_id);
            $this->log('准备保存的数据 - game_name: ' . ($post_data['game_name'] ?? '空') . ', game_icon: ' . ($post_data['game_icon'] ?? '空'));
            
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
                'category_name' => $post_data['category_name'] ?? '',
                'pic2' => $post_data['meta_fields']['_yyk_st_pic2'] ?? null,
                'device_type' => isset($post_data['meta_fields']['_yyk_app_platform']) ? 
                    ($post_data['meta_fields']['_yyk_app_platform'] == 'android' ? 0 : 
                    ($post_data['meta_fields']['_yyk_app_platform'] == 'ios' ? 1 : 2)) : null,
                'typeword' => $post_data['meta_fields']['_yyk_st_typeword'] ?? null,
                'discount' => $post_data['meta_fields']['_yyk_st_discount'] ?? null,
                'updatetime' => $post_data['meta_fields']['_yyk_app_update_date'] ?? null,
                'excerpt' => $post_data['meta_fields']['_yyk_st_excerpt'] ?? null,
                'fanli' => $post_data['meta_fields']['_yyk_st_fanli'] ?? null,
                'vip' => $post_data['meta_fields']['_yyk_st_vip_intro'] ?? null,
                'welfare' => $post_data['meta_fields']['_yyk_st_short_intro'] ?? null,
                'apkname' => $post_data['meta_fields']['_yyk_st_apkname'] ?? null,
                'ios_apkname' => $post_data['meta_fields']['_yyk_st_ios_apkname'] ?? null,
                'downloadnum' => $post_data['meta_fields']['_yyk_app_download_count'] ?? null,
                'edition' => $post_data['meta_fields']['_yyk_app_version'] ?? null,
                'game_tag' => $post_data['meta_fields']['_yyk_st_game_tag'] ?? null,
                'gametype' => $post_data['meta_fields']['_yyk_st_gametype'] ?? null,
                'gametype1' => $post_data['meta_fields']['_yyk_st_gametype1'] ?? null,
                'gametype2' => $post_data['meta_fields']['_yyk_st_gametype2'] ?? null,
                'photo1' => $post_data['meta_fields']['_yyk_st_photo1'] ?? null
            ];
            
            $this->log('准备插入的数据 - ' . json_encode($insert_data, JSON_UNESCAPED_UNICODE));
            
            if ($exists) {
                $this->log('游戏已存在，跳过 - game_id: ' . $game_id);
                return 'exists';
            } else {
                $this->log('插入游戏 - game_id: ' . $game_id);
                $result = $this->db->insert($table_name, $insert_data);
                if ($result === false) {
                    $this->log('插入失败 - 错误: ' . $this->db->last_error);
                } else {
                    $this->log('插入成功 - 插入ID: ' . $this->db->insert_id);
                }
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
            
            $api_source = get_option('yyk_st_api_source');
            if ($api_source === false) {
                $api_source = 'steamsy';
            }
            $this->log('当前数据源: ' . $api_source);
            
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
            
            // 兼容不同接口的返回格式，先检查 lists，再检查 list
            $game_list = [];
            if (!empty($data['lists']) && is_array($data['lists'])) {
                $game_list = $data['lists'];
            } elseif (!empty($data['list']) && is_array($data['list'])) {
                $game_list = $data['list'];
            }
            
            // 检查是否有数据
            if (empty($game_list)) {
                $this->log('没有游戏数据');
                return ['success' => true, 'saved' => 0, 'failed' => 0, 'total' => 0, 'message' => '没有游戏数据'];
            }
            
            $this->log('找到 ' . count($game_list) . ' 个游戏');
            
            foreach ($game_list as $item) {
                try {
                    $this->log('处理游戏项 - 原始数据: ' . json_encode($item, JSON_UNESCAPED_UNICODE));
                    
                    // 兼容不同接口的id字段
                    $game_id = $item['id'] ?? $item['game_id'] ?? '';
                    if (empty($game_id)) {
                        $this->log('游戏ID为空，跳过');
                        $failed++;
                        continue;
                    }
                    $this->log('游戏ID: ' . $game_id);
                    
                    $post_data = $this->extract_post_data($item);
                    $this->log('提取后的数据 - post_title: ' . ($post_data['post_title'] ?? '空') . ', game_icon: ' . ($post_data['game_icon'] ?? '空'));
                    
                    $result = $this->save_game($game_id, $post_data);
                    
                    if ($result === true) {
                        $saved++;
                        $this->log('保存成功 - game_id: ' . $game_id);
                    } elseif ($result === 'exists') {
                        $skipped++;
                        $this->log('游戏已存在，跳过 - game_id: ' . $game_id);
                    } else {
                        $failed++;
                        $this->log('保存失败 - game_id: ' . $game_id);
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
        
        // 采集游戏列表（单次采集20个）
        public function fetch_games_all($page = 1) {
            $limit = 20;
            
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
        
        // 采集游戏详情（包含photo五宣图）- 优先V3，V3没有的读V1
        public function fetch_game_detail($game_id) {
            $this->log('开始采集游戏详情 - game_id: ' . $game_id);
            
            $game_detail = [];
            $v1_detail = [];
            
            // 先调用V3接口
            $result_v3 = $this->api_get('/v3/', [
                'gid' => $game_id
            ]);
            
            if ($result_v3['success'] && !empty($result_v3['data']['c'])) {
                $game_detail = $result_v3['data']['c'];
                $this->log('获取到V3详情数据');
            }
            
            // 检查是否需要调用V1接口补充数据
            $need_v1 = false;
            
            // 如果V3没有photo或video，调用V1
            if (empty($game_detail['photo']) || empty($game_detail['video'])) {
                $need_v1 = true;
                $this->log('V3缺少photo或video，调用V1补充');
            }
            
            if ($need_v1) {
                $result_v1 = $this->api_get('/v1/', [
                    'gid' => $game_id
                ]);
                
                if ($result_v1['success'] && !empty($result_v1['data']['c'])) {
                    $v1_detail = $result_v1['data']['c'];
                    $this->log('获取到V1详情数据');
                    
                    // 合并数据 - 优先使用V3的数据，V3没有的用V1补充
                    if (empty($game_detail['photo']) && !empty($v1_detail['photo'])) {
                        $game_detail['photo'] = $v1_detail['photo'];
                        $this->log('使用V1补充photo字段');
                    }
                    if (empty($game_detail['video']) && !empty($v1_detail['video'])) {
                        $game_detail['video'] = $v1_detail['video'];
                        $this->log('使用V1补充video字段');
                    }
                    if (empty($game_detail['game_bbs']) && !empty($v1_detail['game_bbs'])) {
                        $game_detail['game_bbs'] = $v1_detail['game_bbs'];
                        $this->log('使用V1补充game_bbs字段');
                    }
                    if (empty($game_detail['gamenotice']) && !empty($v1_detail['gamenotice'])) {
                        $game_detail['gamenotice'] = $v1_detail['gamenotice'];
                        $this->log('使用V1补充gamenotice字段');
                    }
                    if (empty($game_detail['box_content']) && !empty($v1_detail['box_content'])) {
                        $game_detail['box_content'] = $v1_detail['box_content'];
                        $this->log('使用V1补充box_content字段');
                    }
                    if (empty($game_detail['download_url']) && !empty($v1_detail['download_url'])) {
                        $game_detail['download_url'] = $v1_detail['download_url'];
                        $this->log('使用V1补充download_url字段');
                    }
                    if (empty($game_detail['url']) && !empty($v1_detail['url'])) {
                        $game_detail['url'] = $v1_detail['url'];
                        $this->log('使用V1补充url字段');
                    }
                    if (empty($game_detail['downloadnum']) && !empty($v1_detail['downloadnum'])) {
                        $game_detail['downloadnum'] = $v1_detail['downloadnum'];
                        $this->log('使用V1补充downloadnum字段');
                    }
                    if (empty($game_detail['gamesize']) && !empty($v1_detail['gamesize'])) {
                        $game_detail['gamesize'] = $v1_detail['gamesize'];
                        $this->log('使用V1补充gamesize字段');
                    }
                    if (empty($game_detail['edition']) && !empty($v1_detail['edition'])) {
                        $game_detail['edition'] = $v1_detail['edition'];
                        $this->log('使用V1补充edition字段');
                    }
                    if (empty($game_detail['updatetime']) && !empty($v1_detail['updatetime'])) {
                        $game_detail['updatetime'] = $v1_detail['updatetime'];
                        $this->log('使用V1补充updatetime字段');
                    }
                    if (empty($game_detail['excerpt']) && !empty($v1_detail['excerpt'])) {
                        $game_detail['excerpt'] = $v1_detail['excerpt'];
                        $this->log('使用V1补充excerpt字段');
                    }
                    if (empty($game_detail['fanli']) && !empty($v1_detail['fanli'])) {
                        $game_detail['fanli'] = $v1_detail['fanli'];
                        $this->log('使用V1补充fanli字段');
                    }
                    if (empty($game_detail['vip']) && !empty($v1_detail['vip'])) {
                        $game_detail['vip'] = $v1_detail['vip'];
                        $this->log('使用V1补充vip字段');
                    }
                    if (empty($game_detail['welfare']) && !empty($v1_detail['welfare'])) {
                        $game_detail['welfare'] = $v1_detail['welfare'];
                        $this->log('使用V1补充welfare字段');
                    }
                    if (empty($game_detail['device_type']) && !empty($v1_detail['device_type'])) {
                        $game_detail['device_type'] = $v1_detail['device_type'];
                        $this->log('使用V1补充device_type字段');
                    }
                    if (empty($game_detail['discount']) && !empty($v1_detail['discount'])) {
                        $game_detail['discount'] = $v1_detail['discount'];
                        $this->log('使用V1补充discount字段');
                    }
                }
            }
            
            if (empty($game_detail)) {
                $this->log('游戏详情数据为空（V3和V1都没有数据）');
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
                    "SELECT data, post_id FROM $table_name WHERE game_id = %s",
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
                    
                    // 更新其他字段（不使用extract_post_data，直接处理v3字段）
                    $meta_fields = &$post_data['meta_fields'];
                    
                    // 更新下载地址
                    $api_source = get_option('yyk_st_api_source');
                    if ($api_source === false) {
                        $api_source = 'steamsy';
                    }
                    
                    $download_url = '';
                    
                    // 先尝试从接口获取原始下载地址
                    if (isset($game_detail['download_url']) && !empty($game_detail['download_url'])) {
                        $download_url = $this->fix_url($game_detail['download_url']);
                        $this->log('使用接口返回的下载地址 - url: ' . $download_url);
                    } elseif (isset($game_detail['url']) && !empty($game_detail['url'])) {
                        $download_url = $this->fix_url($game_detail['url']);
                        $this->log('使用接口返回的url地址 - url: ' . $download_url);
                    }
                    
                    // 如果是 ST手游 并且接口没有返回下载地址，再用 game_id 构造
                    if (empty($download_url) && $api_source !== 'hehesy') {
                        $cps_id = get_option('yyk_st_cps_id', '15907108869');
                        $download_url = 'https://qudao.steamsy.com/down.html?ag=' . $cps_id . '&gid=' . $game_id;
                        $this->log('用game_id构造ST手游下载地址 - url: ' . $download_url);
                    }
                    
                    // 如果是梨子手游并且没有获取到地址，尝试用接口返回的url构造
                    if (empty($download_url) && $api_source === 'hehesy') {
                        $this->log('梨子手游没有获取到下载地址');
                    }
                    
                    if (!empty($download_url)) {
                        $meta_fields['_yyk_app_download_url'] = $download_url;
                        $meta_fields['_yyk_app_android_url'] = $download_url;
                        $meta_fields['_yyk_app_ios_url'] = $download_url;
                    }
                    
                    // 更新一句话简介
                    if (isset($game_detail['welfare']) && !empty($game_detail['welfare'])) {
                        $meta_fields['_yyk_st_short_intro'] = $game_detail['welfare'];
                    }
                    
                    // 更新下载次数
                    if (isset($game_detail['downloadnum']) && !empty($game_detail['downloadnum'])) {
                        $meta_fields['_yyk_app_download_count'] = $game_detail['downloadnum'];
                    }
                    
                    // 更新折扣
                    if (isset($game_detail['discount']) && !empty($game_detail['discount'])) {
                        $meta_fields['_yyk_st_discount'] = $game_detail['discount'];
                    }
                    
                    // 更新返利介绍
                    if (isset($game_detail['fanli']) && !empty($game_detail['fanli'])) {
                        $meta_fields['_yyk_st_fanli'] = $game_detail['fanli'];
                    }
                    
                    // 更新VIP介绍
                    if (isset($game_detail['vip']) && !empty($game_detail['vip'])) {
                        $meta_fields['_yyk_st_vip_intro'] = $game_detail['vip'];
                    }
                    
                    // 更新平台类型
                    if (isset($game_detail['device_type'])) {
                        $device_type = intval($game_detail['device_type']);
                        $platform = 'all';
                        if ($device_type == 0) $platform = 'android';
                        if ($device_type == 1) $platform = 'ios';
                        if ($device_type == 2) $platform = 'all';
                        $meta_fields['_yyk_app_platform'] = $platform;
                    }
                    
                    // 更新游戏大小
                    if (isset($game_detail['gamesize']) && !empty($game_detail['gamesize'])) {
                        $meta_fields['_yyk_app_size'] = $game_detail['gamesize'];
                    }
                    
                    // 视频地址 (接口返回 video)
                    if (isset($game_detail['video']) && !empty($game_detail['video'])) {
                        $video = $this->fix_url($game_detail['video']);
                        $meta_fields['_yyk_st_video'] = $video;
                    }
                    
                    // 备用视频地址 (接口返回 game_bbs)
                    if (isset($game_detail['game_bbs']) && !empty($game_detail['game_bbs'])) {
                        $game_bbs = $this->fix_url($game_detail['game_bbs']);
                        $meta_fields['_yyk_st_game_bbs'] = $game_bbs;
                    }
                    
                    // 游戏公告 (接口返回 gamenotice)
                    if (isset($game_detail['gamenotice']) && !empty($game_detail['gamenotice'])) {
                        $meta_fields['_yyk_st_gamenotice'] = $game_detail['gamenotice'];
                    }
                    
                    // 标记详情已同步
                    $meta_fields['_yyk_st_detail_synced'] = true;
                    
                    // 保存回 post_data
                    $post_data['meta_fields'] = $meta_fields;
                    
                    // 更新数据库
                    $data_json = json_encode($post_data, JSON_UNESCAPED_UNICODE);
                    
                    // 准备数据库更新数据
                    $update_data = ['data' => $data_json];
                    
                    // 同时更新数据库的 download_url 字段
                    if (!empty($download_url)) {
                        $update_data['download_url'] = $download_url;
                    }
                    
                    // 更新 video、game_bbs、gamenotice、detail_synced 字段
                    if (isset($game_detail['video']) && !empty($game_detail['video'])) {
                        $update_data['video'] = $this->fix_url($game_detail['video']);
                    }
                    if (isset($game_detail['game_bbs']) && !empty($game_detail['game_bbs'])) {
                        $update_data['game_bbs'] = $this->fix_url($game_detail['game_bbs']);
                    }
                    if (isset($game_detail['gamenotice']) && !empty($game_detail['gamenotice'])) {
                        $update_data['gamenotice'] = $game_detail['gamenotice'];
                    }
                    $update_data['detail_synced'] = 1;
                    
                    // 更新数据库
                    $this->db->update(
                        $table_name,
                        $update_data,
                        ['game_id' => $game_id]
                    );
                    
                    // 如果游戏已发布，同时更新 WordPress 文章
                    if (!empty($row->post_id)) {
                        $post_id = $row->post_id;
                        
                        $this->log('更新已发布文章 - post_id: ' . $post_id);
                        
                        // 更新所有自定义字段
                        foreach ($meta_fields as $key => $value) {
                            if (!empty($value)) {
                                update_post_meta($post_id, $key, $value);
                                $this->log('更新字段 - ' . $key . ': ' . (is_array($value) ? json_encode($value) : $value));
                            }
                        }
                    }
                    
                    return ['success' => true, 'message' => '游戏详情更新成功'];
                }
            }
            
            return ['success' => false, 'error' => '游戏不存在'];
        }
        
        // 采集预约游戏列表
        public function fetch_reserve_games($page = 1, $limit = 20) {
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
        public function fetch_ranking_games($toptype = 0, $diynum = 1, $totalnum = 20) {
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
            
            // 处理每个礼包，确保编码正确并添加content字段
            foreach ($data['lists'] as &$gift) {
                if (isset($gift['name'])) {
                    $gift['name'] = mb_convert_encoding($gift['name'], 'UTF-8', 'UTF-8');
                }
                if (isset($gift['card'])) {
                    $gift['card'] = mb_convert_encoding($gift['card'], 'UTF-8', 'UTF-8');
                }
                if (isset($gift['content'])) {
                    $gift['content'] = mb_convert_encoding($gift['content'], 'UTF-8', 'UTF-8');
                }
                
                // 字段映射，添加content字段（从excerpt获取）
                $processed_gift = [
                    'gift_id' => $gift['id'] ?? '',
                    'name' => $gift['name'] ?? '',
                    'start_time' => $gift['start_time'] ?? '',
                    'end_time' => $gift['end_time'] ?? '',
                    'content' => $gift['excerpt'] ?? ($gift['content'] ?? ''),
                    'code' => $gift['card'] ?? '',
                    'remain' => $gift['part_num'] ?? ''
                ];
                
                $gift = $processed_gift;
            }
            unset($gift);
            
            $this->log('处理后的礼包数据 - ' . json_encode($data['lists'], JSON_UNESCAPED_UNICODE));
            
            // 保存礼包数据到游戏meta
            $table_name = YYK_ST_TABLE_GAMES;
            $exists = $this->db->get_var($this->db->prepare(
                "SELECT id FROM $table_name WHERE game_id = %s",
                $game_id
            ));
            
            if ($exists) {
                // 获取现有数据
                $row = $this->db->get_row($this->db->prepare(
                    "SELECT * FROM $table_name WHERE game_id = %s",
                    $game_id
                ));
                
                if ($row) {
                    $post_data = json_decode($row->data, true);
                    
                    // 保存礼包数据
                    $post_data['meta_fields']['_yyk_st_gifts'] = json_encode($data['lists'], JSON_UNESCAPED_UNICODE);
                    $saved = count($data['lists']);
                    
                    // 更新数据库
                    $data_json = json_encode($post_data, JSON_UNESCAPED_UNICODE);
                    $this->db->update(
                        $table_name,
                        ['data' => $data_json],
                        ['game_id' => $game_id]
                    );
                    
                    $this->log('更新游戏礼包 - game_id: ' . $game_id . ', 礼包数: ' . $saved);
                    
                    // 如果游戏已发布，同时更新 WordPress 文章
                    if (!empty($row->post_id)) {
                        $post_id = $row->post_id;
                        update_post_meta($post_id, '_yyk_st_gifts', json_encode($data['lists'], JSON_UNESCAPED_UNICODE));
                        $this->log('更新已发布文章的礼包 - post_id: ' . $post_id);
                    }
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
            
            // 构建内容（只保留游戏介绍，返利和VIP在详情页单独显示）
            $content = $post_data['post_content'] ?? '';
            
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
                "SELECT * FROM $table_name WHERE post_id IS NULL OR post_id = 0 LIMIT 20"
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
                $result = $this->get_all_games($page, 20);
                
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
                '游戏采集',
                '游戏采集',
                'manage_options',
                'yyk-st-collector',
                [$this, 'render_admin_page']
            );
        }
        
        public function render_admin_page() {
            if (isset($_POST['save_settings'])) {
                check_admin_referer('yyk_st_settings');
                update_option('yyk_st_api_source', sanitize_text_field($_POST['api_source']));
                update_option('yyk_st_cps_id', sanitize_text_field($_POST['cps_id']));
            }
            
            $api_source = get_option('yyk_st_api_source', 'steamsy');
            $cps_id = get_option('yyk_st_cps_id', '15907108869');
            
            $table_name = YYK_ST_TABLE_GAMES;
            $table_exists = $this->db->get_var("SHOW TABLES LIKE '$table_name'");
            $game_count = $table_exists ? $this->db->get_var("SELECT COUNT(*) FROM $table_name") : 0;
            $published_count = $table_exists ? $this->db->get_var("SELECT COUNT(*) FROM $table_name WHERE post_id IS NOT NULL AND post_id > 0") : 0;
            $unpublished_count = $game_count - $published_count;
            
            ?>
            <div class="wrap yyk-st-collector-page">
                <h1 class="yyk-page-title">
                    <span class="dashicons dashicons-download"></span>
                    游戏采集
                </h1>
                
                <!-- 统计卡片 -->
                <div class="yyk-st-cards">
                    <div class="yyk-st-card">
                        <div class="yyk-st-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <span class="dashicons dashicons-games"></span>
                        </div>
                        <div class="yyk-st-card-info">
                            <div class="yyk-st-card-value"><?php echo number_format($game_count); ?></div>
                            <div class="yyk-st-card-label">已采集游戏</div>
                        </div>
                    </div>
                    
                    <div class="yyk-st-card">
                        <div class="yyk-st-card-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                            <span class="dashicons dashicons-yes"></span>
                        </div>
                        <div class="yyk-st-card-info">
                            <div class="yyk-st-card-value"><?php echo number_format($published_count); ?></div>
                            <div class="yyk-st-card-label">已发布</div>
                        </div>
                    </div>
                    
                    <div class="yyk-st-card">
                        <div class="yyk-st-card-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <span class="dashicons dashicons-clock"></span>
                        </div>
                        <div class="yyk-st-card-info">
                            <div class="yyk-st-card-value"><?php echo number_format($unpublished_count); ?></div>
                            <div class="yyk-st-card-label">待发布</div>
                        </div>
                    </div>
                </div>
                
                <!-- 数据库状态 -->
                <div class="yyk-st-alert yyk-st-alert-info">
                    <div class="yyk-st-alert-icon">ℹ️</div>
                    <div class="yyk-st-alert-content">
                        <strong>数据库状态:</strong> 
                        表名: <code><?php echo esc_html($table_name); ?></code> | 
                        表状态: <?php echo $table_exists ? '✅ 存在' : '❌ 不存在'; ?>
                        <button type="button" class="yyk-st-btn yyk-st-btn-secondary" id="fix_database" style="margin-left: 15px;">
                            <span class="dashicons dashicons-admin-tools"></span>
                            修复数据库
                        </button>
                    </div>
                </div>
                
                <!-- 选项卡导航 -->
                <div class="yyk-st-tabs">
                    <div class="yyk-st-tab yyk-st-tab-active" data-tab="tutorial">
                        <span class="dashicons dashicons-book"></span>
                        采集教程
                    </div>
                    <div class="yyk-st-tab" data-tab="settings">
                        <span class="dashicons dashicons-admin-settings"></span>
                        设置
                    </div>
                    <div class="yyk-st-tab" data-tab="collect">
                        <span class="dashicons dashicons-download"></span>
                        采集
                    </div>
                    <div class="yyk-st-tab" data-tab="publish">
                        <span class="dashicons dashicons-upload"></span>
                        发布管理
                    </div>
                </div>
                
                <!-- 采集教程选项卡 -->
                <div id="tutorial" class="yyk-st-tab-content yyk-st-tab-content-active">
                    <div class="yyk-st-panel">
                        <div class="yyk-st-panel-header">
                            <h2>快速开始</h2>
                        </div>
                        <div class="yyk-st-panel-body">
                            <div class="yyk-st-tutorial-steps">
                                <div class="yyk-st-tutorial-step">
                                    <div class="yyk-st-tutorial-step-number">1</div>
                                    <div class="yyk-st-tutorial-step-content">
                                        <h3>配置API</h3>
                                        <p>前往<span class="yyk-st-highlight">设置</span>选项卡，选择数据源（ST手游或梨子手游），并填写您的渠道ID。</p>
                                    </div>
                                </div>
                                
                                <div class="yyk-st-tutorial-step">
                                    <div class="yyk-st-tutorial-step-number">2</div>
                                    <div class="yyk-st-tutorial-step-content">
                                        <h3>同步分类</h3>
                                        <p>点击<span class="yyk-st-highlight">同步分类</span>按钮，将游戏分类从API同步到本地。</p>
                                    </div>
                                </div>
                                
                                <div class="yyk-st-tutorial-step">
                                    <div class="yyk-st-tutorial-step-number">3</div>
                                    <div class="yyk-st-tutorial-step-content">
                                        <h3>采集游戏</h3>
                                        <p>点击<span class="yyk-st-highlight">采集游戏列表</span>开始采集游戏基本信息，或直接点击<span class="yyk-st-highlight">一键采集全部</span>完成所有操作。</p>
                                    </div>
                                </div>
                                
                                <div class="yyk-st-tutorial-step">
                                    <div class="yyk-st-tutorial-step-number">4</div>
                                    <div class="yyk-st-tutorial-step-content">
                                        <h3>发布游戏</h3>
                                        <p>前往<span class="yyk-st-highlight">发布管理</span>选项卡，查看已采集的游戏，选择需要的游戏发布到您的网站。</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="yyk-st-panel">
                        <div class="yyk-st-panel-header">
                            <h2>采集功能详解</h2>
                        </div>
                        <div class="yyk-st-panel-body">
                            <div class="yyk-st-tutorial-features">
                                <div class="yyk-st-tutorial-feature">
                                    <h3>📂 同步分类</h3>
                                    <p>将API中的游戏分类同步到WordPress分类系统，确保游戏分类匹配。</p>
                                </div>
                                
                                <div class="yyk-st-tutorial-feature">
                                    <h3>🎮 采集游戏列表</h3>
                                    <p>采集游戏的基本信息，包括游戏名称、图标、大小、分类、下载地址等。</p>
                                </div>
                                
                                <div class="yyk-st-tutorial-feature">
                                    <h3>📋 采集游戏详情</h3>
                                    <p>采集游戏的详细信息，包括游戏介绍、截图、标签、礼包信息等。</p>
                                </div>
                                
                                <div class="yyk-st-tutorial-feature">
                                    <h3>📅 采集预约游戏</h3>
                                    <p>采集即将发布的预约游戏信息。</p>
                                </div>
                                
                                <div class="yyk-st-tutorial-feature">
                                    <h3>🏆 采集排行榜</h3>
                                    <p>采集热门游戏排行榜数据。</p>
                                </div>
                                
                                <div class="yyk-st-tutorial-feature">
                                    <h3>🎁 采集游戏礼包</h3>
                                    <p>采集游戏的礼包码和兑换信息。</p>
                                </div>
                                
                                <div class="yyk-st-tutorial-feature">
                                    <h3>🚀 一键采集全部</h3>
                                    <p>自动完成所有采集操作：同步分类→采集游戏列表→采集游戏详情→采集礼包，一步到位！</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="yyk-st-panel">
                        <div class="yyk-st-panel-header">
                            <h2>常见问题</h2>
                        </div>
                        <div class="yyk-st-panel-body">
                            <div class="yyk-st-tutorial-faq">
                                <div class="yyk-st-tutorial-faq-item">
                                    <h3>Q: 采集失败怎么办？</h3>
                                    <p>A: 检查网络连接，确认API源地址可访问。如持续失败，尝试切换数据源或稍后重试。</p>
                                </div>
                                
                                <div class="yyk-st-tutorial-faq-item">
                                    <h3>Q: 如何避免重复采集？</h3>
                                    <p>A: 系统会自动检测已存在的游戏，不会重复采集已有的游戏数据。</p>
                                </div>
                                
                                <div class="yyk-st-tutorial-faq-item">
                                    <h3>Q: 采集后如何发布游戏？</h3>
                                    <p>A: 前往"发布管理"选项卡，选中需要发布的游戏，点击发布按钮即可将游戏发布到WordPress。</p>
                                </div>
                                
                                <div class="yyk-st-tutorial-faq-item">
                                    <h3>Q: 可以只采集部分游戏吗？</h3>
                                    <p>A: 可以！先采集游戏列表，然后在"发布管理"中选择您需要的游戏进行发布。</p>
                                </div>
                                
                                <div class="yyk-st-tutorial-faq-item">
                                    <h3>Q: 数据库表不存在怎么办？</h3>
                                    <p>A: 点击页面顶部的"修复数据库"按钮，系统会自动创建所需的数据表。</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 设置选项卡 -->
                <div id="settings" class="yyk-st-tab-content">
                    <div class="yyk-st-panel">
                        <div class="yyk-st-panel-header">
                            <h2>API配置</h2>
                        </div>
                        <div class="yyk-st-panel-body">
                            <form id="st-settings-form" method="post">
                                <?php wp_nonce_field('yyk_st_settings'); ?>
                                <div class="yyk-st-form-row">
                                    <div class="yyk-st-form-group">
                                        <label>数据源</label>
                                        <select name="api_source" class="yyk-st-input">
                                            <option value="steamsy" <?php selected($api_source, 'steamsy'); ?>>ST手游 (www.steamsy.com)</option>
                                            <option value="hehesy" <?php selected($api_source, 'hehesy'); ?>>梨子手游 (box.hehesy.com)</option>
                                        </select>
                                        <p class="yyk-st-help">选择要采集的数据源</p>
                                    </div>
                                </div>
                                
                                <div class="yyk-st-form-row">
                                    <div class="yyk-st-form-group">
                                        <label>渠道ID</label>
                                        <input type="text" name="cps_id" value="<?php echo esc_attr($cps_id); ?>" class="yyk-st-input">
                                        <p class="yyk-st-help">您的渠道账号，用于统计分成</p>
                                    </div>
                                </div>
                                
                                <div class="yyk-st-form-actions">
                                    <button type="submit" name="save_settings" class="yyk-st-btn yyk-st-btn-primary">
                                        <span class="dashicons dashicons-saved"></span>
                                        保存设置
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                
                <!-- 采集选项卡 -->
                <div id="collect" class="yyk-st-tab-content">
                    <div class="yyk-st-panel">
                        <div class="yyk-st-panel-header">
                            <h2>快速采集</h2>
                        </div>
                        <div class="yyk-st-panel-body">
                            <div class="yyk-st-collect-buttons">
                                <button type="button" class="yyk-st-btn yyk-st-btn-info" id="collect_categories">
                                    <span class="dashicons dashicons-category"></span>
                                    同步分类 (v2)
                                </button>
                                <button type="button" class="yyk-st-btn yyk-st-btn-success" id="collect_games">
                                    <span class="dashicons dashicons-games"></span>
                                    采集游戏列表 (v1)
                                </button>
                                <button type="button" class="yyk-st-btn yyk-st-btn-warning" id="collect_details">
                                    <span class="dashicons dashicons-list-view"></span>
                                    采集游戏详情 (v3)
                                </button>
                                <button type="button" class="yyk-st-btn yyk-st-btn-secondary" id="collect_reserve">
                                    <span class="dashicons dashicons-calendar"></span>
                                    采集预约游戏 (v4)
                                </button>
                                <button type="button" class="yyk-st-btn yyk-st-btn-secondary" id="collect_ranking">
                                    <span class="dashicons dashicons-chart-bar"></span>
                                    采集排行榜 (v5)
                                </button>
                                <button type="button" class="yyk-st-btn yyk-st-btn-secondary" id="collect_gifts">
                                    <span class="dashicons dashicons-star-filled"></span>
                                    采集游戏礼包 (v6)
                                </button>
                                <button type="button" class="yyk-st-btn yyk-st-btn-primary" id="collect_all">
                                    <span class="dashicons dashicons-update"></span>
                                    一键采集全部
                                </button>
                            </div>
                            
                            <div id="collect_status" class="yyk-st-alert yyk-st-alert-info" style="display: none;"></div>
                            <div id="collect_result" style="margin-top: 15px;"></div>
                        </div>
                    </div>
                </div>
                
                <!-- 发布管理选项卡 -->
                <div id="publish" class="yyk-st-tab-content">
                    <div class="yyk-st-panel">
                        <div class="yyk-st-panel-header">
                            <h2>游戏管理</h2>
                            <div class="yyk-st-header-actions">
                                <button type="button" class="yyk-st-btn yyk-st-btn-primary" id="publish_all">
                                    <span class="dashicons dashicons-upload"></span>
                                    发布所有未发布游戏
                                </button>
                                <button type="button" class="yyk-st-btn yyk-st-btn-danger" id="delete_all">
                                    <span class="dashicons dashicons-trash"></span>
                                    删除所有游戏
                                </button>
                            </div>
                        </div>
                        <div class="yyk-st-panel-body">
                            <div id="game_list_stats" class="yyk-st-alert yyk-st-alert-info" style="margin-bottom: 20px;"></div>
                            <div id="game_list"></div>
                            <div id="game_pagination" class="yyk-st-pagination"></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <style>
            .yyk-st-collector-page {
                max-width: 1200px;
            }
            
            .yyk-page-title {
                display: flex;
                align-items: center;
                gap: 12px;
                font-size: 24px;
                font-weight: 600;
                color: #1d2327;
                margin-bottom: 25px;
            }
            
            .yyk-page-title .dashicons {
                font-size: 28px;
                width: 28px;
                height: 28px;
                color: #667eea;
            }
            
            /* 统计卡片 */
            .yyk-st-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
                gap: 20px;
                margin-bottom: 25px;
            }
            
            .yyk-st-card {
                background: white;
                border-radius: 16px;
                padding: 24px;
                display: flex;
                align-items: center;
                gap: 20px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
                border: 1px solid #e8e8e8;
                transition: all 0.3s ease;
            }
            
            .yyk-st-card:hover {
                transform: translateY(-3px);
                box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            }
            
            .yyk-st-card-icon {
                width: 64px;
                height: 64px;
                border-radius: 14px;
                display: flex;
                align-items: center;
                justify-content: center;
                flex-shrink: 0;
            }
            
            .yyk-st-card-icon .dashicons {
                font-size: 32px;
                width: 32px;
                height: 32px;
                color: white;
            }
            
            .yyk-st-card-info {
                flex: 1;
            }
            
            .yyk-st-card-value {
                font-size: 28px;
                font-weight: 700;
                color: #1d2327;
                line-height: 1.2;
                margin-bottom: 4px;
            }
            
            .yyk-st-card-label {
                font-size: 14px;
                color: #64748b;
                font-weight: 500;
            }
            
            /* 警告框 */
            .yyk-st-alert {
                display: flex;
                align-items: center;
                gap: 12px;
                padding: 16px 20px;
                border-radius: 12px;
                margin-bottom: 20px;
                font-size: 14px;
            }
            
            .yyk-st-alert-info {
                background: #d1ecf1;
                color: #0c5460;
                border: 1px solid #bee5eb;
            }
            
            .yyk-st-alert-icon {
                font-size: 20px;
                flex-shrink: 0;
            }
            
            .yyk-st-alert-content {
                flex: 1;
            }
            
            .yyk-st-alert code {
                background: rgba(0,0,0,0.1);
                padding: 2px 8px;
                border-radius: 4px;
                font-family: monospace;
            }
            
            /* 选项卡 */
            .yyk-st-tabs {
                display: flex;
                gap: 8px;
                margin-bottom: 20px;
                background: white;
                padding: 8px;
                border-radius: 12px;
                border: 1px solid #e8e8e8;
            }
            
            .yyk-st-tab {
                flex: 1;
                display: flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 14px 20px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 500;
                color: #64748b;
                transition: all 0.3s ease;
            }
            
            .yyk-st-tab:hover {
                background: #f8f9fa;
                color: #475569;
            }
            
            .yyk-st-tab.yyk-st-tab-active {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            
            .yyk-st-tab .dashicons {
                font-size: 18px;
                width: 18px;
                height: 18px;
            }
            
            /* 选项卡内容 */
            .yyk-st-tab-content {
                display: none;
            }
            
            .yyk-st-tab-content.yyk-st-tab-content-active {
                display: block;
            }
            
            /* 面板 */
            .yyk-st-panel {
                background: white;
                border-radius: 16px;
                border: 1px solid #e8e8e8;
                overflow: hidden;
            }
            
            .yyk-st-panel-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 20px 24px;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .yyk-st-panel-header h2 {
                margin: 0;
                font-size: 18px;
                font-weight: 600;
                color: #1d2327;
            }
            
            .yyk-st-header-actions {
                display: flex;
                gap: 10px;
            }
            
            .yyk-st-panel-body {
                padding: 24px;
            }
            
            /* 表单 */
            .yyk-st-form-row {
                margin-bottom: 24px;
            }
            
            .yyk-st-form-group {
                display: flex;
                flex-direction: column;
                gap: 8px;
            }
            
            .yyk-st-form-group label {
                font-size: 14px;
                font-weight: 600;
                color: #1d2327;
            }
            
            .yyk-st-input {
                width: 100%;
                max-width: 500px;
                padding: 12px 16px;
                border: 2px solid #e8e8e8;
                border-radius: 10px;
                font-size: 14px;
                transition: all 0.3s ease;
            }
            
            .yyk-st-input:focus {
                outline: none;
                border-color: #667eea;
                box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
            }
            
            .yyk-st-help {
                font-size: 13px;
                color: #64748b;
                margin: 0;
            }
            
            .yyk-st-form-actions {
                margin-top: 20px;
            }
            
            /* 按钮 */
            .yyk-st-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 8px;
                padding: 12px 20px;
                border: none;
                border-radius: 10px;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                text-decoration: none;
            }
            
            .yyk-st-btn .dashicons {
                font-size: 18px;
                width: 18px;
                height: 18px;
            }
            
            .yyk-st-btn-primary {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            
            .yyk-st-btn-primary:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
            }
            
            .yyk-st-btn-success {
                background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
                color: white;
            }
            
            .yyk-st-btn-success:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
            }
            
            .yyk-st-btn-info {
                background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
                color: white;
            }
            
            .yyk-st-btn-info:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(79, 172, 254, 0.4);
            }
            
            .yyk-st-btn-warning {
                background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
                color: white;
            }
            
            .yyk-st-btn-warning:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(240, 147, 251, 0.4);
            }
            
            .yyk-st-btn-secondary {
                background: #f8f9fa;
                color: #475569;
                border: 2px solid #e8e8e8;
            }
            
            .yyk-st-btn-secondary:hover {
                background: #e8e8e8;
            }
            
            .yyk-st-btn-danger {
                background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
                color: white;
            }
            
            .yyk-st-btn-danger:hover {
                transform: translateY(-1px);
                box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            }
            
            .yyk-st-btn:disabled {
                opacity: 0.6;
                cursor: not-allowed;
                transform: none !important;
                box-shadow: none !important;
            }
            
            /* 采集按钮网格 */
            .yyk-st-collect-buttons {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
                gap: 12px;
            }
            
            /* 游戏状态 */
            .yyk-game-status {
                padding: 4px 10px;
                border-radius: 12px;
                font-size: 12px;
                font-weight: 600;
                display: inline-block;
            }
            
            .yyk-game-status.published {
                background: #d4edda;
                color: #155724;
            }
            
            .yyk-game-status.unpublished {
                background: #fff3cd;
                color: #856404;
            }
            
            /* 分页 */
            .yyk-st-pagination {
                margin-top: 20px;
            }
            
            .yyk-st-pagination-wrapper {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 12px;
            }
            
            .yyk-st-pagination-inner {
                display: flex;
                align-items: center;
                gap: 8px;
                flex-wrap: wrap;
                justify-content: center;
            }
            
            .yyk-st-pagination-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                gap: 6px;
                min-width: 44px;
                height: 44px;
                padding: 0 16px;
                background: #f8f9fa;
                border: 2px solid #e8e8e8;
                border-radius: 10px;
                color: #475569;
                font-size: 14px;
                font-weight: 600;
                cursor: pointer;
                transition: all 0.3s ease;
                box-shadow: 0 2px 6px rgba(0, 0, 0, 0.04);
            }
            
            .yyk-st-pagination-btn:hover:not(:disabled) {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border-color: #667eea;
                transform: translateY(-2px);
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.4);
            }
            
            .yyk-st-pagination-btn.yyk-st-pagination-current {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border-color: #667eea;
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.3);
            }
            
            .yyk-st-pagination-btn.yyk-st-pagination-disabled {
                opacity: 0.5;
                cursor: not-allowed;
                background: #f1f5f9;
            }
            
            .yyk-st-pagination-btn .dashicons {
                font-size: 18px;
                width: 18px;
                height: 18px;
            }
            
            .yyk-st-pagination-pages {
                display: flex;
                align-items: center;
                gap: 8px;
            }
            
            .yyk-st-pagination-dots {
                padding: 0 8px;
                color: #94a3b8;
                font-weight: 600;
                font-size: 18px;
            }
            
            .yyk-st-pagination-info {
                color: #64748b;
                font-size: 14px;
                font-weight: 500;
            }
            
            /* 教程样式 */
            .yyk-st-tutorial-steps {
                display: flex;
                flex-direction: column;
                gap: 20px;
            }
            
            .yyk-st-tutorial-step {
                display: flex;
                gap: 20px;
                padding: 20px;
                background: #f8f9fa;
                border-radius: 12px;
                border-left: 4px solid #667eea;
                transition: all 0.3s ease;
            }
            
            .yyk-st-tutorial-step:hover {
                transform: translateX(5px);
                box-shadow: 0 4px 12px rgba(102, 126, 234, 0.15);
            }
            
            .yyk-st-tutorial-step-number {
                display: flex;
                align-items: center;
                justify-content: center;
                min-width: 48px;
                height: 48px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                font-size: 20px;
                font-weight: 700;
                border-radius: 50%;
                flex-shrink: 0;
            }
            
            .yyk-st-tutorial-step-content h3 {
                margin: 0 0 8px 0;
                font-size: 18px;
                font-weight: 600;
                color: #1e293b;
            }
            
            .yyk-st-tutorial-step-content p {
                margin: 0;
                color: #64748b;
                font-size: 15px;
                line-height: 1.6;
            }
            
            .yyk-st-highlight {
                display: inline-block;
                padding: 2px 8px;
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
                border-radius: 4px;
                font-weight: 600;
                font-size: 14px;
            }
            
            .yyk-st-tutorial-features {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
                gap: 20px;
            }
            
            .yyk-st-tutorial-feature {
                padding: 20px;
                background: #f8f9fa;
                border-radius: 12px;
                border-top: 3px solid #667eea;
                transition: all 0.3s ease;
            }
            
            .yyk-st-tutorial-feature:hover {
                transform: translateY(-5px);
                box-shadow: 0 8px 20px rgba(102, 126, 234, 0.15);
            }
            
            .yyk-st-tutorial-feature h3 {
                margin: 0 0 10px 0;
                font-size: 17px;
                font-weight: 600;
                color: #1e293b;
            }
            
            .yyk-st-tutorial-feature p {
                margin: 0;
                color: #64748b;
                font-size: 14px;
                line-height: 1.6;
            }
            
            .yyk-st-tutorial-faq {
                display: flex;
                flex-direction: column;
                gap: 15px;
            }
            
            .yyk-st-tutorial-faq-item {
                padding: 20px;
                background: #f8f9fa;
                border-radius: 12px;
                border-left: 4px solid #11998e;
                transition: all 0.3s ease;
            }
            
            .yyk-st-tutorial-faq-item:hover {
                box-shadow: 0 4px 12px rgba(17, 153, 142, 0.15);
            }
            
            .yyk-st-tutorial-faq-item h3 {
                margin: 0 0 10px 0;
                font-size: 16px;
                font-weight: 600;
                color: #1e293b;
            }
            
            .yyk-st-tutorial-faq-item p {
                margin: 0;
                color: #64748b;
                font-size: 14px;
                line-height: 1.6;
            }
            
            /* 采集结果 */
            .collect-result-item {
                padding: 14px 18px;
                margin: 10px 0;
                border-radius: 10px;
                border-left: 4px solid;
            }
            
            .collect-result-item.success {
                background: #d4edda;
                color: #155724;
                border-left-color: #28a745;
            }
            
            .collect-result-item.error {
                background: #f8d7da;
                color: #721c24;
                border-left-color: #dc3545;
            }
            </style>
            
            <script>
            jQuery(document).ready(function($) {
                $('.yyk-st-tab').click(function(e) {
                    var tabId = $(this).data('tab');
                    $('.yyk-st-tab').removeClass('yyk-st-tab-active');
                    $(this).addClass('yyk-st-tab-active');
                    $('.yyk-st-tab-content').removeClass('yyk-st-tab-content-active');
                    $('#' + tabId).addClass('yyk-st-tab-content-active');
                });
                
                function showStatus(msg, isSuccess) {
                    $('#collect_status').show().html(msg);
                    setTimeout(function() { $('#collect_status').fadeOut(); }, 5000);
                }
                
                function showResult(data, type) {
                    var html = '<div class="collect-result-item ' + (data.success ? 'success' : 'error') + '">';
                    html += '<div style="font-size:16px;font-weight:600;margin-bottom:8px;">' + type + '</div>';
                    if (data.success) {
                        if (data.saved !== undefined && data.saved > 0) html += '<div style="display:flex;align-items:center;gap:8px;margin:4px 0;"><span style="font-size:20px">✅</span> 成功保存: <strong>' + data.saved + '</strong> 个游戏</div>';
                        if (data.skipped !== undefined && data.skipped > 0) html += '<div style="display:flex;align-items:center;gap:8px;margin:4px 0;"><span style="font-size:20px">⚠️</span> 跳过: <strong>' + data.skipped + '</strong> 个（已存在）</div>';
                        if (data.failed !== undefined && data.failed > 0) html += '<div style="display:flex;align-items:center;gap:8px;margin:4px 0;"><span style="font-size:20px">❌</span> 采集失败: <strong>' + data.failed + '</strong> 个</div>';
                        if (data.total !== undefined) html += '<div style="display:flex;align-items:center;gap:8px;margin:4px 0;"><span style="font-size:20px">📊</span> 本次处理: <strong>' + data.total + '</strong> 个</div>';
                    } else {
                        html += '<div style="display:flex;align-items:center;gap:8px;"><span style="font-size:20px">❌</span> ' + (data.message || data.error || '未知错误') + '</div>';
                    }
                    html += '</div>';
                    $('#collect_result').prepend(html);
                }
                
                // 保存按钮原始内容
                var buttonHtmls = {};
                $('.yyk-st-btn[id^="collect_"]').each(function() {
                    buttonHtmls[$(this).attr('id')] = $(this).html();
                });
                
                // 同步分类
                $('#collect_categories').click(function() {
                    var btnId = $(this).attr('id');
                    $(this).prop('disabled', true).html('<span class="dashicons dashicons-update"></span> 同步中...');
                    $.post(ajaxurl, { action: 'yyk_st_collect', type: 'categories', nonce: '<?php echo wp_create_nonce("yyk_st_collect"); ?>' }, function(r) {
                        $('#' + btnId).prop('disabled', false).html(buttonHtmls[btnId]);
                        if (r.success) {
                            showStatus('同步成功，新增 ' + (r.data.saved || 0) + ' 个分类', true);
                            showResult(r.data, '分类同步');
                        } else {
                            showStatus(r.data.message || '同步失败', false);
                        }
                    });
                });
                
                // 采集游戏列表
                var currentPage = 1;
                $('#collect_games').click(function() {
                    var btnId = $(this).attr('id');
                    $(this).prop('disabled', true).html('<span class="dashicons dashicons-update"></span> 采集中...');
                    $('#collect_result').empty();
                    $('#collect_status').show().html('🎯 正在采集第 ' + currentPage + ' 页游戏...');
                    $.post(ajaxurl, { action: 'yyk_st_collect', type: 'games_all', page: currentPage, nonce: '<?php echo wp_create_nonce("yyk_st_collect"); ?>' }, function(r) {
                        $('#' + btnId).prop('disabled', false).html(buttonHtmls[btnId]);
                        if (r.success) {
                            var data = r.data;
                            var pageInfo = '第 ' + (data.now_page || data.page || 1) + ' 页';
                            if (data.total_page) {
                                pageInfo += ' / 共 ' + data.total_page + ' 页';
                            }
                            if (data.api_total) {
                                pageInfo += ' (总计 ' + data.api_total + ' 个游戏)';
                            }
                            
                            showResult({
                                success: true,
                                saved: data.saved || 0,
                                skipped: data.skipped || 0,
                                failed: data.failed || 0,
                                total: data.total || 0
                            }, '🎮 游戏列表采集 - ' + pageInfo);
                            
                            loadGameList();
                            
                            // 检查是否到达最后一页
                            var isLastPage = false;
                            if (data.total_page && data.now_page >= data.total_page) {
                                isLastPage = true;
                            }
                            
                            if (!isLastPage) {
                                $('#collect_result').append('<div style="margin-top:10px;padding:15px;background:#fff3cd;border-radius:8px;border-left:4px solid #ffc107"><div style="font-size:16px;font-weight:600;color:#856404;margin-bottom:10px;">💡 当前页采集完成</div><div style="display:flex;gap:10px;flex-wrap:wrap"><button type="button" class="yyk-st-btn yyk-st-btn-primary" id="collect_next_page"><span class="dashicons dashicons-arrow-right-alt2"></span> 采集下一页</button><button type="button" class="yyk-st-btn yyk-st-btn-secondary" id="refresh_list"><span class="dashicons dashicons-update"></span> 刷新列表</button></div></div>');
                            } else {
                                $('#collect_result').append('<div style="margin-top:10px;padding:15px;background:#d4edda;border-radius:8px;border-left:4px solid #27ae60"><div style="font-size:16px;font-weight:600;color:#155724;margin-bottom:10px;">🎉 所有页面采集完成！</div><button type="button" class="yyk-st-btn yyk-st-btn-success" id="refresh_list"><span class="dashicons dashicons-update"></span> 刷新游戏列表</button></div>');
                            }
                        } else {
                            showResult({success: false, message: r.data.message || '采集失败'}, '❌ 采集失败');
                        }
                    });
                });
                
                // 采集下一页
                $(document).on('click', '#collect_next_page', function() {
                    currentPage++;
                    $('#collect_games').click();
                });
                
                // 采集游戏详情
                $('#collect_details').click(function() {
                    if (!confirm('确定要采集所有游戏的详情吗？这可能需要较长时间。')) return;
                    
                    var btnId = $(this).attr('id');
                    $(this).prop('disabled', true).html('<span class="dashicons dashicons-update"></span> 采集中...');
                    $('#collect_result').empty();
                    
                    // 先获取所有游戏ID
                    $.post(ajaxurl, { action: 'yyk_st_publish', action_type: 'list', page: 1, nonce: '<?php echo wp_create_nonce("yyk_st_publish"); ?>' }, function(r) {
                        if (r.success && r.data.games && r.data.games.length > 0) {
                            var games = r.data.games;
                            var gameIds = games.map(function(g) { return g.game_id; });
                            var gameNames = games.map(function(g) { return g.game_name; });
                            var processed = 0;
                            var failed = 0;
                            
                            function processNext(index) {
                                if (index >= gameIds.length) {
                                    $('#collect_details').prop('disabled', false).html(buttonHtmls['collect_details']);
                                    showResult({
                                        success: true,
                                        saved: processed,
                                        failed: failed,
                                        total: gameIds.length
                                    }, '📋 游戏详情采集完成');
                                    loadGameList();
                                    return;
                                }
                                
                                var currentGameName = gameNames[index] || '未知游戏';
                                $('#collect_status').show().html('📋 正在采集: ' + (index + 1) + '/' + gameIds.length + ' - ' + currentGameName);
                                
                                $.post(ajaxurl, { 
                                    action: 'yyk_st_collect', 
                                    type: 'detail', 
                                    game_id: gameIds[index], 
                                    nonce: '<?php echo wp_create_nonce("yyk_st_collect"); ?>' 
                                }, function(r) {
                                    var resultHtml = '<div class="collect-result-item ' + (r.success ? 'success' : 'error') + '">';
                                    resultHtml += '<span style="font-size:16px;margin-right:8px">' + (r.success ? '✅' : '❌') + '</span>';
                                    resultHtml += '<strong>' + currentGameName + '</strong>';
                                    if (!r.success && r.data && r.data.message) {
                                        resultHtml += '<br><small>' + r.data.message + '</small>';
                                    }
                                    resultHtml += '</div>';
                                    $('#collect_result').prepend(resultHtml);
                                    
                                    if (r.success) {
                                        processed++;
                                    } else {
                                        failed++;
                                    }
                                    
                                    setTimeout(function() { processNext(index + 1); }, 300);
                                });
                            }
                            
                            processNext(0);
                        } else {
                            $('#collect_details').prop('disabled', false).html(buttonHtmls['collect_details']);
                            showResult({success: false, message: '没有游戏数据，请先采集游戏列表'}, '❌ 采集失败');
                        }
                    });
                });
                
                // 采集预约游戏
                $('#collect_reserve').click(function() {
                    var btnId = $(this).attr('id');
                    $(this).prop('disabled', true).html('<span class="dashicons dashicons-update"></span> 采集中...');
                    $('#collect_result').empty();
                    $('#collect_status').show().html('📅 正在采集预约游戏...');
                    $.post(ajaxurl, { action: 'yyk_st_collect', type: 'reserve', page: 1, nonce: '<?php echo wp_create_nonce("yyk_st_collect"); ?>' }, function(r) {
                        $('#' + btnId).prop('disabled', false).html(buttonHtmls[btnId]);
                        if (r.success) {
                            showResult(r.data, '📅 预约游戏采集');
                            loadGameList();
                        } else {
                            showResult({success: false, message: r.data.message || '采集失败'}, '❌ 采集失败');
                        }
                    });
                });
                
                // 采集排行榜
                $('#collect_ranking').click(function() {
                    var btnId = $(this).attr('id');
                    $(this).prop('disabled', true).html('<span class="dashicons dashicons-update"></span> 采集中...');
                    $('#collect_result').empty();
                    $('#collect_status').show().html('🏆 正在采集排行榜...');
                    $.post(ajaxurl, { action: 'yyk_st_collect', type: 'ranking', nonce: '<?php echo wp_create_nonce("yyk_st_collect"); ?>' }, function(r) {
                        $('#' + btnId).prop('disabled', false).html(buttonHtmls[btnId]);
                        if (r.success) {
                            showResult(r.data, '🏆 排行榜采集');
                            loadGameList();
                        } else {
                            showResult({success: false, message: r.data.message || '采集失败'}, '❌ 采集失败');
                        }
                    });
                });
                
                // 采集游戏礼包
                $('#collect_gifts').click(function() {
                    if (!confirm('确定要采集所有游戏的礼包吗？这可能需要较长时间。')) return;
                    
                    var btnId = $(this).attr('id');
                    $(this).prop('disabled', true).html('<span class="dashicons dashicons-update"></span> 采集中...');
                    $('#collect_result').empty();
                    
                    // 先获取所有游戏ID
                    $.post(ajaxurl, { action: 'yyk_st_publish', action_type: 'list', page: 1, nonce: '<?php echo wp_create_nonce("yyk_st_publish"); ?>' }, function(r) {
                        if (r.success && r.data.games && r.data.games.length > 0) {
                            var games = r.data.games;
                            var gameIds = games.map(function(g) { return g.game_id; });
                            var gameNames = games.map(function(g) { return g.game_name; });
                            var processed = 0;
                            var failed = 0;
                            var totalGifts = 0;
                            
                            function processNext(index) {
                                if (index >= gameIds.length) {
                                    $('#collect_gifts').prop('disabled', false).html(buttonHtmls['collect_gifts']);
                                    showResult({
                                        success: true,
                                        saved: totalGifts,
                                        total: gameIds.length
                                    }, '🎁 游戏礼包采集完成');
                                    loadGameList();
                                    return;
                                }
                                
                                var currentGameName = gameNames[index] || '未知游戏';
                                $('#collect_status').show().html('🎁 正在采集礼包: ' + (index + 1) + '/' + gameIds.length + ' - ' + currentGameName);
                                
                                $.post(ajaxurl, { 
                                    action: 'yyk_st_collect', 
                                    type: 'gifts', 
                                    game_id: gameIds[index], 
                                    nonce: '<?php echo wp_create_nonce("yyk_st_collect"); ?>' 
                                }, function(r) {
                                    var giftCount = r.data ? (r.data.saved || 0) : 0;
                                    var resultHtml = '<div class="collect-result-item ' + (r.success ? 'success' : 'error') + '">';
                                    resultHtml += '<span style="font-size:16px;margin-right:8px">' + (r.success ? '✅' : '❌') + '</span>';
                                    resultHtml += '<strong>' + currentGameName + '</strong>';
                                    if (r.success && giftCount > 0) {
                                        resultHtml += ' - <strong>' + giftCount + '</strong> 个礼包';
                                    }
                                    resultHtml += '</div>';
                                    $('#collect_result').prepend(resultHtml);
                                    
                                    if (r.success) {
                                        processed++;
                                        totalGifts += giftCount;
                                    } else {
                                        failed++;
                                    }
                                    
                                    setTimeout(function() { processNext(index + 1); }, 300);
                                });
                            }
                            
                            processNext(0);
                        } else {
                            $('#collect_gifts').prop('disabled', false).html(buttonHtmls['collect_gifts']);
                            showResult({success: false, message: '没有游戏数据，请先采集游戏列表'}, '❌ 采集失败');
                        }
                    });
                });
                
                // 一键采集
                $('#collect_all').click(function() {
                    var btnId = $(this).attr('id');
                    $(this).prop('disabled', true).html('<span class="dashicons dashicons-update"></span> 一键采集中...');
                    $('#collect_result').empty();
                    $('#collect_status').show().html('🚀 正在一键采集...');
                    $.post(ajaxurl, { action: 'yyk_st_collect', type: 'all', nonce: '<?php echo wp_create_nonce("yyk_st_collect"); ?>' }, function(r) {
                        $('#' + btnId).prop('disabled', false).html(buttonHtmls[btnId]);
                        if (r.success) {
                            var data = r.data;
                            var totalSaved = 0;
                            var totalFailed = 0;
                            if (data.games) {
                                totalSaved += (data.games.saved || 0);
                                totalFailed += (data.games.failed || 0);
                            }
                            showResult({
                                success: true,
                                saved: totalSaved,
                                failed: totalFailed,
                                total: totalSaved + totalFailed
                            }, '🚀 一键采集完成');
                            loadGameList();
                        } else {
                            showResult({success: false, message: r.data.message || '采集失败'}, '❌ 一键采集失败');
                        }
                    }.bind(this));
                });
                
                // 刷新列表按钮
                $(document).on('click', '#refresh_list', function() {
                    loadGameList();
                    $('#collect_status').show().html('<span style="color:green">✅ 列表已刷新</span>');
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
                    
                    // 生成美化的翻页按钮
                    var perPage = 20;
                    var totalPages = Math.ceil(total / perPage);
                    var paginationHtml = '';
                    
                    if (totalPages > 1) {
                        paginationHtml += '<div class="yyk-st-pagination-wrapper">';
                        paginationHtml += '<div class="yyk-st-pagination-inner">';
                        
                        // 上一页
                        if (page > 1) {
                            paginationHtml += '<button type="button" class="yyk-st-pagination-btn yyk-st-pagination-prev" data-page="' + (page - 1) + '">';
                            paginationHtml += '<span class="dashicons dashicons-arrow-left-alt2"></span> 上一页';
                            paginationHtml += '</button>';
                        } else {
                            paginationHtml += '<button type="button" class="yyk-st-pagination-btn yyk-st-pagination-disabled" disabled>';
                            paginationHtml += '<span class="dashicons dashicons-arrow-left-alt2"></span> 上一页';
                            paginationHtml += '</button>';
                        }
                        
                        // 页码
                        paginationHtml += '<div class="yyk-st-pagination-pages">';
                        
                        var startPage = Math.max(1, page - 2);
                        var endPage = Math.min(totalPages, page + 2);
                        
                        if (startPage > 1) {
                            paginationHtml += '<button type="button" class="yyk-st-pagination-btn yyk-st-pagination-page" data-page="1">1</button>';
                            if (startPage > 2) {
                                paginationHtml += '<span class="yyk-st-pagination-dots">...</span>';
                            }
                        }
                        
                        for (var i = startPage; i <= endPage; i++) {
                            if (i === page) {
                                paginationHtml += '<button type="button" class="yyk-st-pagination-btn yyk-st-pagination-page yyk-st-pagination-current" data-page="' + i + '" disabled>' + i + '</button>';
                            } else {
                                paginationHtml += '<button type="button" class="yyk-st-pagination-btn yyk-st-pagination-page" data-page="' + i + '">' + i + '</button>';
                            }
                        }
                        
                        if (endPage < totalPages) {
                            if (endPage < totalPages - 1) {
                                paginationHtml += '<span class="yyk-st-pagination-dots">...</span>';
                            }
                            paginationHtml += '<button type="button" class="yyk-st-pagination-btn yyk-st-pagination-page" data-page="' + totalPages + '">' + totalPages + '</button>';
                        }
                        
                        paginationHtml += '</div>';
                        
                        // 下一页
                        if (page < totalPages) {
                            paginationHtml += '<button type="button" class="yyk-st-pagination-btn yyk-st-pagination-next" data-page="' + (page + 1) + '">';
                            paginationHtml += '下一页 <span class="dashicons dashicons-arrow-right-alt2"></span>';
                            paginationHtml += '</button>';
                        } else {
                            paginationHtml += '<button type="button" class="yyk-st-pagination-btn yyk-st-pagination-disabled" disabled>';
                            paginationHtml += '下一页 <span class="dashicons dashicons-arrow-right-alt2"></span>';
                            paginationHtml += '</button>';
                        }
                        
                        paginationHtml += '</div>';
                        paginationHtml += '<div class="yyk-st-pagination-info">共 ' + total + ' 个游戏，第 ' + page + ' / ' + totalPages + ' 页</div>';
                        paginationHtml += '</div>';
                    }
                    
                    $('#game_pagination').html(paginationHtml);
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
                
                // 翻页按钮点击事件
                $(document).on('click', '.yyk-st-pagination-btn:not(.yyk-st-pagination-disabled):not(.yyk-st-pagination-current)', function() {
                    var page = $(this).data('page');
                    if (page && page > 0) {
                        loadGameList(page);
                    }
                });
                
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