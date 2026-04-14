<?php
/**
 * Plugin Name: 应用下载管理器 (YYK)
 * Plugin URI: https://yourwebsite.com/
 * Description: 专业的应用下载管理插件，支持卡片样式和游戏盒子样式展示，集成ST手游采集
 * Version: 0.1.0
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
define('YYK_APP_VERSION', '1.1.2');
define('YYK_APP_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('YYK_APP_PLUGIN_URL', plugin_dir_url(__FILE__));
define('YYK_APP_ASSETS_URL', YYK_APP_PLUGIN_URL . 'assets/');

// 检查是否已定义类
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
            register_activation_hook(__FILE__, [$this, 'activate']);
            register_deactivation_hook(__FILE__, [$this, 'deactivate']);
            
            add_action('init', [$this, 'init'], 0);
            add_action('plugins_loaded', [$this, 'load_textdomain']);
            add_action('wp_enqueue_scripts', [$this, 'enqueue_public_assets']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_assets']);
            add_action('admin_menu', [$this, 'add_admin_menu']);
            add_action('widgets_init', [$this, 'register_widgets']);
            
            add_filter('plugin_action_links_' . plugin_basename(__FILE__), [$this, 'add_plugin_action_links']);
            
            // 添加ST采集器AJAX处理
            add_action('wp_ajax_yyk_record_download', [$this, 'handle_download_record']);
            add_action('wp_ajax_nopriv_yyk_record_download', [$this, 'handle_download_record']);
        }
        
        public function activate() {
            $this->include_files();
            
            // 先注册文章类型和分类法
            if (class_exists('YYK_App_Post_Type')) {
                $post_type_instance = YYK_App_Post_Type::get_instance();
                $post_type_instance->register_post_type();
                $post_type_instance->register_taxonomy();
            }
            
            $this->create_default_categories();
            
            update_option('yyk_app_default_style', 'card');
            update_option('yyk_app_items_per_page', 12);
            update_option('yyk_app_version', YYK_APP_VERSION);
            
            // 激活ST采集器
            if (class_exists('YYK_ST_Collector')) {
                YYK_ST_Collector::get_instance()->activate();
            }
            
            error_log('YYK应用下载管理器已激活 v' . YYK_APP_VERSION);
        }
        
        public function deactivate() {
            flush_rewrite_rules();
            error_log('YYK应用下载管理器已停用');
        }
        
        public function load_textdomain() {
            load_plugin_textdomain('yyk-app-download', false, dirname(plugin_basename(__FILE__)) . '/languages/');
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
            
            $this->include_files();
            
            // 先初始化文章类型
            if (class_exists('YYK_App_Post_Type')) {
                YYK_App_Post_Type::get_instance()->init();
                error_log('YYK: 文章类型已初始化');
            }

            // 自动刷新重写规则
            $this->flush_rewrite_rules_on_init();
            
            // 初始化其他模块
            if (class_exists('YYK_App_Meta_Boxes')) {
                YYK_App_Meta_Boxes::get_instance()->init();
                error_log('YYK: 元字段已初始化');
            }
            
            if (class_exists('YYK_App_Shortcodes')) {
                YYK_App_Shortcodes::get_instance()->init();
                error_log('YYK: 短代码已初始化');
            }
            
            if (class_exists('YYK_App_Frontend')) {
                YYK_App_Frontend::get_instance()->init();
                error_log('YYK: 前端已初始化');
            }
            
            if (class_exists('YYK_ST_Collector')) {
                YYK_ST_Collector::get_instance()->init();
                error_log('YYK: ST采集器已初始化');
            }
            
            if (class_exists('YYK_ST_Display')) {
                YYK_ST_Display::get_instance()->init();
                error_log('YYK: ST显示已初始化');
            }
            
            if (class_exists('YYK_App_Diagnostic')) {
                YYK_App_Diagnostic::get_instance();
                error_log('YYK: 诊断工具已初始化');
            }
            
            error_log('YYK应用下载管理器: 初始化完成');
        }
        
        private function flush_rewrite_rules_on_init() {
            $saved_version = get_option('yyk_app_version');
            
            if ($saved_version !== YYK_APP_VERSION) {
                flush_rewrite_rules();
                update_option('yyk_app_version', YYK_APP_VERSION);
                error_log('YYK: 重写规则已刷新');
            }
        }
        
        public function register_widgets() {
            if (class_exists('YYK_App_Widget')) {
                register_widget('YYK_App_Widget');
                error_log('YYK: 小工具已注册');
            }
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
                'includes/class-st-display.php',
            ];
            
            foreach ($files as $file) {
                $file_path = YYK_APP_PLUGIN_DIR . $file;
                if (file_exists($file_path)) {
                    require_once $file_path;
                } else {
                    error_log('YYK错误：找不到文件 ' . $file);
                    add_action('admin_notices', function() use ($file) {
                        if (current_user_can('manage_options')) {
                            echo '<div class="notice notice-error"><p>';
                            printf(__('应用下载管理器错误：找不到文件 %s', 'yyk-app-download'), esc_html($file));
                            echo '</p></div>';
                        }
                    });
                }
            }
        }
        
        public function enqueue_public_assets() {
            // 前端样式
            wp_enqueue_style(
                'yyk-app-public-style',
                YYK_APP_PLUGIN_URL . 'public/css/public-style.css',
                [],
                YYK_APP_VERSION
            );
            
            // 前端脚本
            wp_enqueue_script(
                'yyk-app-public-script',
                YYK_APP_PLUGIN_URL . 'public/js/public-script.js',
                ['jquery'],
                YYK_APP_VERSION,
                true
            );
            
            // 本地化脚本
            wp_localize_script('yyk-app-public-script', 'yykAppAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('yyk_app_nonce'),
                'loading_text' => __('加载中...', 'yyk-app-download'),
                'error_text' => __('加载失败，请重试', 'yyk-app-download')
            ]);
            
            // 添加平台图标内联CSS
            $this->add_platform_icons_css();
        }
        
        private function add_platform_icons_css() {
            $android_icon = YYK_APP_ASSETS_URL . 'images/安卓.svg';
            $ios_icon = YYK_APP_ASSETS_URL . 'images/ios.svg';
            $windows_icon = YYK_APP_ASSETS_URL . 'images/Windows.svg';
            
            $custom_css = "
                /* ========== 平台图标样式 ========== */
                .yyk-platform-icon.android::before {
                    background-image: url('{$android_icon}') !important;
                }
                .yyk-platform-icon.ios::before {
                    background-image: url('{$ios_icon}') !important;
                }
                .yyk-platform-icon.pc::before {
                    background-image: url('{$windows_icon}') !important;
                }
                .yyk-platform-icon.all::before {
                    background-image: url('{$android_icon}'), url('{$ios_icon}'), url('{$windows_icon}') !important;
                    background-size: 20px 20px, 20px 20px, 20px 20px !important;
                    background-position: 0 center, 22px center, 44px center !important;
                    background-repeat: no-repeat !important;
                    width: 68px !important;
                    height: 20px !important;
                }
            ";
            
            wp_add_inline_style('yyk-app-public-style', $custom_css);
        }
        
        public function enqueue_admin_assets($hook) {
            global $post_type;
            
            if ($this->is_app_download_page($hook, $post_type)) {
                wp_enqueue_style(
                    'yyk-app-admin-style',
                    YYK_APP_PLUGIN_URL . 'admin/css/admin-style.css',
                    [],
                    YYK_APP_VERSION
                );
                
                wp_enqueue_script(
                    'yyk-app-admin-script',
                    YYK_APP_PLUGIN_URL . 'admin/js/admin-script.js',
                    ['jquery'],
                    YYK_APP_VERSION,
                    true
                );
                
                wp_localize_script('yyk-app-admin-script', 'yykAppAdmin', [
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('yyk_app_admin_nonce')
                ]);
                
                if ('post.php' === $hook || 'post-new.php' === $hook) {
                    wp_enqueue_media();
                }
            }
            
            if ('toplevel_page_yyk-app-diagnostic' === $hook || false !== strpos($hook, 'yyk-st') || 'toplevel_page_yyk-app-dashboard' === $hook) {
                wp_enqueue_style(
                    'yyk-app-admin-style',
                    YYK_APP_PLUGIN_URL . 'admin/css/admin-style.css',
                    [],
                    YYK_APP_VERSION
                );
            }
        }
        
        private function is_app_download_page($hook, $post_type) {
            return 'yyk_app_download' === $post_type || 
                   'edit.php' === $hook || 
                   'post.php' === $hook || 
                   'post-new.php' === $hook ||
                   false !== strpos($hook, 'yyk_app') ||
                   false !== strpos($hook, 'yyk-st');
        }
        
        public function add_plugin_action_links($links) {
            $settings_link = '<a href="' . admin_url('edit.php?post_type=yyk_app_download&page=yyk-app-diagnostic') . '">' . __('诊断', 'yyk-app-download') . '</a>';
            $st_link = '<a href="' . admin_url('edit.php?post_type=yyk_app_download&page=yyk-st-collector') . '">' . __('ST采集', 'yyk-app-download') . '</a>';
            array_unshift($links, $st_link);
            array_unshift($links, $settings_link);
            return $links;
        }
        
        public function add_admin_menu() {
            add_menu_page(
                '应用下载管理',
                '应用下载管理',
                'manage_options',
                'yyk-app-dashboard',
                [$this, 'render_dashboard'],
                'dashicons-download',
                30
            );
            
            add_submenu_page(
                'yyk-app-dashboard',
                '数据统计',
                '数据统计',
                'manage_options',
                'yyk-app-dashboard',
                [$this, 'render_dashboard']
            );
        }
        
        public function render_dashboard() {
            global $wpdb;
            
            // 获取统计数据
            $total_apps = wp_count_posts('yyk_app_download')->publish;
            $total_categories = wp_count_terms('yyk_app_category', ['hide_empty' => false]);
            
            // 获取ST采集数据
            $st_table = $wpdb->prefix . 'yyk_st_games';
            $st_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$st_table'");
            $st_total = $st_table_exists ? $wpdb->get_var("SELECT COUNT(*) FROM $st_table") : 0;
            $st_published = $wpdb->get_var("SELECT COUNT(*) FROM $st_table WHERE post_id > 0");
            $st_unpublished = $st_total - $st_published;
            
            // 获取分类统计
            $categories = get_terms([
                'taxonomy' => 'yyk_app_category',
                'hide_empty' => true,
                'orderby' => 'count',
                'order' => 'DESC',
                'number' => 10
            ]);
            
            // 获取最新游戏
            $recent_apps = get_posts([
                'post_type' => 'yyk_app_download',
                'post_status' => 'publish',
                'posts_per_page' => 10,
                'orderby' => 'date',
                'order' => 'DESC'
            ]);
            
            ?>
            <div class="wrap">
                <h1>应用下载管理 - 数据统计</h1>
                
                <div class="yyk-dashboard-stats" style="margin: 20px 0;">
                    <div class="yyk-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block; width: 23%; margin-right: 2%; vertical-align: top;">
                        <h3 style="margin: 0 0 10px 0; color: #666;">已发布游戏</h3>
                        <div style="font-size: 36px; font-weight: bold; color: #2271b1;"><?php echo number_format($total_apps); ?></div>
                        <p style="margin: 10px 0 0 0; color: #999;">WordPress文章</p>
                    </div>
                    
                    <div class="yyk-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block; width: 23%; margin-right: 2%; vertical-align: top;">
                        <h3 style="margin: 0 0 10px 0; color: #666;">游戏分类</h3>
                        <div style="font-size: 36px; font-weight: bold; color: #2271b1;"><?php echo number_format($total_categories); ?></div>
                        <p style="margin: 10px 0 0 0; color: #999;">分类总数</p>
                    </div>
                    
                    <div class="yyk-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block; width: 23%; margin-right: 2%; vertical-align: top;">
                        <h3 style="margin: 0 0 10px 0; color: #666;">ST采集总数</h3>
                        <div style="font-size: 36px; font-weight: bold; color: #00a32a;"><?php echo number_format($st_total); ?></div>
                        <p style="margin: 10px 0 0 0; color: #999;">已采集游戏</p>
                    </div>
                    
                    <div class="yyk-stat-card" style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block; width: 23%; vertical-align: top;">
                        <h3 style="margin: 0 0 10px 0; color: #666;">未发布游戏</h3>
                        <div style="font-size: 36px; font-weight: bold; color: #d63638;"><?php echo number_format($st_unpublished); ?></div>
                        <p style="margin: 10px 0 0 0; color: #999;">待发布</p>
                    </div>
                </div>
                
                <div style="margin-top: 30px;">
                    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block; width: 48%; margin-right: 2%; vertical-align: top;">
                        <h2 style="margin-top: 0;">热门分类 TOP 10</h2>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>分类名称</th>
                                    <th style="text-align: right;">游戏数量</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($categories)): ?>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo get_term_link($category); ?>" target="_blank">
                                                    <?php echo esc_html($category->name); ?>
                                                </a>
                                            </td>
                                            <td style="text-align: right;"><?php echo number_format($category->count); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2">暂无分类数据</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); display: inline-block; width: 48%; vertical-align: top;">
                        <h2 style="margin-top: 0;">最新发布游戏</h2>
                        <table class="wp-list-table widefat fixed striped">
                            <thead>
                                <tr>
                                    <th>游戏名称</th>
                                    <th style="text-align: right;">发布时间</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (!empty($recent_apps)): ?>
                                    <?php foreach ($recent_apps as $app): ?>
                                        <tr>
                                            <td>
                                                <a href="<?php echo get_permalink($app->ID); ?>" target="_blank">
                                                    <?php echo esc_html($app->post_title); ?>
                                                </a>
                                            </td>
                                            <td style="text-align: right;"><?php echo get_the_date('Y-m-d H:i', $app->ID); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="2">暂无游戏数据</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div style="margin-top: 30px;">
                    <h2>快捷操作</h2>
                    <div style="background: #fff; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                        <a href="<?php echo admin_url('edit.php?post_type=yyk_app_download'); ?>" class="button button-primary" style="margin-right: 10px;">管理游戏</a>
                        <a href="<?php echo admin_url('edit-tags.php?taxonomy=yyk_app_category&post_type=yyk_app_download'); ?>" class="button" style="margin-right: 10px;">管理分类</a>
                        <a href="<?php echo admin_url('edit.php?post_type=yyk_app_download&page=yyk-st-collector'); ?>" class="button" style="margin-right: 10px;">ST游戏采集</a>
                        <a href="<?php echo admin_url('edit.php?post_type=yyk_app_download&page=yyk-app-diagnostic'); ?>" class="button">诊断工具</a>
                    </div>
                </div>
            </div>
            <?php
        }
        
        public function handle_download_record() {
            check_ajax_referer('yyk_app_nonce', 'nonce');
            
            $app_id = isset($_POST['app_id']) ? intval($_POST['app_id']) : 0;
            
            if ($app_id > 0) {
                $download_count = get_post_meta($app_id, '_yyk_app_download_count', true);
                $download_count = $download_count ? intval($download_count) + 1 : 1;
                update_post_meta($app_id, '_yyk_app_download_count', $download_count);
                
                wp_send_json_success([
                    'download_count' => $download_count,
                    'message' => __('下载记录已更新', 'yyk-app-download')
                ]);
            }
            
            wp_send_json_error(['message' => __('无效的应用ID', 'yyk-app-download')]);
        }
    }
    
    // 初始化插件
    YYK_App_Download_Manager::get_instance();
}