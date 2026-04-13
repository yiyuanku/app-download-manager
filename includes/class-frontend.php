<?php
/**
 * 应用下载前端显示类
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('YYK_App_Frontend')) {

    class YYK_App_Frontend {
        
        private static $instance = null;
        
        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        private function __construct() {
            // 空构造函数，由主文件统一初始化
        }
        
        /**
         * 初始化前端类
         */
        public function init() {
            // 模板重写
            add_filter('template_include', [$this, 'template_include']);
            
            // AJAX处理
            add_action('wp_ajax_yyk_record_download', [$this, 'record_download']);
            add_action('wp_ajax_nopriv_yyk_record_download', [$this, 'record_download']);
            
            // 加载前端样式和脚本
            add_action('wp_enqueue_scripts', [$this, 'enqueue_styles']);
            add_action('wp_enqueue_scripts', [$this, 'enqueue_scripts']);
        }
        
        public function template_include($template) {
            // 使用插件常量获取路径
            if (is_singular('yyk_app_download')) {
                $custom_template = YYK_APP_PLUGIN_DIR . 'public/templates/single-app.php';
                if (file_exists($custom_template)) {
                    return $custom_template;
                }
            }
            
            if (is_post_type_archive('yyk_app_download') || is_tax('yyk_app_category')) {
                $archive_template = YYK_APP_PLUGIN_DIR . 'public/templates/archive-app.php';
                if (file_exists($archive_template)) {
                    return $archive_template;
                }
            }
            
            return $template;
        }
        
        /**
         * 加载前端样式
         */
        public function enqueue_styles() {
            // 总是加载公共样式，确保小工具也能显示样式
            wp_enqueue_style(
                'yyk-app-public-style',
                YYK_APP_PLUGIN_URL . 'public/css/public-style.css',
                [],
                '1.0.5',
                'all'
            );
            
            // 如果是详情页，添加额外样式
            if (is_singular('yyk_app_download')) {
                wp_add_inline_style('yyk-app-public-style', '
                    .yyk-single-app-container {
                        margin: 30px auto;
                        padding: 20px;
                    }
                    @media (max-width: 768px) {
                        .yyk-single-app-container {
                            margin: 15px auto;
                            padding: 15px;
                        }
                    }
                ');
            }
            
            // 如果是归档页，确保样式优先级最高
            if (is_post_type_archive('yyk_app_download') || is_tax('yyk_app_category')) {
                wp_add_inline_style('yyk-app-public-style', '
                    /* 强制归档页网格布局 */
                    .yyk-archive-container .yyk-app-grid {
                        display: grid !important;
                        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)) !important;
                        gap: 25px !important;
                        margin-bottom: 40px !important;
                    }
                    
                    /* 确保卡片显示正常 */
                    .yyk-archive-container .yyk-template-card {
                        width: 100% !important;
                        margin-bottom: 0 !important;
                    }
                    
                    /* 归档页容器样式 */
                    .yyk-archive-container {
                        max-width: 1200px !important;
                        margin: 30px auto !important;
                        padding: 20px !important;
                    }
                    
                    @media (max-width: 768px) {
                        .yyk-archive-container {
                            margin: 15px auto !important;
                            padding: 15px !important;
                        }
                        
                        .yyk-archive-container .yyk-app-grid {
                            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr)) !important;
                            gap: 15px !important;
                        }
                    }
                    
                    @media (max-width: 576px) {
                        .yyk-archive-container .yyk-app-grid {
                            grid-template-columns: 1fr !important;
                        }
                    }
                ');
            }
        }
        
        /**
         * 加载前端脚本
         */
        public function enqueue_scripts() {
            // 加载公共脚本
            wp_enqueue_script(
                'yyk-app-public-script',
                YYK_APP_PLUGIN_URL . 'public/js/public-script.js',
                ['jquery'],
                '1.0.5',
                true
            );
            
            // 本地化脚本，传递参数到JavaScript
            wp_localize_script('yyk-app-public-script', 'yykAppAjax', [
                'ajax_url' => admin_url('admin-ajax.php'),
                'nonce'    => wp_create_nonce('yyk_app_nonce'),
                'loading_text' => __('下载中...', 'yyk-app-download'),
                'error_text'   => __('下载失败，请重试', 'yyk-app-download')
            ]);
        }
        
        public function record_download() {
            // 安全检查
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
            
            wp_send_json_error([
                'message' => __('无效的应用ID', 'yyk-app-download')
            ]);
        }
        
        /**
         * 获取模板文件
         */
        public static function get_template($template_name, $args = []) {
            if (!empty($args) && is_array($args)) {
                extract($args);
            }
            
            // 使用插件常量获取路径
            $template_path = YYK_APP_PLUGIN_DIR . 'public/templates/' . $template_name;
            
            if (file_exists($template_path)) {
                include $template_path;
            } else {
                // 输出错误信息（仅管理员可见）
                if (current_user_can('manage_options')) {
                    echo '<div class="yyk-error" style="border:2px solid red; padding:10px; background:#ffe6e6; margin:10px 0;">';
                    echo '<strong>模板文件错误：</strong><br>';
                    echo '模板文件: ' . esc_html($template_name) . '<br>';
                    echo '完整路径: ' . esc_html($template_path) . '<br>';
                    echo '文件存在: ' . (file_exists($template_path) ? '是' : '否') . '<br>';
                    echo '</div>';
                }
            }
        }
        
        /**
         * 获取应用图标URL（支持尺寸选择）
         */
        public static function get_app_icon_url($post_id, $size = 'medium') {
            $app_icon_id = get_post_meta($post_id, '_yyk_app_icon_id', true);
            $app_icon_url = get_post_meta($post_id, '_yyk_app_icon_url', true);
            $icon_url = '';
            
            if ($app_icon_url) {
                // 使用远程图标URL
                $icon_url = $app_icon_url;
            } elseif ($app_icon_id) {
                // 根据图标尺寸获取不同大小的图片
                switch ($size) {
                    case 'small':
                        $icon_url = wp_get_attachment_image_url($app_icon_id, 'thumbnail');
                        break;
                    case 'large':
                        $icon_url = wp_get_attachment_image_url($app_icon_id, 'large');
                        break;
                    case 'medium':
                    default:
                        $icon_url = wp_get_attachment_image_url($app_icon_id, 'medium');
                        break;
                }
            }
            
            if (!$icon_url) {
                // 如果没有图标，使用默认图标
                $default_icon_path = YYK_APP_PLUGIN_DIR . 'assets/images/default-icon.png';
                $default_icon_url = YYK_APP_PLUGIN_URL . 'assets/images/default-icon.png';
                
                if (file_exists($default_icon_path)) {
                    $icon_url = $default_icon_url;
                } else {
                    $icon_url = 'https://via.placeholder.com/150/0073aa/ffffff?text=APP';
                }
            }
            
            return $icon_url;
        }
        
        /**
         * 渲染应用卡片
         */
        public static function render_app_card($post_id, $style = 'card', $icon_size = 'medium') {
            $post = get_post($post_id);
            
            if (!$post || 'yyk_app_download' !== $post->post_type) {
                return '';
            }
            
            // 获取应用图标URL
            $icon_url = self::get_app_icon_url($post_id, $icon_size);
            
            $args = [
                'post' => $post,
                'post_id' => $post_id,
                'version' => get_post_meta($post_id, '_yyk_app_version', true),
                'size' => get_post_meta($post_id, '_yyk_app_size', true),
                'developer' => get_post_meta($post_id, '_yyk_app_developer', true),
                'download_url' => get_post_meta($post_id, '_yyk_app_download_url', true),
                'is_hot' => get_post_meta($post_id, '_yyk_app_is_hot', true),
                'is_recommend' => get_post_meta($post_id, '_yyk_app_is_recommend', true),
                'is_new' => get_post_meta($post_id, '_yyk_app_is_new', true),
                'icon_size' => $icon_size,
                'default_icon_url' => $icon_url
            ];
            
            // 开始缓冲输出
            ob_start();
            
            if ('gamebox' === $style) {
                self::get_template('gamebox.php', $args);
            } else {
                self::get_template('card.php', $args);
            }
            
            // 获取并返回缓冲内容
            return ob_get_clean();
        }
        
        /**
         * 渲染应用列表（用于首页）
         */
        public static function render_app_list($args = []) {
            $default_args = [
                'style' => 'card',
                'category' => '',
                'count' => 6,
                'columns' => 3,
                'orderby' => 'date',
                'order' => 'DESC',
                'layout' => 'grid',
                'icon_size' => 'medium',
            ];
            
            $args = wp_parse_args($args, $default_args);
            
            $query_args = [
                'post_type' => 'yyk_app_download',
                'posts_per_page' => intval($args['count']),
                'post_status' => 'publish',
                'orderby' => $args['orderby'],
                'order' => $args['order'],
            ];
            
            if (!empty($args['category'])) {
                if (is_numeric($args['category'])) {
                    // 如果是数字，按分类ID查询
                    $query_args['tax_query'] = [[
                        'taxonomy' => 'yyk_app_category',
                        'field' => 'term_id',
                        'terms' => intval($args['category']),
                    ]];
                } else {
                    // 如果是字符串，按分类slug查询
                    $query_args['tax_query'] = [[
                        'taxonomy' => 'yyk_app_category',
                        'field' => 'slug',
                        'terms' => sanitize_text_field($args['category']),
                    ]];
                }
            }
            
            $query = new WP_Query($query_args);
            
            ob_start();
            
            if ($query->have_posts()) {
                $columns_class = 'yyk-columns-' . min(6, max(1, intval($args['columns'])));
                $style_class = 'yyk-style-' . sanitize_html_class($args['style']);
                $layout_class = 'yyk-layout-' . sanitize_html_class($args['layout']);
                $icon_class = 'yyk-icon-' . sanitize_html_class($args['icon_size']);
                
                echo '<div class="yyk-widget-container ' . esc_attr($layout_class) . ' ' . esc_attr($columns_class) . ' ' . esc_attr($style_class) . ' ' . esc_attr($icon_class) . '">';
                
                if ($args['layout'] === 'list') {
                    echo '<div class="yyk-widget-list">';
                    while ($query->have_posts()) {
                        $query->the_post();
                        echo self::render_app_card(get_the_ID(), $args['style'], $args['icon_size']);
                    }
                    echo '</div>';
                } else {
                    echo '<div class="yyk-widget-grid">';
                    while ($query->have_posts()) {
                        $query->the_post();
                        echo self::render_app_card(get_the_ID(), $args['style'], $args['icon_size']);
                    }
                    echo '</div>';
                }
                
                echo '</div>';
            } else {
                echo '<div class="yyk-no-apps">' . __('暂无应用', 'yyk-app-download') . '</div>';
            }
            
            wp_reset_postdata();
            return ob_get_clean();
        }
    }
}