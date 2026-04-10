<?php
if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('YYK_ST_Collector')) {

    class YYK_ST_Collector {
        
        private static $instance = null;
        private $api_domain = '';
        private $cps_id = '';
        private $timeout = 30;
        
        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        private function __construct() {
            $this->load_settings();
        }
        
        private function load_settings() {
            $this->api_domain = get_option('yyk_st_api_domain', 'https://www.steamsy.com');
            $this->cps_id = get_option('yyk_st_cps_id', '15907108869');
        }
        
        public function update_settings($api_domain, $cps_id) {
            if (!empty($api_domain)) {
                update_option('yyk_st_api_domain', $api_domain);
                $this->api_domain = $api_domain;
            }
            if (!empty($cps_id)) {
                update_option('yyk_st_cps_id', $cps_id);
                $this->cps_id = $cps_id;
            }
        }
        
        private function api_request($endpoint, $params = []) {
            $params['cpsId'] = $this->cps_id;
            $url = $this->api_domain . $endpoint . '?' . http_build_query($params);
            
            $response = wp_remote_get($url, [
                'timeout' => $this->timeout,
                'sslverify' => false,
                'headers' => ['User-Agent' => 'WordPress/YYK-ST-Collector']
            ]);
            
            if (is_wp_error($response)) {
                $this->log_error('API请求失败: ' . $response->get_error_message(), $url);
                return false;
            }
            
            $body = wp_remote_retrieve_body($response);
            return json_decode($body, true);
        }
        
        private function log_error($message, $url = '') {
            global $wpdb;
            $table = $wpdb->prefix . 'yyk_collect_logs';
            $wpdb->insert($table, [
                'action' => 'error',
                'message' => $message,
                'url' => $url,
                'status' => 'failed',
                'created_at' => current_time('mysql')
            ]);
            error_log('YYK ST: ' . $message);
        }
        
        private function log_success($action, $message, $count = 0) {
            global $wpdb;
            $table = $wpdb->prefix . 'yyk_collect_logs';
            $wpdb->insert($table, [
                'action' => $action,
                'message' => $message,
                'count' => $count,
                'status' => 'success',
                'created_at' => current_time('mysql')
            ]);
        }
        
        // ========== 采集分类 /v2/ ==========
        public function fetch_categories() {
            $data = $this->api_request('/v2/', []);
            
            if (!$data || !isset($data['type'])) {
                return ['success' => false, 'message' => '获取分类失败'];
            }
            
            $count = 0;
            foreach ($data['type'] as $cat) {
                $name = $cat['name'];
                if (empty($name) || $name == '全部') continue;
                
                $term = term_exists($name, 'yyk_app_category');
                if (!$term) {
                    wp_insert_term($name, 'yyk_app_category', ['slug' => sanitize_title($name)]);
                    $count++;
                }
            }
            
            $this->log_success('fetch_categories', '采集分类完成', $count);
            return ['success' => true, 'message' => "成功采集 {$count} 个分类", 'count' => $count];
        }
        
        // ========== 采集游戏列表 /v1/ ==========
        public function fetch_game_list($page = 1, $limit = 20) {
            $data = $this->api_request('/v1/', ['pagecode' => $page, 'pagenum' => $limit]);
            
            if (!$data || !isset($data['lists'])) {
                return ['success' => false, 'message' => '获取游戏列表失败'];
            }
            
            $count = 0;
            foreach ($data['lists'] as $game) {
                $post_id = $this->save_game_basic($game);
                if ($post_id) {
                    $count++;
                    $this->fetch_and_update_game_detail($game['id'], $post_id);
                }
            }
            
            $this->log_success('fetch_games', "采集游戏列表完成（第{$page}页）", $count);
            return ['success' => true, 'message' => "成功采集 {$count} 个游戏", 'count' => $count];
        }
        
        // ========== 采集预约列表 /v4/ ==========
        public function fetch_reserve_list($page = 1, $limit = 20) {
            $data = $this->api_request('/v4/', ['pagecode' => $page, 'pagenum' => $limit]);
            
            if (!$data || !isset($data['lists'])) {
                return ['success' => false, 'message' => '获取预约列表失败'];
            }
            
            $count = 0;
            foreach ($data['lists'] as $game) {
                $post_id = $this->save_game_basic($game);
                if ($post_id) {
                    $count++;
                    $this->fetch_and_update_game_detail($game['id'], $post_id);
                }
            }
            
            $this->log_success('fetch_reserve', "采集预约列表完成（第{$page}页）", $count);
            return ['success' => true, 'message' => "成功采集 {$count} 个预约游戏", 'count' => $count];
        }
        
        // ========== 保存游戏基础信息 ==========
        private function save_game_basic($game) {
            $game_id = $game['id'];
            $title = $game['gamename'] ?? '';
            
            if (empty($title) || empty($game_id)) return false;
            
            $existing = get_posts([
                'post_type' => 'yyk_app_download',
                'meta_key' => '_yyk_app_game_id',
                'meta_value' => $game_id,
                'posts_per_page' => 1,
                'fields' => 'ids'
            ]);
            
            if (!empty($existing)) {
                $post_id = $existing[0];
            } else {
                $post_id = wp_insert_post([
                    'post_title' => $title,
                    'post_type' => 'yyk_app_download',
                    'post_status' => 'publish',
                    'post_content' => $game['box_content'] ?? ''
                ]);
            }
            
            if (is_wp_error($post_id) || !$post_id) return false;
            
            update_post_meta($post_id, '_yyk_app_game_id', $game_id);
            update_post_meta($post_id, '_yyk_app_icon_url', $game['pic1'] ?? '');
            update_post_meta($post_id, '_yyk_app_size', $game['gamesize'] ?? '');
            update_post_meta($post_id, '_yyk_app_discount', $game['discount'] ?? '');
            update_post_meta($post_id, '_yyk_app_device_type', $game['device_type'] ?? '');
            update_post_meta($post_id, '_yyk_app_category_name', $game['typeword'] ?? '');
            update_post_meta($post_id, '_yyk_app_update_date', $game['updatetime'] ?? '');
            update_post_meta($post_id, '_yyk_app_welfare_intro', $game['excerpt'] ?? '');
            update_post_meta($post_id, '_yyk_app_welfare_tags', $game['fuli'] ?? '');
            update_post_meta($post_id, '_yyk_app_short_intro', $game['Welfare'] ?? '');
            update_post_meta($post_id, '_yyk_app_rebate_intro', $game['fanli'] ?? '');
            update_post_meta($post_id, '_yyk_app_vip_intro', $game['Vip'] ?? '');
            
            return $post_id;
        }
        
        // ========== 调用 /v3/ 补全详情 ==========
        private function fetch_and_update_game_detail($game_id, $post_id) {
            $data = $this->api_request('/v3/', ['gid' => $game_id]);
            
            if (!$data || !isset($data['a']) || $data['a'] != 1) {
                return false;
            }
            
            $detail = $data['c'];
            
            // 下载地址（替换域名）
            $download_url = $detail['url'] ?? '';
            $download_url = str_replace('qudao.guazisy.com', 'qudao.steamsy.com', $download_url);
            update_post_meta($post_id, '_yyk_app_download_url', $download_url);
            
            // 五宣图
            if (!empty($detail['photo'])) {
                update_post_meta($post_id, '_yyk_app_photos', json_encode($detail['photo']));
            }
            
            // 下载次数
            if (isset($detail['downloadnum'])) {
                update_post_meta($post_id, '_yyk_app_download_count', $detail['downloadnum']);
            }
            
            // 如果有更完整的介绍，更新文章内容
            if (!empty($detail['box_content'])) {
                wp_update_post(['ID' => $post_id, 'post_content' => $detail['box_content']]);
            }
            
            return true;
        }
        
        // ========== 采集排行榜 /v5/ ==========
        public function fetch_rankings($toptype = 0, $days = 7, $limit = 20) {
            $data = $this->api_request('/v5/', ['toptype' => $toptype, 'diynum' => $days, 'totalnum' => $limit]);
            
            if (!$data || !isset($data['lists'])) {
                return ['success' => false, 'message' => '获取排行榜失败'];
            }
            
            global $wpdb;
            $table = $wpdb->prefix . 'yyk_rankings';
            $count = 0;
            $rank = 1;
            
            foreach ($data['lists'] as $item) {
                $game_id = $item['id'];
                $rank_value = $toptype == 0 ? ($item['register'] ?? 0) : ($item['amount'] ?? 0);
                
                $wpdb->replace($table, [
                    'game_id' => $game_id,
                    'rank_type' => $toptype,
                    'days' => $days,
                    'rank_value' => $rank_value,
                    'rank_num' => $rank++,
                    'created_at' => current_time('mysql')
                ]);
                $count++;
            }
            
            $type_name = $toptype == 0 ? '注册' : '充值';
            $this->log_success('fetch_rankings', "采集{$type_name}排行({$days}天)完成", $count);
            return ['success' => true, 'message' => "成功采集 {$count} 条排行榜", 'count' => $count];
        }
        
        // ========== 采集礼包 /v6/ ==========
        public function fetch_gifts($game_id) {
            $data = $this->api_request('/v6/', ['gid' => $game_id, 'pagecode' => 1]);
            
            if (!$data || !isset($data['lists'])) {
                return ['success' => false, 'message' => '获取礼包失败'];
            }
            
            global $wpdb;
            $table = $wpdb->prefix . 'yyk_gifts';
            $count = 0;
            
            foreach ($data['lists'] as $gift) {
                $wpdb->replace($table, [
                    'gift_id' => $gift['id'],
                    'game_id' => $game_id,
                    'gift_name' => $gift['name'],
                    'gift_code' => $gift['card'] ?? '',
                    'gift_desc' => $gift['excerpt'] ?? '',
                    'start_time' => $gift['start_time'] ?? null,
                    'end_time' => $gift['end_time'] ?? null,
                    'remain' => $gift['part_num'] ?? 0,
                    'created_at' => current_time('mysql')
                ]);
                $count++;
            }
            
            $this->log_success('fetch_gifts', "采集礼包完成", $count);
            return ['success' => true, 'message' => "成功采集 {$count} 个礼包", 'count' => $count];
        }
        
        // ========== 一键全部采集 ==========
        public function collect_all() {
            $results = [];
            $results['categories'] = $this->fetch_categories();
            
            for ($page = 1; $page <= 3; $page++) {
                $results['games_page_' . $page] = $this->fetch_game_list($page, 20);
            }
            
            $results['rankings_reg_7'] = $this->fetch_rankings(0, 7, 20);
            $results['rankings_reg_30'] = $this->fetch_rankings(0, 30, 20);
            $results['rankings_recharge_7'] = $this->fetch_rankings(1, 7, 20);
            $results['rankings_recharge_30'] = $this->fetch_rankings(1, 30, 20);
            
            return $results;
        }
        
        // ========== 日志方法 ==========
        public function get_logs($per_page = 20, $offset = 0) {
            global $wpdb;
            $table = $wpdb->prefix . 'yyk_collect_logs';
            return $wpdb->get_results($wpdb->prepare("SELECT * FROM {$table} ORDER BY id DESC LIMIT %d OFFSET %d", $per_page, $offset));
        }
        
        public function get_log_count() {
            global $wpdb;
            $table = $wpdb->prefix . 'yyk_collect_logs';
            return (int) $wpdb->get_var("SELECT COUNT(*) FROM {$table}");
        }
        
        public function clear_logs() {
            global $wpdb;
            $table = $wpdb->prefix . 'yyk_collect_logs';
            return $wpdb->query("TRUNCATE TABLE {$table}") !== false;
        }
        
        public function render_logs_table($page = 1, $per_page = 20) {
            $logs = $this->get_logs($per_page, ($page - 1) * $per_page);
            if (empty($logs)) {
                echo '<p>暂无日志</p>';
                return;
            }
            ?>
            <table class="widefat striped">
                <thead>
                    <tr><th>ID</th><th>操作</th><th>消息</th><th>数量</th><th>状态</th><th>时间</th</tr>
                </thead>
                <tbody>
                    <?php foreach ($logs as $log): ?>
                    <tr>
                        <td><?php echo $log->id; ?></td>
                        <td><?php echo $log->action; ?></td>
                        <td><?php echo $log->message; ?></td>
                        <td><?php echo $log->count ?: '-'; ?></td>
                        <td><?php echo $log->status == 'success' ? '成功' : '失败'; ?></td>
                        <td><?php echo $log->created_at; ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php
        }
    }
}