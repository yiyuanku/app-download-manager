<?php
/**
 * 应用下载元字段管理类
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('YYK_App_Meta_Boxes')) {

    class YYK_App_Meta_Boxes {
        
        private static $instance = null;
        
        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        private function __construct() {}
        
        public function init() {
            add_action('add_meta_boxes', [$this, 'add_meta_boxes']);
            add_action('save_post', [$this, 'save_meta_boxes'], 10, 2);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
        }
        
        public function enqueue_admin_scripts($hook) {
            global $post_type;
            
            // 只在应用编辑页面加载
            if (($hook == 'post.php' || $hook == 'post-new.php') && $post_type == 'yyk_app_download') {
                // 加载媒体上传相关脚本
                wp_enqueue_media();
                
                // 加载自定义脚本
                wp_enqueue_script(
                    'yyk-app-admin-script',
                    plugins_url('../admin/js/admin-script.js', __FILE__),
                    ['jquery'],
                    '1.0.0',
                    true
                );
                
                // 本地化脚本，传递变量到JavaScript
                wp_localize_script('yyk-app-admin-script', 'yyk_admin_vars', [
                    'default_icon_url' => plugins_url('../assets/images/default-icon.png', __FILE__),
                    'ajax_url' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('yyk_app_admin_nonce')
                ]);
                
                // 加载样式
                wp_enqueue_style(
                    'yyk-app-admin-style',
                    plugins_url('../admin/css/admin-style.css', __FILE__)
                );
            }
        }
        
        public function render_app_icon_meta_box($post) {
            // 获取已保存的图标ID
            $app_icon_id = get_post_meta($post->ID, '_yyk_app_icon_id', true);
            
            // 安全验证
            wp_nonce_field('save_app_icon', 'app_icon_nonce');
            ?>
            <div class="yyk-app-icon-upload">
                <div class="yyk-app-icon-preview" style="margin-bottom: 10px;">
                    <?php if ($app_icon_id) : ?>
                        <?php echo wp_get_attachment_image($app_icon_id, 'thumbnail', false, ['style' => 'max-width:150px;height:auto;']); ?>
                    <?php else : ?>
                        <img src="<?php echo plugins_url('../assets/images/default-icon.png', __FILE__); ?>" 
                             style="max-width:150px;height:auto;" alt="默认图标">
                    <?php endif; ?>
                </div>
                
                <input type="hidden" id="yyk_app_icon_id" name="yyk_app_icon_id" value="<?php echo esc_attr($app_icon_id); ?>">
                
                <button type="button" class="button yyk-upload-icon-button" id="yyk_upload_icon_btn">
                    <?php echo $app_icon_id ? '更换图标' : '上传图标'; ?>
                </button>
                
                <?php if ($app_icon_id) : ?>
                    <button type="button" class="button yyk-remove-icon-button" id="yyk_remove_icon_btn">
                        移除图标
                    </button>
                <?php endif; ?>
                
                <p class="description">建议尺寸：512×512 像素，支持 PNG、JPG 格式</p>
            </div>
            <?php
        }
        
        public function add_meta_boxes() {
            // 添加应用图标元框 - 修正文章类型为 yyk_app_download
            add_meta_box(
                'yyk_app_icon_meta_box',          // 元框ID
                '应用图标',                         // 元框标题
                [$this, 'render_app_icon_meta_box'], // 回调函数
                'yyk_app_download',               // 文章类型（修正为正确的）
                'side',                           // 位置（侧边栏）
                'high'                            // 优先级
            );
            
            add_meta_box(
                'yyk_app_info',
                __('应用信息', 'yyk-app-download'),
                [$this, 'render_app_info_metabox'],
                'yyk_app_download',
                'normal',
                'high'
            );
            
            add_meta_box(
                'yyk_st_extra',
                __('ST采集字段', 'yyk-app-download'),
                [$this, 'render_st_extra_metabox'],
                'yyk_app_download',
                'normal',
                'high'
            );
            
            add_meta_box(
                'yyk_app_links',
                __('下载链接', 'yyk-app-download'),
                [$this, 'render_download_links_metabox'],
                'yyk_app_download',
                'normal',
                'high'
            );
            
            add_meta_box(
                'yyk_app_status',
                __('应用状态', 'yyk-app-download'),
                [$this, 'render_status_metabox'],
                'yyk_app_download',
                'side',
                'high'
            );
        }
        
        public function render_app_info_metabox($post) {
            wp_nonce_field('yyk_app_meta_box', 'yyk_app_meta_box_nonce');
            
            $version = get_post_meta($post->ID, '_yyk_app_version', true);
            $size = get_post_meta($post->ID, '_yyk_app_size', true);
            $developer = get_post_meta($post->ID, '_yyk_app_developer', true);
            $compatibility = get_post_meta($post->ID, '_yyk_app_compatibility', true);
            $update_date = get_post_meta($post->ID, '_yyk_app_update_date', true);
            $platform = get_post_meta($post->ID, '_yyk_app_platform', true);
            $game_type = get_post_meta($post->ID, '_yyk_app_game_type', true);
            
            // 默认值
            if (!$platform) $platform = 'all';
            if (!$game_type) $game_type = '';
            ?>
            
            <div class="yyk-meta-fields">
                <div class="yyk-field">
                    <label for="yyk_app_version"><?php _e('版本号:', 'yyk-app-download'); ?></label>
                    <input type="text" id="yyk_app_version" name="yyk_app_version" 
                           value="<?php echo esc_attr($version); ?>" class="widefat">
                    <p class="description"><?php _e('例如: 1.2.3', 'yyk-app-download'); ?></p>
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_app_size"><?php _e('文件大小:', 'yyk-app-download'); ?></label>
                    <input type="text" id="yyk_app_size" name="yyk_app_size" 
                           value="<?php echo esc_attr($size); ?>" class="widefat">
                    <p class="description"><?php _e('例如: 15.2 MB', 'yyk-app-download'); ?></p>
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_app_developer"><?php _e('开发商:', 'yyk-app-download'); ?></label>
                    <input type="text" id="yyk_app_developer" name="yyk_app_developer" 
                           value="<?php echo esc_attr($developer); ?>" class="widefat">
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_app_platform"><?php _e('平台类型:', 'yyk-app-download'); ?></label>
                    <select id="yyk_app_platform" name="yyk_app_platform" class="widefat">
                        <option value="all" <?php selected($platform, 'all'); ?>><?php _e('全平台', 'yyk-app-download'); ?></option>
                        <option value="android" <?php selected($platform, 'android'); ?>><?php _e('安卓', 'yyk-app-download'); ?></option>
                        <option value="ios" <?php selected($platform, 'ios'); ?>><?php _e('苹果', 'yyk-app-download'); ?></option>
                        <option value="pc" <?php selected($platform, 'pc'); ?>><?php _e('PC', 'yyk-app-download'); ?></option>
                    </select>
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_app_game_type"><?php _e('游戏类型:', 'yyk-app-download'); ?></label>
                    <input type="text" id="yyk_app_game_type" name="yyk_app_game_type" 
                           value="<?php echo esc_attr($game_type); ?>" class="widefat">
                    <p class="description"><?php _e('例如: MOBA、RPG、射击、休闲等', 'yyk-app-download'); ?></p>
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_app_compatibility"><?php _e('兼容性:', 'yyk-app-download'); ?></label>
                    <input type="text" id="yyk_app_compatibility" name="yyk_app_compatibility" 
                           value="<?php echo esc_attr($compatibility); ?>" class="widefat">
                    <p class="description"><?php _e('例如: Android 5.0+ / iOS 10.0+', 'yyk-app-download'); ?></p>
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_app_update_date"><?php _e('更新日期:', 'yyk-app-download'); ?></label>
                    <input type="date" id="yyk_app_update_date" name="yyk_app_update_date" 
                           value="<?php echo esc_attr($update_date); ?>" class="widefat">
                </div>
            </div>
            
            <style>
            .yyk-meta-fields { padding: 12px 0; }
            .yyk-field { margin-bottom: 15px; }
            .yyk-field label { display: block; margin-bottom: 5px; font-weight: 600; }
            .yyk-field .description { margin: 5px 0 0 0; font-style: italic; color: #666; }
            </style>
            
            <?php
        }
        
        public function render_st_extra_metabox($post) {
            wp_nonce_field('yyk_st_extra_meta_box', 'yyk_st_extra_meta_box_nonce');
            
            $short_intro = get_post_meta($post->ID, '_yyk_st_short_intro', true);
            $discount = get_post_meta($post->ID, '_yyk_st_discount', true);
            $welfare_tags = get_post_meta($post->ID, '_yyk_st_welfare_tags', true);
            $fanli = get_post_meta($post->ID, '_yyk_st_fanli', true);
            $vip_intro = get_post_meta($post->ID, '_yyk_st_vip_intro', true);
            $photos = get_post_meta($post->ID, '_yyk_st_photos', true);
            $gifts = get_post_meta($post->ID, '_yyk_st_gifts', true);
            $video = get_post_meta($post->ID, '_yyk_st_video', true);
            $game_bbs = get_post_meta($post->ID, '_yyk_st_game_bbs', true);
            $gamenotice = get_post_meta($post->ID, '_yyk_st_gamenotice', true);
            
            $welfare_tags_array = maybe_unserialize($welfare_tags);
            if (!is_array($welfare_tags_array)) {
                $welfare_tags_array = [];
            }
            
            // 如果discount是数字，格式化为带"折"的字符串
            if (is_numeric($discount)) {
                $discount = $discount . '折';
            }
            ?>
            
            <div class="yyk-st-extra-fields">
                <div class="yyk-field">
                    <label for="yyk_st_short_intro"><?php _e('福利简介:', 'yyk-app-download'); ?></label>
                    <textarea id="yyk_st_short_intro" name="yyk_st_short_intro" 
                              class="widefat" rows="3"><?php echo esc_textarea($short_intro); ?></textarea>
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_st_discount"><?php _e('折扣:', 'yyk-app-download'); ?></label>
                    <input type="text" id="yyk_st_discount" name="yyk_st_discount" 
                           value="<?php echo esc_attr($discount); ?>" class="widefat">
                    <p class="description"><?php _e('例如: 0.1折', 'yyk-app-download'); ?></p>
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_st_welfare_tags"><?php _e('福利标签:', 'yyk-app-download'); ?></label>
                    <input type="text" id="yyk_st_welfare_tags" name="yyk_st_welfare_tags" 
                           value="<?php echo esc_attr(implode(',', $welfare_tags_array)); ?>" class="widefat">
                    <p class="description"><?php _e('多个标签用英文逗号分隔，例如: 满减福利,首充福利', 'yyk-app-download'); ?></p>
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_st_fanli"><?php _e('返利介绍:', 'yyk-app-download'); ?></label>
                    <?php
                    wp_editor($fanli, 'yyk_st_fanli', [
                        'media_buttons' => true,
                        'textarea_rows' => 5,
                        'teeny' => false,
                        'textarea_name' => 'yyk_st_fanli',
                    ]);
                    ?>
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_st_vip_intro"><?php _e('VIP介绍:', 'yyk-app-download'); ?></label>
                    <?php
                    wp_editor($vip_intro, 'yyk_st_vip_intro', [
                        'media_buttons' => true,
                        'textarea_rows' => 5,
                        'teeny' => false,
                        'textarea_name' => 'yyk_st_vip_intro',
                    ]);
                    ?>
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_st_photos"><?php _e('五宣图/游戏截图:', 'yyk-app-download'); ?></label>
                    <textarea id="yyk_st_photos" name="yyk_st_photos" 
                              class="widefat" rows="4"><?php echo esc_textarea($photos); ?></textarea>
                    <p class="description"><?php _e('JSON格式的图片URL数组，例如: ["http://example.com/1.jpg","http://example.com/2.jpg"]', 'yyk-app-download'); ?></p>
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_st_gifts"><?php _e('游戏礼包:', 'yyk-app-download'); ?></label>
                    <textarea id="yyk_st_gifts" name="yyk_st_gifts" 
                              class="widefat" rows="6"><?php echo esc_textarea($gifts); ?></textarea>
                    <p class="description"><?php _e('JSON格式的礼包数据', 'yyk-app-download'); ?></p>
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_st_video"><?php _e('游戏视频:', 'yyk-app-download'); ?></label>
                    <input type="url" id="yyk_st_video" name="yyk_st_video" 
                           value="<?php echo esc_url($video); ?>" class="widefat">
                    <p class="description"><?php _e('游戏视频的URL地址', 'yyk-app-download'); ?></p>
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_st_game_bbs"><?php _e('备用视频:', 'yyk-app-download'); ?></label>
                    <input type="url" id="yyk_st_game_bbs" name="yyk_st_game_bbs" 
                           value="<?php echo esc_url($game_bbs); ?>" class="widefat">
                    <p class="description"><?php _e('备用视频地址（主视频为空时使用）', 'yyk-app-download'); ?></p>
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_st_gamenotice"><?php _e('游戏公告:', 'yyk-app-download'); ?></label>
                    <textarea id="yyk_st_gamenotice" name="yyk_st_gamenotice" 
                              class="widefat" rows="3"><?php echo esc_textarea($gamenotice); ?></textarea>
                    <p class="description"><?php _e('游戏公告内容', 'yyk-app-download'); ?></p>
                </div>
            </div>
            
            <style>
            .yyk-st-extra-fields { padding: 12px 0; }
            .yyk-st-extra-fields .yyk-field { margin-bottom: 20px; }
            .yyk-st-extra-fields .yyk-field label { display: block; margin-bottom: 5px; font-weight: 600; }
            .yyk-st-extra-fields .yyk-field .description { margin: 5px 0 0 0; font-style: italic; color: #666; }
            </style>
            
            <?php
        }
        
        public function render_download_links_metabox($post) {
            $download_url = get_post_meta($post->ID, '_yyk_app_download_url', true);
            $android_url = get_post_meta($post->ID, '_yyk_app_android_url', true);
            $ios_url = get_post_meta($post->ID, '_yyk_app_ios_url', true);
            $pc_url = get_post_meta($post->ID, '_yyk_app_pc_url', true);
            $qr_code = get_post_meta($post->ID, '_yyk_app_qr_code', true);
            ?>
            
            <div class="yyk-link-fields">
                <div class="yyk-field">
                    <label for="yyk_app_download_url"><?php _e('通用下载地址:', 'yyk-app-download'); ?></label>
                    <input type="url" id="yyk_app_download_url" name="yyk_app_download_url" 
                           value="<?php echo esc_url($download_url); ?>" class="widefat">
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_app_android_url"><?php _e('Android下载地址:', 'yyk-app-download'); ?></label>
                    <input type="url" id="yyk_app_android_url" name="yyk_app_android_url" 
                           value="<?php echo esc_url($android_url); ?>" class="widefat">
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_app_ios_url"><?php _e('iOS下载地址:', 'yyk-app-download'); ?></label>
                    <input type="url" id="yyk_app_ios_url" name="yyk_app_ios_url" 
                           value="<?php echo esc_url($ios_url); ?>" class="widefat">
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_app_pc_url"><?php _e('PC下载地址:', 'yyk-app-download'); ?></label>
                    <input type="url" id="yyk_app_pc_url" name="yyk_app_pc_url" 
                           value="<?php echo esc_url($pc_url); ?>" class="widefat">
                </div>
                
                <div class="yyk-field">
                    <label for="yyk_app_qr_code"><?php _e('二维码图片地址:', 'yyk-app-download'); ?></label>
                    <div class="yyk-upload-field">
                        <input type="url" id="yyk_app_qr_code" name="yyk_app_qr_code" 
                               value="<?php echo esc_url($qr_code); ?>" class="widefat">
                        <button type="button" class="button yyk-upload-button" 
                                data-target="yyk_app_qr_code"><?php _e('上传图片', 'yyk-app-download'); ?></button>
                    </div>
                    <p class="description"><?php _e('输入二维码图片URL或上传图片', 'yyk-app-download'); ?></p>
                </div>
                
                <?php if ($qr_code): ?>
                <div class="yyk-qr-preview">
                    <p><strong><?php _e('二维码预览:', 'yyk-app-download'); ?></strong></p>
                    <img src="<?php echo esc_url($qr_code); ?>" alt="<?php _e('下载二维码', 'yyk-app-download'); ?>" 
                         style="max-width: 150px; border: 1px solid #ddd; padding: 5px;">
                </div>
                <?php endif; ?>
            </div>
            
            <style>
            .yyk-upload-field { display: flex; gap: 10px; margin-top: 5px; }
            .yyk-upload-field input { flex: 1; }
            .yyk-qr-preview { margin-top: 15px; padding: 10px; background: #f5f5f5; border-radius: 4px; }
            </style>
            
            <?php
        }
        
        public function render_status_metabox($post) {
            $is_hot = get_post_meta($post->ID, '_yyk_app_is_hot', true);
            $is_recommend = get_post_meta($post->ID, '_yyk_app_is_recommend', true);
            $is_new = get_post_meta($post->ID, '_yyk_app_is_new', true);
            $download_count = get_post_meta($post->ID, '_yyk_app_download_count', true);
            ?>
            
            <div class="yyk-status-fields">
                <p>
                    <label>
                        <input type="checkbox" name="yyk_app_is_hot" value="1" <?php checked($is_hot, '1'); ?>>
                        <?php _e('热门应用', 'yyk-app-download'); ?>
                    </label>
                </p>
                
                <p>
                    <label>
                        <input type="checkbox" name="yyk_app_is_recommend" value="1" <?php checked($is_recommend, '1'); ?>>
                        <?php _e('推荐应用', 'yyk-app-download'); ?>
                    </label>
                </p>
                
                <p>
                    <label>
                        <input type="checkbox" name="yyk_app_is_new" value="1" <?php checked($is_new, '1'); ?>>
                        <?php _e('新应用', 'yyk-app-download'); ?>
                    </label>
                </p>
                
                <hr>
                
                <div class="yyk-download-count">
                    <label for="yyk_app_download_count"><?php _e('下载次数:', 'yyk-app-download'); ?></label>
                    <input type="number" id="yyk_app_download_count" name="yyk_app_download_count" 
                           value="<?php echo esc_attr($download_count ?: 0); ?>" min="0" class="widefat">
                </div>
                
                <hr>
                
                <div class="yyk-shortcode-info">
                    <p><strong><?php _e('短代码:', 'yyk-app-download'); ?></strong></p>
                    <code>[yyk_app id="<?php echo $post->ID; ?>"]</code>
                    <p class="description"><?php _e('复制此短代码到文章或页面中显示此应用', 'yyk-app-download'); ?></p>
                </div>
            </div>
            
            <style>
            .yyk-status-fields p { margin-bottom: 10px; }
            .yyk-status-fields label { font-weight: normal; }
            .yyk-download-count label { display: block; margin-bottom: 5px; font-weight: 600; }
            .yyk-shortcode-info { margin-top: 15px; }
            .yyk-shortcode-info code { display: block; padding: 5px; background: #f5f5f5; border: 1px solid #ddd; }
            </style>
            
            <?php
        }

        public function save_meta_boxes($post_id, $post) {
            // 检查自动保存
            if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
                return $post_id;
            }
            
            // 检查权限
            if ('yyk_app_download' !== $post->post_type || !current_user_can('edit_post', $post_id)) {
                return $post_id;
            }
            
            // 保存图标ID - 检查图标元框的nonce
            if (isset($_POST['app_icon_nonce']) && wp_verify_nonce($_POST['app_icon_nonce'], 'save_app_icon')) {
                if (isset($_POST['yyk_app_icon_id'])) {
                    $icon_id = intval($_POST['yyk_app_icon_id']);
                    update_post_meta($post_id, '_yyk_app_icon_id', $icon_id);
                }
            }
            
            // 保存应用信息字段
            if (isset($_POST['yyk_app_meta_box_nonce']) && 
                wp_verify_nonce($_POST['yyk_app_meta_box_nonce'], 'yyk_app_meta_box')) {
                
                // 保存应用信息字段
                $info_fields = [
                    'yyk_app_version' => '_yyk_app_version',
                    'yyk_app_size' => '_yyk_app_size',
                    'yyk_app_developer' => '_yyk_app_developer',
                    'yyk_app_compatibility' => '_yyk_app_compatibility',
                    'yyk_app_update_date' => '_yyk_app_update_date',
                    'yyk_app_platform' => '_yyk_app_platform',
                    'yyk_app_game_type' => '_yyk_app_game_type',
                ];
                
                foreach ($info_fields as $field => $meta_key) {
                    if (isset($_POST[$field])) {
                        update_post_meta($post_id, $meta_key, sanitize_text_field($_POST[$field]));
                    }
                }
                
                // 保存链接字段
                $link_fields = [
                    'yyk_app_download_url' => '_yyk_app_download_url',
                    'yyk_app_android_url' => '_yyk_app_android_url',
                    'yyk_app_ios_url' => '_yyk_app_ios_url',
                    'yyk_app_pc_url' => '_yyk_app_pc_url',
                    'yyk_app_qr_code' => '_yyk_app_qr_code',
                ];
                
                foreach ($link_fields as $field => $meta_key) {
                    if (isset($_POST[$field])) {
                        update_post_meta($post_id, $meta_key, esc_url_raw($_POST[$field]));
                    }
                }
                
                // 保存状态字段
                $status_fields = [
                    'yyk_app_is_hot' => '_yyk_app_is_hot',
                    'yyk_app_is_recommend' => '_yyk_app_is_recommend',
                    'yyk_app_is_new' => '_yyk_app_is_new',
                ];
                
                foreach ($status_fields as $field => $meta_key) {
                    $value = isset($_POST[$field]) ? '1' : '0';
                    update_post_meta($post_id, $meta_key, $value);
                }
                
                // 保存下载次数
                if (isset($_POST['yyk_app_download_count'])) {
                    update_post_meta($post_id, '_yyk_app_download_count', intval($_POST['yyk_app_download_count']));
                }
            }
            
            // 保存ST采集字段
            if (isset($_POST['yyk_st_extra_meta_box_nonce']) && 
                wp_verify_nonce($_POST['yyk_st_extra_meta_box_nonce'], 'yyk_st_extra_meta_box')) {
                
                // 保存福利简介
                if (isset($_POST['yyk_st_short_intro'])) {
                    update_post_meta($post_id, '_yyk_st_short_intro', sanitize_textarea_field($_POST['yyk_st_short_intro']));
                }
                
                // 保存折扣
                if (isset($_POST['yyk_st_discount'])) {
                    $discount = sanitize_text_field($_POST['yyk_st_discount']);
                    // 如果包含"折"字，只保存数字部分
                    if (strpos($discount, '折') !== false) {
                        $discount = floatval(str_replace('折', '', $discount));
                    }
                    update_post_meta($post_id, '_yyk_st_discount', $discount);
                }
                
                // 保存福利标签
                if (isset($_POST['yyk_st_welfare_tags'])) {
                    $tags_str = sanitize_text_field($_POST['yyk_st_welfare_tags']);
                    $tags_array = array_filter(array_map('trim', explode(',', $tags_str)));
                    update_post_meta($post_id, '_yyk_st_welfare_tags', serialize($tags_array));
                }
                
                // 保存返利介绍
                if (isset($_POST['yyk_st_fanli'])) {
                    update_post_meta($post_id, '_yyk_st_fanli', wp_kses_post($_POST['yyk_st_fanli']));
                }
                
                // 保存VIP介绍
                if (isset($_POST['yyk_st_vip_intro'])) {
                    update_post_meta($post_id, '_yyk_st_vip_intro', wp_kses_post($_POST['yyk_st_vip_intro']));
                }
                
                // 保存五宣图
                if (isset($_POST['yyk_st_photos'])) {
                    update_post_meta($post_id, '_yyk_st_photos', sanitize_textarea_field($_POST['yyk_st_photos']));
                }
                
                // 保存游戏礼包
                if (isset($_POST['yyk_st_gifts'])) {
                    update_post_meta($post_id, '_yyk_st_gifts', sanitize_textarea_field($_POST['yyk_st_gifts']));
                }
                
                // 保存游戏视频
                if (isset($_POST['yyk_st_video'])) {
                    update_post_meta($post_id, '_yyk_st_video', esc_url_raw($_POST['yyk_st_video']));
                }
                
                // 保存备用视频
                if (isset($_POST['yyk_st_game_bbs'])) {
                    update_post_meta($post_id, '_yyk_st_game_bbs', esc_url_raw($_POST['yyk_st_game_bbs']));
                }
                
                // 保存游戏公告
                if (isset($_POST['yyk_st_gamenotice'])) {
                    update_post_meta($post_id, '_yyk_st_gamenotice', sanitize_textarea_field($_POST['yyk_st_gamenotice']));
                }
            }
        }
    }
}