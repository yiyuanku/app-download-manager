<?php
/**
 * Plugin Name: 应用下载管理器 (YYK)
 * Plugin URI: https://yourwebsite.com/
 * Description: 专业的应用下载管理插件，支持卡片样式和游戏盒子样式展示
 * Version: 1.1.0
 * Author: 您的名字
 * License: GPL v2 or later
 * Text Domain: yyk-app-download
 */

// 防止直接访问
if (!defined('ABSPATH')) {
    exit;
}

// 版本检查
if (version_compare(PHP_VERSION, '7.0.0', '<')) {
    add_action('admin_notices', 'yyk_app_download_php_version_notice');
    return;
}

function yyk_app_download_php_version_notice() {
    ?>
    <div class="notice notice-error">
        <p><?php printf(__('应用下载管理器需要PHP 7.0或更高版本，当前版本：%s', 'yyk-app-download'), PHP_VERSION); ?></p>
    </div>
    <?php
}

// 定义插件常量
define('YYK_APP_VERSION', '1.1.0');
define('YYK_APP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YYK_APP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YYK_APP_ASSETS_URL', YYK_APP_PLUGIN_URL . 'assets/');

// 检查是否已定义类，避免重复定义
if (!class_exists('YYK_App_Download_Manager')) {

    class YYK_App_Download_Manager {
        
        private static $instance = null;
        
        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        private function __construct() {
            $this->init_hooks();
        }
        
        private function init_hooks() {
            // 插件激活/停用钩子
            register_activation_hook(__FILE__, [$this, 'activate']);
            register_deactivation_hook(__FILE__, [$this, 'deactivate']);
            
            // 初始化
            add_action('init', [$this, 'init'], 0);
            
            // 加载文本域
            add_action('plugins_loaded', [$this, 'load_textdomain']);
            
            // 注册样式和脚本
            add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
            
            // 添加诊断信息到插件列表
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_plugin_action_links']);
            
            // 添加诊断页面
            add_action('admin_menu', [$this, 'add_diagnostic_page']);
            
            // ========== ST采集 AJAX 钩子 ==========
            add_action('wp_ajax_yyk_get_st_nonce', [$this, 'ajax_yyk_get_st_nonce']);
            add_action('wp_ajax_st_collect_categories', [$this, 'ajax_st_collect_categories']);
            add_action('wp_ajax_st_collect_games', [$this, 'ajax_st_collect_games']);
            add_action('wp_ajax_st_collect_reserve', [$this, 'ajax_st_collect_reserve']);
            add_action('wp_ajax_st_collect_rankings', [$this, 'ajax_st_collect_rankings']);
            add_action('wp_ajax_st_collect_all', [$this, 'ajax_st_collect_all']);
            add_action('wp_ajax_st_save_settings', [$this, 'ajax_st_save_settings']);
            add_action('wp_ajax_st_clear_logs', [$this, 'ajax_st_clear_logs']);
            
            // ST采集菜单
            add_action('admin_menu', [$this, 'add_st_collect_menu']);
        }
        
        public function activate() {
            // 创建默认分类
            $this->create_default_categories();
            
            // 创建数据库表
            $this->create_database_tables();
            
            // 刷新重写规则
            flush_rewrite_rules();
            
            // 设置默认选项
            update_option('yyk_app_default_style', 'card');
            update_option('yyk_app_items_per_page', 12);
            
            // 设置ST采集默认值
            if (!get_option('yyk_st_api_domain')) {
                update_option('yyk_st_api_domain', 'https://www.steamsy.com');
            }
            if (!get_option('yyk_st_cps_id')) {
                update_option('yyk_st_cps_id', '15907108869');
            }
            
            // 注册定时任务
            if (!wp_next_scheduled('st_daily_collect')) {
                wp_schedule_event(time(), 'daily', 'st_daily_collect');
            }
            
            error_log('YYK应用下载管理器已激活');
        }
        
        public function deactivate() {
            wp_clear_scheduled_hook('st_daily_collect');
            flush_rewrite_rules();
            error_log('YYK应用下载管理器已停用');
        }
        
        private function create_database_tables() {
            global $wpdb;
            $charset_collate = $wpdb->get_charset_collate();
            
            // 采集日志表
            $table_logs = $wpdb->prefix . 'yyk_collect_logs';
            $sql_logs = "CREATE TABLE IF NOT EXISTS {$table_logs} (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                action VARCHAR(50) NOT NULL,
                message TEXT,
                url TEXT,
                count INT DEFAULT 0,
                status VARCHAR(20) DEFAULT 'pending',
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP
            ) {$charset_collate}";
            
            // 排行榜表
            $table_rankings = $wpdb->prefix . 'yyk_rankings';
            $sql_rankings = "CREATE TABLE IF NOT EXISTS {$table_rankings} (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                game_id VARCHAR(20) NOT NULL,
                rank_type TINYINT NOT NULL COMMENT '0注册/1充值',
                days TINYINT NOT NULL COMMENT '1/7/30',
                rank_value INT DEFAULT 0,
                rank_num INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_game_rank (game_id, rank_type, days)
            ) {$charset_collate}";
            
            // 礼包表
            $table_gifts = $wpdb->prefix . 'yyk_gifts';
            $sql_gifts = "CREATE TABLE IF NOT EXISTS {$table_gifts} (
                id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                gift_id VARCHAR(20) NOT NULL,
                game_id VARCHAR(20) NOT NULL,
                gift_name VARCHAR(200) NOT NULL,
                gift_code VARCHAR(100),
                gift_desc TEXT,
                start_time DATETIME,
                end_time DATETIME,
                remain INT DEFAULT 0,
                created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY uk_gift_id (gift_id)
            ) {$charset_collate}";
            
            require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
            dbDelta($sql_logs);
            dbDelta($sql_rankings);
            dbDelta($sql_gifts);
        }
        
        public function load_textdomain() {
            load_plugin_textdomain(
                'yyk-app-download',
                false,
                dirname(plugin_basename(__FILE__)) . '/languages/'
            );
        }
        
        private function create_default_categories() {
            $categories = [
                'tools' => __('工具软件', 'yyk-app-download'),
                'games' => __('游戏娱乐', 'yyk-app-download'),
                'productivity' => __('效率办公', 'yyk-app-download'),
                'education' => __('教育学习', 'yyk-app-download'),
            ];
            
            foreach ($categories as $slug => $name) {
                if (!term_exists($name, 'yyk_app_category')) {
                    wp_insert_term($name, 'yyk_app_category', ['slug' => $slug]);
                }
            }
        }
        
        public function init() {
            error_log('YYK应用下载管理器: 开始初始化');
            
            // 包含核心文件
            $this->include_files();
            
            // 强制重新注册文章类型和分类
            if (class_exists('YYK_App_Post_Type')) {
                YYK_App_Post_Type::get_instance()->init();
                error_log('YYK应用下载管理器: 文章类型已初始化');
            }

            // 初始化元字段
            if (class_exists('YYK_App_Meta_Boxes')) {
                YYK_App_Meta_Boxes::get_instance()->init();
            }
            
            // 初始化短代码
            if (class_exists('YYK_App_Shortcodes')) {
                YYK_App_Shortcodes::get_instance()->init();
            }
            
            // 初始化前端
            if (class_exists('YYK_App_Frontend')) {
                YYK_App_Frontend::get_instance()->init();
            }
            
            // 初始化诊断工具
            if (class_exists('YYK_App_Diagnostic')) {
                YYK_App_Diagnostic::get_instance();
            }
            
            error_log('YYK应用下载管理器: 初始化完成');
        }
        
        private function include_files() {
            $files = [
                'includes/class-post-type.php',
                'includes/class-meta-boxes.php',
                'includes/class-widget.php',
                'includes/class-shortcodes.php',
                'includes/class-frontend.php',
                'includes/class-diagnostic.php',
                'includes/class-st-collector.php',
            ];
            
            foreach ($files as $file) {
                $file_path = YYK_APP_PLUGIN_DIR . $file;
                if (file_exists($file_path)) {
                    require_once $file_path;
                } else {
                    error_log('YYK应用下载管理器：找不到文件 ' . $file);
                }
            }
        }
        
        // ========== ST采集 AJAX 方法 ==========
        
        public function ajax_yyk_get_st_nonce() {
            wp_send_json_success(['nonce' => wp_create_nonce('yyk_st_nonce')]);
        }
        
        public function ajax_st_collect_categories() {
            check_ajax_referer('yyk_st_nonce', 'nonce');
            if (!class_exists('YYK_ST_Collector')) {
                wp_send_json(['success' => false, 'message' => '采集类未加载']);
                return;
            }
            $result = YYK_ST_Collector::get_instance()->fetch_categories();
            wp_send_json($result);
        }
        
        public function ajax_st_collect_games() {
            check_ajax_referer('yyk_st_nonce', 'nonce');
            if (!class_exists('YYK_ST_Collector')) {
                wp_send_json(['success' => false, 'message' => '采集类未加载']);
                return;
            }
            $page = intval($_POST['page'] ?? 1);
            $limit = intval($_POST['limit'] ?? 20);
            $result = YYK_ST_Collector::get_instance()->fetch_game_list($page, $limit);
            wp_send_json($result);
        }
        
        public function ajax_st_collect_reserve() {
            check_ajax_referer('yyk_st_nonce', 'nonce');
            if (!class_exists('YYK_ST_Collector')) {
                wp_send_json(['success' => false, 'message' => '采集类未加载']);
                return;
            }
            $page = intval($_POST['page'] ?? 1);
            $limit = intval($_POST['limit'] ?? 20);
            $result = YYK_ST_Collector::get_instance()->fetch_reserve_list($page, $limit);
            wp_send_json($result);
        }
        
        public function ajax_st_collect_rankings() {
            check_ajax_referer('yyk_st_nonce', 'nonce');
            if (!class_exists('YYK_ST_Collector')) {
                wp_send_json(['success' => false, 'message' => '采集类未加载']);
                return;
            }
            $toptype = intval($_POST['toptype'] ?? 0);
            $days = intval($_POST['days'] ?? 7);
            $limit = intval($_POST['limit'] ?? 20);
            $result = YYK_ST_Collector::get_instance()->fetch_rankings($toptype, $days, $limit);
            wp_send_json($result);
        }
        
        public function ajax_st_collect_all() {
            check_ajax_referer('yyk_st_nonce', 'nonce');
            if (!class_exists('YYK_ST_Collector')) {
                wp_send_json(['success' => false, 'message' => '采集类未加载']);
                return;
            }
            $result = YYK_ST_Collector::get_instance()->collect_all();
            wp_send_json_success(['message' => '一键采集完成', 'data' => $result]);
        }
        
        public function ajax_st_save_settings() {
            check_ajax_referer('yyk_st_nonce', 'nonce');
            if (!class_exists('YYK_ST_Collector')) {
                wp_send_json(['success' => false, 'message' => '采集类未加载']);
                return;
            }
            $api_domain = sanitize_text_field($_POST['api_domain'] ?? '');
            $cps_id = sanitize_text_field($_POST['cps_id'] ?? '');
            YYK_ST_Collector::get_instance()->update_settings($api_domain, $cps_id);
            wp_send_json_success(['message' => '设置已保存']);
        }
        
        public function ajax_st_clear_logs() {
            check_ajax_referer('yyk_st_nonce', 'nonce');
            if (!class_exists('YYK_ST_Collector')) {
                wp_send_json(['success' => false, 'message' => '采集类未加载']);
                return;
            }
            YYK_ST_Collector::get_instance()->clear_logs();
            wp_send_json_success(['message' => '日志已清空']);
        }
        
        // ========== ST采集菜单 ==========
        
        public function add_st_collect_menu() {
            add_submenu_page(
                'edit.php?post_type=yyk_app_download',
                __('ST采集', 'yyk-app-download'),
                __('ST采集', 'yyk-app-download'),
                'manage_options',
                'yyk-st-collect',
                [$this, 'render_st_collect_page']
            );
        }
        
        public function render_st_collect_page() {
            if (!current_user_can('manage_options')) {
                wp_die(__('您没有权限访问此页面。', 'yyk-app-download'));
            }
            require_once YYK_APP_PLUGIN_DIR . 'admin/partials/st-collect-page.php';
        }
        
        // ========== 原有方法 ==========
        
        public function enqueue_public_assets() {
            wp_enqueue_style('yyk-app-public-style', YYK_APP_PLUGIN_URL . 'public/css/public-style.css', [], YYK_APP_VERSION);
            wp_enqueue_script('yyk-app-public-script', YYK_APP_PLUGIN_URL . 'public/js/public-script.js', ['jquery'], YYK_APP_VERSION, true);
            wp_localize_script('yyk-app-public-script', 'yykAppAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('yyk_app_nonce')
            ]);
        }
        
        public function enqueue_admin_assets($hook) {
            global $post_type;
            
            if ($post_type === 'yyk_app_download' || $hook === 'post.php' || $hook === 'post-new.php') {
                wp_enqueue_style('yyk-app-admin-style', YYK_APP_PLUGIN_URL . 'admin/css/admin-style.css', [], YYK_APP_VERSION);
                wp_enqueue_script('yyk-app-admin-script', YYK_APP_PLUGIN_URL . 'admin/js/admin-script.js', ['jquery'], YYK_APP_VERSION, true);
                wp_localize_script('yyk-app-admin-script', 'yykAppAdmin', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('yyk_app_admin_nonce')
                ]);
                if ($hook === 'post.php' || $hook === 'post-new.php') {
                    wp_enqueue_media();
                }
            }
            
            if ($hook === 'yyk_app_download_page_yyk-st-collect') {
                wp_enqueue_style('yyk-st-collect-style', YYK_APP_PLUGIN_URL . 'admin/css/st-collect.css', [], YYK_APP_VERSION);
                wp_enqueue_script('yyk-st-collect-script', YYK_APP_PLUGIN_URL . 'admin/js/st-collect.js', ['jquery'], YYK_APP_VERSION, true);
                wp_localize_script('yyk-st-collect-script', 'yykStAjax', [
                    'ajax_url' => admin_url('admin-ajax.php')
                ]);
            }
        }
        
        public function add_plugin_action_links($links) {
            $settings_link = '<a href="' . admin_url('edit.php?post_type=yyk_app_download&page=yyk-st-collect') . '">' . __('ST采集', 'yyk-app-download') . '</a>';
            $diagnostic_link = '<a href="' . admin_url('admin.php?page=yyk-app-diagnostic') . '">' . __('诊断', 'yyk-app-download') . '</a>';
            array_unshift($links, $settings_link);
            array_unshift($links, $diagnostic_link);
            return $links;
        }
        
        public function add_diagnostic_page() {
            add_menu_page(
                __('应用下载诊断', 'yyk-app-download'),
                __('应用下载诊断', 'yyk-app-download'),
                'manage_options',
                'yyk-app-diagnostic',
                [$this, 'render_diagnostic_page'],
                'dashicons-embed-generic',
                100
            );
        }
        
        public function render_diagnostic_page() {
            if (!current_user_can('manage_options')) {
                wp_die(__('您没有权限访问此页面。', 'yyk-app-download'));
            }
            if (class_exists('YYK_App_Diagnostic')) {
                YYK_App_Diagnostic::get_instance()->render_diagnostic_page();
            } else {
                echo '<div class="wrap"><h1>诊断页面</h1><p>诊断类未加载</p></div>';
            }
        }
    }
    
    // 初始化插件
    YYK_App_Download_Manager::get_instance();
    
    // 确保小工具被注册
    add_action('widgets_init', function() {
        if (class_exists('YYK_App_Widget')) {
            register_widget('YYK_App_Widget');
        }
    }, 99);
}