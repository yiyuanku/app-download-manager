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
            add_shortcode('yyk_video_player', [$this, 'video_player_shortcode']);
            add_shortcode('yyk_app_carousel', [$this, 'app_carousel_shortcode']);
            add_shortcode('yyk_logo_carousel', [$this, 'logo_carousel_shortcode']);
            
            // 记录日志
            if (current_user_can('manage_options')) {
                error_log('YYK应用下载管理器: 短代码已注册');
            }
        }
        
        public function video_player_shortcode($atts) {
            $atts = shortcode_atts([
                'id' => 0,
                'count' => 5,
            ], $atts, 'yyk_video_player');
            
            $app_id = intval($atts['id']);
            $video_count = intval($atts['count']);
            $video_list = [];
            
            if ($app_id) {
                $post = get_post($app_id);
                if ($post && 'yyk_app_download' === $post->post_type) {
                    $video = get_post_meta($app_id, '_yyk_st_video', true);
                    $game_bbs = get_post_meta($app_id, '_yyk_st_game_bbs', true);
                    if (empty($video) && !empty($game_bbs)) {
                        $video = $game_bbs;
                    }
                    if (!empty($video)) {
                        $video_list[] = [
                            'url' => $video,
                            'title' => get_the_title($app_id) . ' - 宣传视频',
                            'thumb' => get_post_meta($app_id, '_yyk_app_icon_url', true)
                        ];
                    }
                }
            }
            
            $args = [
                'post_type' => 'yyk_app_download',
                'posts_per_page' => $video_count,
                'orderby' => 'rand',
                'meta_query' => [
                    [
                        'key' => '_yyk_st_video',
                        'compare' => 'EXISTS'
                    ]
                ]
            ];
            if (!empty($video_list)) {
                $args['post__not_in'] = [$app_id];
                $args['posts_per_page'] = $video_count - 1;
            }
            
            $related_videos = new WP_Query($args);
            if ($related_videos->have_posts()) {
                while ($related_videos->have_posts()) {
                    $related_videos->the_post();
                    $rel_video = get_post_meta(get_the_ID(), '_yyk_st_video', true);
                    $rel_game_bbs = get_post_meta(get_the_ID(), '_yyk_st_game_bbs', true);
                    if (empty($rel_video) && !empty($rel_game_bbs)) {
                        $rel_video = $rel_game_bbs;
                    }
                    if (!empty($rel_video)) {
                        $video_list[] = [
                            'url' => $rel_video,
                            'title' => get_the_title() . ' - 宣传视频',
                            'thumb' => get_post_meta(get_the_ID(), '_yyk_app_icon_url', true)
                        ];
                    }
                }
                wp_reset_postdata();
            }
            
            if (empty($video_list)) {
                return '<div class="yyk-error">' . __('暂无视频可展示', 'yyk-app-download') . '</div>';
            }
            
            shuffle($video_list);
            $current_video = $video_list[0];
            $player_id = $app_id ? $app_id : 'player_' . rand(1000, 9999);
            
            ob_start();
            ?>
            <div class="yyk-video-player-wrapper">
                <div class="yyk-main-video">
                    <video id="yyk-main-video-<?php echo $player_id; ?>" src="<?php echo esc_url($current_video['url']); ?>" controls>
                        您的浏览器不支持视频播放。
                    </video>
                </div>
                <div class="yyk-video-list">
                    <div class="yyk-video-nav-btn yyk-video-nav-up">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7.41 15.41L12 10.83l4.59 4.58L18 14l-6-6-6 6z"/>
                        </svg>
                    </div>
                    <div class="yyk-video-list-container" id="yyk-video-list-<?php echo $player_id; ?>">
                        <?php foreach ($video_list as $index => $v): ?>
                        <div class="yyk-video-item <?php echo $index === 0 ? 'active' : ''; ?>" data-video="<?php echo esc_url($v['url']); ?>" data-title="<?php echo esc_attr($v['title']); ?>" data-player-id="<?php echo $player_id; ?>">
                            <div class="yyk-video-thumb">
                                <?php if (!empty($v['thumb'])): ?>
                                <img src="<?php echo esc_url($v['thumb']); ?>" alt="">
                                <?php endif; ?>
                                <div class="yyk-video-play-icon">
                                    <svg viewBox="0 0 24 24" fill="currentColor">
                                        <path d="M8 5v14l11-7z"/>
                                    </svg>
                                </div>
                            </div>
                            <div class="yyk-video-info">
                                <span class="yyk-video-name"><?php echo esc_html($v['title']); ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="yyk-video-nav-btn yyk-video-nav-down">
                        <svg viewBox="0 0 24 24" fill="currentColor">
                            <path d="M7.41 8.59L12 13.17l4.59-4.58L18 10l-6 6-6-6z"/>
                        </svg>
                    </div>
                </div>
            </div>
            <?php
            return ob_get_clean();
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
        
        public function app_carousel_shortcode($atts) {
            $atts = shortcode_atts([
                'title' => __('热门展示', 'yyk-app-download'),
                'count' => 12,
                'category' => '',
                'orderby' => 'date',
                'order' => 'DESC',
                'show_view_more' => true,
            ], $atts, 'yyk_app_carousel');
            
            ob_start();
            ?>
            <div class="yyk-partner-carousel-wrapper" style="background: white; border-radius: 16px; padding: 20px; margin-bottom: 30px; position: relative; box-shadow: 0 4px 12px rgba(0,0,0,0.08); border: 1px solid #e8e8e8;">
                <div style="text-align: center; margin-bottom: 15px; position: relative; display: flex; align-items: center; justify-content: center;">
                    <div class="yyk-hot-title-line yyk-hot-title-line-left"></div>
                    <h3 style="color: #333; font-size: 16px; font-weight: 600; margin: 0 15px; white-space: nowrap;"><?php echo esc_html($atts['title']); ?></h3>
                    <div class="yyk-hot-title-line yyk-hot-title-line-right"></div>
                </div>
                <?php 
                if (class_exists('YYK_App_Frontend')) {
                    echo YYK_App_Frontend::render_app_list([
                        'style' => 'compact',
                        'layout' => 'carousel',
                        'count' => intval($atts['count']),
                        'orderby' => sanitize_text_field($atts['orderby']),
                        'order' => sanitize_text_field($atts['order']),
                        'category' => sanitize_text_field($atts['category']),
                    ]);
                }
                ?>
                <div class="yyk-partner-left-fade"></div>
                <div class="yyk-partner-right-fade"></div>
                <?php if ($atts['show_view_more'] !== 'false'): ?>
                <a href="<?php echo get_post_type_archive_link('yyk_app_download'); ?>" class="yyk-partner-view-more">
                    查看全部
                </a>
                <?php endif; ?>
            </div>
            <?php
            echo ob_get_clean();
        }
        
        public function logo_carousel_shortcode($atts) {
            $atts = shortcode_atts([
                'title' => '',
                'subtitle' => '',
                'show_header' => 'true',
                'logo_style' => 'theme',
                'logo_size' => '100',
                'custom_logos' => '',
                'animation_speed' => '30',
                'pause_on_hover' => 'true',
            ], $atts, 'yyk_logo_carousel');
            
            if (class_exists('YYK_App_Widgets')) {
                $widget = new YYK_App_Logo_Widget();
                $widget_id = 'yyk-logo-' . uniqid();
                $instance = [
                    'title' => sanitize_text_field($atts['title']),
                    'subtitle' => sanitize_text_field($atts['subtitle']),
                    'show_header' => sanitize_text_field($atts['show_header']),
                    'logo_style' => sanitize_text_field($atts['logo_style']),
                    'logo_size' => intval($atts['logo_size']),
                    'custom_logos' => sanitize_textarea_field($atts['custom_logos']),
                    'animation_speed' => floatval($atts['animation_speed']),
                    'pause_on_hover' => sanitize_text_field($atts['pause_on_hover']),
                ];
                
                ob_start();
                $widget->widget([
                    'before_widget' => '<div id="' . esc_attr($widget_id) . '" class="yyk-widget-wrapper yyk-app-logo-carousel">',
                    'after_widget' => '</div>',
                ], $instance);
                return ob_get_clean();
            }
            
            return '<div class="yyk-error">' . __('前端类未加载', 'yyk-app-download') . '</div>';
        }
    }
}