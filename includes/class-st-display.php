<?php
/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：ST显示模块
 =  📄 文件：class-st-display.php
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：ST游戏数据显示类，负责将采集的游戏数据在前台展示
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('YYK_ST_Display')) {

    class YYK_ST_Display {
        
        private static $instance = null;
        private $db;
        
        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        private function __construct() {
            $this->db = $GLOBALS['wpdb'];
        }
        
        public function init() {
            // 使用现有插件的文章类型和分类法
            add_filter('template_include', [$this, 'template_include']);
            add_filter('manage_yyk_app_download_posts_columns', [$this, 'add_custom_columns']);
            add_action('manage_yyk_app_download_posts_custom_column', [$this, 'display_custom_columns'], 10, 2);
        }
        
        public function template_include($template) {
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
        
        public function add_custom_columns($columns) {
            $new_columns = [];
            foreach ($columns as $key => $value) {
                $new_columns[$key] = $value;
                if ('title' === $key) {
                    $new_columns['yyk_st_game_id'] = __('游戏ID', 'yyk-app-download');
                    $new_columns['yyk_st_discount'] = __('折扣', 'yyk-app-download');
                }
            }
            return $new_columns;
        }
        
        public function display_custom_columns($column, $post_id) {
            switch ($column) {
                case 'yyk_st_game_id':
                    $game_id = get_post_meta($post_id, '_yyk_st_game_id', true);
                    echo $game_id ? esc_html($game_id) : '<em>—</em>';
                    break;
                case 'yyk_st_discount':
                    $discount = get_post_meta($post_id, '_yyk_st_discount', true);
                    if ($discount && $discount < 10) {
                        echo '<span style="color:#e74c3c;font-weight:bold;">' . esc_html($discount) . '折</span>';
                    } else {
                        echo '<em>—</em>';
                    }
                    break;
            }
        }
        
        /**
         * 获取游戏完整详情（用于前台展示）
         */
        public static function get_game_details($post_id) {
            $details = [
                'game_id' => get_post_meta($post_id, '_yyk_st_game_id', true),
                'size' => get_post_meta($post_id, '_yyk_app_size', true),
                'version' => get_post_meta($post_id, '_yyk_app_version', true),
                'download_url' => get_post_meta($post_id, '_yyk_app_download_url', true),
                'android_url' => get_post_meta($post_id, '_yyk_app_android_url', true),
                'ios_url' => get_post_meta($post_id, '_yyk_app_ios_url', true),
                'platform' => get_post_meta($post_id, '_yyk_app_platform', true),
                'is_hot' => get_post_meta($post_id, '_yyk_app_is_hot', true),
                'is_new' => get_post_meta($post_id, '_yyk_app_is_new', true),
                'update_date' => get_post_meta($post_id, '_yyk_app_update_date', true),
                'download_count' => get_post_meta($post_id, '_yyk_app_download_count', true),
                // ST特有字段
                'game_type' => get_post_meta($post_id, '_yyk_st_game_type', true),
                'discount' => get_post_meta($post_id, '_yyk_st_discount', true),
                'welfare_tags' => get_post_meta($post_id, '_yyk_st_welfare_tags', true),
                'short_intro' => get_post_meta($post_id, '_yyk_st_short_intro', true),
                'fanli' => get_post_meta($post_id, '_yyk_st_fanli', true),
                'vip_intro' => get_post_meta($post_id, '_yyk_st_vip_intro', true),
                'photos' => get_post_meta($post_id, '_yyk_st_photos', true),
            ];
            return $details;
        }
        
        /**
         * 渲染五宣图
         */
        public static function render_photos($post_id) {
            $photos = get_post_meta($post_id, '_yyk_st_photos', true);
            if (empty($photos) || !is_array($photos)) {
                return '';
            }
            
            $html = '<div class="yyk-game-photos">';
            $html .= '<h3>' . __('游戏截图', 'yyk-app-download') . '</h3>';
            $html .= '<div class="yyk-photos-grid">';
            foreach ($photos as $photo) {
                $html .= '<div class="yyk-photo-item">';
                $html .= '<img src="' . esc_url($photo) . '" alt="' . __('游戏截图', 'yyk-app-download') . '" loading="lazy">';
                $html .= '</div>';
            }
            $html .= '</div>';
            $html .= '</div>';
            
            return $html;
        }
        
        /**
         * 渲染福利标签
         */
        public static function render_welfare_tags($post_id) {
            $tags = get_post_meta($post_id, '_yyk_st_welfare_tags', true);
            if (empty($tags)) {
                return '';
            }
            
            if (is_string($tags)) {
                $tags = maybe_unserialize($tags);
            }
            
            if (empty($tags) || !is_array($tags)) {
                return '';
            }
            
            $html = '<div class="yyk-welfare-tags">';
            foreach ($tags as $tag) {
                $html .= '<span class="yyk-welfare-tag">' . esc_html($tag) . '</span>';
            }
            $html .= '</div>';
            
            return $html;
        }
        
        /**
         * 渲染折扣标签
         */
        public static function render_discount_tag($post_id) {
            $discount = get_post_meta($post_id, '_yyk_st_discount', true);
            if ($discount && $discount < 10) {
                return '<span class="yyk-discount-tag">' . esc_html($discount) . __('折', 'yyk-app-download') . '</span>';
            }
            return '';
        }
    }
}