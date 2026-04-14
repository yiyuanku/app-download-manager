<?php
/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：短代码模块
 =  📄 文件：class-shortcodes.php
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：短代码管理类，提供应用列表、单个应用、分类、ST游戏等短代码功能
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('YYK_App_Shortcodes')) {

    class YYK_App_Shortcodes {
        
        private static $instance = null;
        
        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        private function __construct() {}
        
        public function init() {
            add_shortcode('yyk_app', [$this, 'app_shortcode']);
            add_shortcode('yyk_app_list', [$this, 'app_list_shortcode']);
            add_shortcode('yyk_app_categories', [$this, 'app_categories_shortcode']);
            add_shortcode('yyk_st_games', [$this, 'st_games_shortcode']);
            add_shortcode('yyk_st_game', [$this, 'st_game_shortcode']);
            
            // 记录日志
            if (current_user_can('manage_options')) {
                error_log('YYK应用下载管理器: 短代码已注册');
            }
        }
        
        public function app_shortcode($atts) {
            $atts = shortcode_atts([
                'id' => 0,
                'style' => 'card',
                'icon_size' => 'medium',
            ], $atts, 'yyk_app');
            
            $app_id = intval($atts['id']);
            
            if (!$app_id) {
                return '<div class="yyk-error">' . __('请指定应用ID', 'yyk-app-download') . '</div>';
            }
            
            $post = get_post($app_id);
            
            if (!$post || 'yyk_app_download' !== $post->post_type) {
                return '<div class="yyk-error">' . __('应用不存在', 'yyk-app-download') . '</div>';
            }
            
            // 使用前端类的渲染方法
            if (class_exists('YYK_App_Frontend')) {
                return YYK_App_Frontend::render_app_card($app_id, $atts['style'], $atts['icon_size']);
            }
            
            return '<div class="yyk-error">' . __('前端类未加载', 'yyk-app-download') . '</div>';
        }
        
        public function app_list_shortcode($atts) {
            $atts = shortcode_atts([
                'style' => 'card', // card 或 gamebox
                'category' => '',
                'count' => 6,
                'columns' => 3,
                'orderby' => 'date',
                'order' => 'DESC',
                'layout' => 'grid', // grid 或 carousel
                'icon_size' => 'medium', // small, medium, large
            ], $atts, 'yyk_app_list');
            
            // 使用前端类的渲染方法
            if (class_exists('YYK_App_Frontend')) {
                return YYK_App_Frontend::render_app_list($atts);
            }
            
            return '<div class="yyk-error">' . __('前端类未加载', 'yyk-app-download') . '</div>';
        }
        
        public function app_categories_shortcode($atts) {
            $atts = shortcode_atts([
                'parent' => 0,
                'show_count' => true,
                'columns' => 2,
                'style' => 'list', // list 或 grid
            ], $atts, 'yyk_app_categories');
            
            $args = [
                'taxonomy' => 'yyk_app_category',
                'parent' => intval($atts['parent']),
                'hide_empty' => false,
            ];
            
            $categories = get_terms($args);
            
            ob_start();
            
            if (!empty($categories) && !is_wp_error($categories)) {
                $columns_class = 'yyk-cat-columns-' . min(4, max(1, intval($atts['columns'])));
                $style_class = 'yyk-cat-style-' . sanitize_html_class($atts['style']);
                
                echo '<div class="yyk-categories ' . esc_attr($columns_class) . ' ' . esc_attr($style_class) . '">';
                
                if ('grid' === $atts['style']) {
                    echo '<div class="yyk-cat-grid">';
                    foreach ($categories as $category) {
                        echo '<div class="yyk-cat-grid-item">';
                        echo '<a href="' . esc_url(get_term_link($category)) . '" class="yyk-cat-link">';
                        echo '<span class="yyk-cat-name">' . esc_html($category->name) . '</span>';
                        if ($atts['show_count']) {
                            echo '<span class="yyk-cat-count">' . $category->count . '</span>';
                        }
                        echo '</a>';
                        echo '</div>';
                    }
                    echo '</div>';
                } else {
                    echo '<ul>';
                    foreach ($categories as $category) {
                        $count = $atts['show_count'] ? ' <span class="yyk-cat-count">(' . $category->count . ')</span>' : '';
                        
                        echo '<li>';
                        echo '<a href="' . esc_url(get_term_link($category)) . '">';
                        echo esc_html($category->name) . $count;
                        echo '</a>';
                        echo '</li>';
                    }
                    echo '</ul>';
                }
                
                echo '</div>';
            } else {
                echo '<div class="yyk-no-categories">' . __('暂无分类', 'yyk-app-download') . '</div>';
            }
            
            return ob_get_clean();
        }
        
        public function st_game_shortcode($atts) {
            $atts = shortcode_atts([
                'id' => 0,
                'icon_size' => 'medium',
            ], $atts, 'yyk_st_game');
            
            $game_id = intval($atts['id']);
            
            if (!$game_id) {
                return '<div class="yyk-error">' . __('请指定游戏ID', 'yyk-app-download') . '</div>';
            }
            
            $post = get_post($game_id);
            
            if (!$post || 'yyk_app_download' !== $post->post_type) {
                return '<div class="yyk-error">' . __('ST游戏不存在', 'yyk-app-download') . '</div>';
            }
            
            if (class_exists('YYK_ST_Display')) {
                return YYK_ST_Display::render_st_game_card($game_id, $atts['icon_size']);
            }
            
            return '<div class="yyk-error">' . __('ST显示类未加载', 'yyk-app-download') . '</div>';
        }
        
        public function st_games_shortcode($atts) {
            $atts = shortcode_atts([
                'count' => 12,
                'columns' => 3,
                'orderby' => 'date',
                'order' => 'DESC',
                'icon_size' => 'medium',
            ], $atts, 'yyk_st_games');
            
            if (class_exists('YYK_ST_Display')) {
                return YYK_ST_Display::render_st_game_list($atts);
            }
            
            return '<div class="yyk-error">' . __('ST显示类未加载', 'yyk-app-download') . '</div>';
        }
    }
}