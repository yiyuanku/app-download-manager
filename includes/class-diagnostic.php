<?php
/**
 * 应用下载诊断工具类
 */

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('YYK_App_Diagnostic')) {

    class YYK_App_Diagnostic {
        
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
            // 添加诊断页面
            add_action('admin_menu', [$this, 'add_diagnostic_page']);
            
            // 添加插件列表操作链接
            add_filter('plugin_action_links_' . plugin_basename(YYK_APP_PLUGIN_DIR . 'app-download-manager.php'), [$this, 'add_action_links']);
        }
        
        public function add_diagnostic_page() {
            add_submenu_page(
                'edit.php?post_type=yyk_app_download',
                __('系统诊断', 'yyk-app-download'),
                __('系统诊断', 'yyk-app-download'),
                'manage_options',
                'yyk-app-diagnostic',
                [$this, 'render_diagnostic_page']
            );
        }
        
        public function add_action_links($links) {
            $settings_link = '<a href="' . admin_url('edit.php?post_type=yyk_app_download&page=yyk-app-diagnostic') . '">' . __('诊断', 'yyk-app-download') . '</a>';
            array_unshift($links, $settings_link);
            return $links;
        }
        
        public function render_diagnostic_page() {
            if (!current_user_can('manage_options')) {
                wp_die(__('您没有权限访问此页面。', 'yyk-app-download'));
            }
            
            // 处理修复操作
            if (isset($_POST['yyk_fix_action'])) {
                $this->handle_fix_action($_POST['yyk_fix_action']);
            }
            ?>
            <div class="wrap">
                <h1><?php _e('应用下载管理器 - 系统诊断', 'yyk-app-download'); ?></h1>
                
                <div class="yyk-diagnostic-container">
                    <?php $this->render_system_check(); ?>
                    <?php $this->render_plugin_status(); ?>
                    <?php $this->render_fix_tools(); ?>
                    <?php $this->render_debug_info(); ?>
                </div>
            </div>
            
            <style>
            .yyk-diagnostic-container {
                margin-top: 20px;
            }
            .yyk-diagnostic-section {
                background: #fff;
                border: 1px solid #ccd0d4;
                padding: 20px;
                margin-bottom: 20px;
                border-radius: 4px;
            }
            .yyk-diagnostic-section h2 {
                margin-top: 0;
                padding-bottom: 10px;
                border-bottom: 1px solid #eee;
            }
            .yyk-status-good {
                color: #46b450;
                font-weight: bold;
            }
            .yyk-status-warning {
                color: #ffb900;
                font-weight: bold;
            }
            .yyk-status-error {
                color: #dc3232;
                font-weight: bold;
            }
            .yyk-fix-actions {
                margin: 20px 0;
            }
            .yyk-fix-actions .button {
                margin-right: 10px;
                margin-bottom: 10px;
            }
            .yyk-debug-info {
                background: #f5f5f5;
                padding: 15px;
                border-radius: 4px;
                font-family: monospace;
                white-space: pre-wrap;
                max-height: 400px;
                overflow-y: auto;
            }
            </style>
            <?php
        }
        
        private function render_system_check() {
            ?>
            <div class="yyk-diagnostic-section">
                <h2><?php _e('系统环境检查', 'yyk-app-download'); ?></h2>
                <table class="widefat striped">
                    <tr>
                        <th width="30%"><?php _e('检查项目', 'yyk-app-download'); ?></th>
                        <th width="40%"><?php _e('当前状态', 'yyk-app-download'); ?></th>
                        <th width="30%"><?php _e('状态', 'yyk-app-download'); ?></th>
                    </tr>
                    <tr>
                        <td><?php _e('WordPress版本', 'yyk-app-download'); ?></td>
                        <td><?php echo esc_html(get_bloginfo('version')); ?></td>
                        <td>
                            <?php if (version_compare(get_bloginfo('version'), '5.0', '>=')): ?>
                                <span class="yyk-status-good">✅ <?php _e('通过', 'yyk-app-download'); ?></span>
                            <?php else: ?>
                                <span class="yyk-status-warning">⚠️ <?php _e('建议升级', 'yyk-app-download'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('PHP版本', 'yyk-app-download'); ?></td>
                        <td><?php echo esc_html(PHP_VERSION); ?></td>
                        <td>
                            <?php if (version_compare(PHP_VERSION, '7.0.0', '>=')): ?>
                                <span class="yyk-status-good">✅ <?php _e('通过', 'yyk-app-download'); ?></span>
                            <?php else: ?>
                                <span class="yyk-status-error">❌ <?php _e('需要7.0+', 'yyk-app-download'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <td><?php _e('插件版本', 'yyk-app-download'); ?></td>
                        <td><?php echo esc_html(YYK_APP_VERSION); ?></td>
                        <td><span class="yyk-status-good">✅ <?php _e('最新', 'yyk-app-download'); ?></span></td>
                    </tr>
                    <tr>
                        <td><?php _e('调试模式', 'yyk-app-download'); ?></td>
                        <td><?php echo defined('WP_DEBUG') && WP_DEBUG ? __('已开启', 'yyk-app-download') : __('已关闭', 'yyk-app-download'); ?></td>
                        <td>
                            <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                                <span class="yyk-status-warning">⚠️ <?php _e('开发模式', 'yyk-app-download'); ?></span>
                            <?php else: ?>
                                <span class="yyk-status-good">✅ <?php _e('正常', 'yyk-app-download'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
            <?php
        }
        
        private function render_plugin_status() {
            $status_checks = [
                'post_type' => [
                    'label' => __('文章类型', 'yyk-app-download'),
                    'check' => function() {
                        return get_post_type_object('yyk_app_download') ? true : false;
                    },
                    'message_good' => __('已注册', 'yyk-app-download'),
                    'message_bad' => __('未注册', 'yyk-app-download'),
                ],
                'taxonomy' => [
                    'label' => __('分类法', 'yyk-app-download'),
                    'check' => function() {
                        return get_taxonomy('yyk_app_category') ? true : false;
                    },
                    'message_good' => __('已注册', 'yyk-app-download'),
                    'message_bad' => __('未注册', 'yyk-app-download'),
                ],
                'widget' => [
                    'label' => __('小工具', 'yyk-app-download'),
                    'check' => function() {
                        global $wp_widget_factory;
                        return isset($wp_widget_factory->widgets['YYK_App_Widget']);
                    },
                    'message_good' => __('已注册', 'yyk-app-download'),
                    'message_bad' => __('未注册', 'yyk-app-download'),
                ],
                'shortcodes' => [
                    'label' => __('短代码', 'yyk-app-download'),
                    'check' => function() {
                        return shortcode_exists('yyk_app');
                    },
                    'message_good' => __('已注册', 'yyk-app-download'),
                    'message_bad' => __('未注册', 'yyk-app-download'),
                ],
                'post_exists' => [
                    'label' => __('应用数据', 'yyk-app-download'),
                    'check' => function() {
                        $count = wp_count_posts('yyk_app_download');
                        return $count->publish > 0;
                    },
                    'message_good' => __('有数据', 'yyk-app-download'),
                    'message_bad' => __('无数据', 'yyk-app-download'),
                ],
            ];
            ?>
            <div class="yyk-diagnostic-section">
                <h2><?php _e('插件状态检查', 'yyk-app-download'); ?></h2>
                <table class="widefat striped">
                    <tr>
                        <th width="30%"><?php _e('功能模块', 'yyk-app-download'); ?></th>
                        <th width="40%"><?php _e('状态', 'yyk-app-download'); ?></th>
                        <th width="30%"><?php _e('检查结果', 'yyk-app-download'); ?></th>
                    </tr>
                    <?php foreach ($status_checks as $check): ?>
                    <tr>
                        <td><?php echo esc_html($check['label']); ?></td>
                        <td>
                            <?php 
                            $result = $check['check']();
                            echo $result ? $check['message_good'] : $check['message_bad'];
                            ?>
                        </td>
                        <td>
                            <?php if ($result): ?>
                                <span class="yyk-status-good">✅ <?php _e('正常', 'yyk-app-download'); ?></span>
                            <?php else: ?>
                                <span class="yyk-status-error">❌ <?php _e('异常', 'yyk-app-download'); ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
            </div>
            <?php
        }
        
        private function render_fix_tools() {
            ?>
            <div class="yyk-diagnostic-section">
                <h2><?php _e('修复工具', 'yyk-app-download'); ?></h2>
                <p><?php _e('如果遇到菜单不显示、小工具不可用等问题，请尝试以下修复操作：', 'yyk-app-download'); ?></p>
                
                <form method="post" class="yyk-fix-actions">
                    <?php wp_nonce_field('yyk_fix_actions', 'yyk_fix_nonce'); ?>
                    
                    <button type="submit" name="yyk_fix_action" value="force_register_post_type" class="button button-primary">
                        <?php _e('强制注册文章类型', 'yyk-app-download'); ?>
                    </button>
                    
                    <button type="submit" name="yyk_fix_action" value="force_register_widget" class="button">
                        <?php _e('强制注册小工具', 'yyk-app-download'); ?>
                    </button>
                    
                    <button type="submit" name="yyk_fix_action" value="flush_rewrite_rules" class="button">
                        <?php _e('刷新重写规则', 'yyk-app-download'); ?>
                    </button>
                    
                    <button type="submit" name="yyk_fix_action" value="create_sample_data" class="button">
                        <?php _e('创建示例数据', 'yyk-app-download'); ?>
                    </button>
                    
                    <button type="submit" name="yyk_fix_action" value="reset_settings" class="button button-secondary">
                        <?php _e('重置插件设置', 'yyk-app-download'); ?>
                    </button>
                </form>
                
                <div class="yyk-fix-descriptions">
                    <p><strong><?php _e('各功能说明：', 'yyk-app-download'); ?></strong></p>
                    <ul>
                        <li><strong><?php _e('强制注册文章类型：', 'yyk-app-download'); ?></strong><?php _e('如果左侧菜单没有显示"应用下载"，请点击此按钮', 'yyk-app-download'); ?></li>
                        <li><strong><?php _e('强制注册小工具：', 'yyk-app-download'); ?></strong><?php _e('如果小工具列表中没有"应用展示卡片"，请点击此按钮', 'yyk-app-download'); ?></li>
                        <li><strong><?php _e('刷新重写规则：', 'yyk-app-download'); ?></strong><?php _e('修复404错误或页面无法访问问题', 'yyk-app-download'); ?></li>
                        <li><strong><?php _e('创建示例数据：', 'yyk-app-download'); ?></strong><?php _e('创建几个示例应用和分类，用于测试', 'yyk-app-download'); ?></li>
                        <li><strong><?php _e('重置插件设置：', 'yyk-app-download'); ?></strong><?php _e('重置所有插件设置到默认值', 'yyk-app-download'); ?></li>
                    </ul>
                </div>
            </div>
            <?php
        }
        
        private function render_debug_info() {
            $debug_info = $this->collect_debug_info();
            ?>
            <div class="yyk-diagnostic-section">
                <h2><?php _e('调试信息', 'yyk-app-download'); ?></h2>
                <p><?php _e('以下信息可用于技术支持：', 'yyk-app-download'); ?></p>
                <div class="yyk-debug-info"><?php echo esc_html($debug_info); ?></div>
                <p>
                    <button onclick="copyDebugInfo()" class="button">
                        <?php _e('复制调试信息', 'yyk-app-download'); ?>
                    </button>
                </p>
            </div>
            
            <script>
            function copyDebugInfo() {
                const debugInfo = document.querySelector('.yyk-debug-info').textContent;
                navigator.clipboard.writeText(debugInfo).then(function() {
                    alert('<?php _e('调试信息已复制到剪贴板', 'yyk-app-download'); ?>');
                });
            }
            </script>
            <?php
        }
        
        private function collect_debug_info() {
            $info = [];
            
            $info[] = "=== 应用下载管理器调试信息 ===";
            $info[] = "生成时间: " . date('Y-m-d H:i:s');
            $info[] = "WordPress版本: " . get_bloginfo('version');
            $info[] = "PHP版本: " . PHP_VERSION;
            $info[] = "插件版本: " . YYK_APP_VERSION;
            $info[] = "站点URL: " . get_site_url();
            
            // 插件状态
            $info[] = "\n=== 插件状态 ===";
            $plugin = plugin_basename(YYK_APP_PLUGIN_DIR . 'app-download-manager.php');
            $info[] = "插件路径: " . $plugin;
            $info[] = "插件状态: " . (is_plugin_active($plugin) ? '已激活' : '未激活');
            
            // 文章类型状态
            $post_type = get_post_type_object('yyk_app_download');
            $info[] = "\n=== 文章类型状态 ===";
            if ($post_type) {
                $info[] = "状态: 已注册";
                $info[] = "名称: " . $post_type->labels->name;
                $info[] = "显示在菜单: " . ($post_type->show_in_menu ? '是' : '否');
                $info[] = "菜单位置: " . ($post_type->menu_position ?: '未设置');
            } else {
                $info[] = "状态: 未注册";
            }
            
            // 分类状态
            $taxonomy = get_taxonomy('yyk_app_category');
            $info[] = "\n=== 分类状态 ===";
            if ($taxonomy) {
                $info[] = "状态: 已注册";
            } else {
                $info[] = "状态: 未注册";
            }
            
            // 小工具状态
            global $wp_widget_factory;
            $info[] = "\n=== 小工具状态 ===";
            if (isset($wp_widget_factory->widgets['YYK_App_Widget'])) {
                $info[] = "状态: 已注册";
            } else {
                $info[] = "状态: 未注册";
            }
            
            // 短代码状态
            $info[] = "\n=== 短代码状态 ===";
            $info[] = "yyk_app: " . (shortcode_exists('yyk_app') ? '已注册' : '未注册');
            $info[] = "yyk_app_list: " . (shortcode_exists('yyk_app_list') ? '已注册' : '未注册');
            
            // 应用数据
            $count = wp_count_posts('yyk_app_download');
            $info[] = "\n=== 应用数据统计 ===";
            $info[] = "已发布: " . $count->publish;
            $info[] = "草稿: " . $count->draft;
            $info[] = "待审核: " . $count->pending;
            
            // 分类数据
            $terms = get_terms(['taxonomy' => 'yyk_app_category', 'hide_empty' => false]);
            $info[] = "\n=== 分类数据 ===";
            $info[] = "分类数量: " . count($terms);
            foreach ($terms as $term) {
                $info[] = "  - " . $term->name . " (" . $term->count . "个应用)";
            }
            
            return implode("\n", $info);
        }
        
        private function handle_fix_action($action) {
            if (!wp_verify_nonce($_POST['yyk_fix_nonce'], 'yyk_fix_actions')) {
                wp_die(__('安全校验失败', 'yyk-app-download'));
            }
            
            switch ($action) {
                case 'force_register_post_type':
                    // 强制重新注册文章类型和分类
                    do_action('yyk_force_register_post_type');
                    do_action('yyk_force_register_taxonomy');
                    echo '<div class="notice notice-success"><p>' . __('文章类型和分类已强制重新注册！', 'yyk-app-download') . '</p></div>';
                    break;
                    
                case 'force_register_widget':
                    // 强制注册小工具
                    if (class_exists('YYK_App_Widget')) {
                        register_widget('YYK_App_Widget');
                        echo '<div class="notice notice-success"><p>' . __('小工具已强制重新注册！', 'yyk-app-download') . '</p></div>';
                    }
                    break;
                    
                case 'flush_rewrite_rules':
                    // 刷新重写规则
                    flush_rewrite_rules();
                    echo '<div class="notice notice-success"><p>' . __('重写规则已刷新！', 'yyk-app-download') . '</p></div>';
                    break;
                    
                case 'create_sample_data':
                    // 创建示例数据
                    $this->create_sample_data();
                    echo '<div class="notice notice-success"><p>' . __('示例数据已创建！', 'yyk-app-download') . '</p></div>';
                    break;
                    
                case 'reset_settings':
                    // 重置设置
                    delete_option('yyk_app_default_style');
                    delete_option('yyk_app_items_per_page');
                    echo '<div class="notice notice-success"><p>' . __('插件设置已重置！', 'yyk-app-download') . '</p></div>';
                    break;
            }
        }
        
        private function create_sample_data() {
            // 创建示例应用
            $sample_apps = [
                [
                    'title' => __('微信', 'yyk-app-download'),
                    'content' => __('微信是一款跨平台的通讯工具，支持发送语音短信、视频、图片和文字。', 'yyk-app-download'),
                    'version' => '8.0.0',
                    'size' => '250 MB',
                    'developer' => __('腾讯公司', 'yyk-app-download'),
                    'category' => 'tools',
                ],
                [
                    'title' => __('王者荣耀', 'yyk-app-download'),
                    'content' => __('王者荣耀是一款5V5团队公平竞技手游，国民MOBA手游大作！', 'yyk-app-download'),
                    'version' => '1.70.1',
                    'size' => '3.2 GB',
                    'developer' => __('腾讯游戏', 'yyk-app-download'),
                    'category' => 'games',
                    'is_hot' => true,
                ],
                [
                    'title' => __('WPS Office', 'yyk-app-download'),
                    'content' => __('WPS Office是一款办公软件套件，包含文字、表格、演示三大组件。', 'yyk-app-download'),
                    'version' => '14.0.0',
                    'size' => '180 MB',
                    'developer' => __('金山软件', 'yyk-app-download'),
                    'category' => 'productivity',
                    'is_recommend' => true,
                ],
            ];
            
            foreach ($sample_apps as $app_data) {
                $post_id = wp_insert_post([
                    'post_title' => $app_data['title'],
                    'post_content' => $app_data['content'],
                    'post_status' => 'publish',
                    'post_type' => 'yyk_app_download',
                ]);
                
                if ($post_id) {
                    update_post_meta($post_id, '_yyk_app_version', $app_data['version']);
                    update_post_meta($post_id, '_yyk_app_size', $app_data['size']);
                    update_post_meta($post_id, '_yyk_app_developer', $app_data['developer']);
                    update_post_meta($post_id, '_yyk_app_download_url', 'https://example.com/download/' . sanitize_title($app_data['title']));
                    
                    if (isset($app_data['is_hot'])) {
                        update_post_meta($post_id, '_yyk_app_is_hot', '1');
                    }
                    
                    if (isset($app_data['is_recommend'])) {
                        update_post_meta($post_id, '_yyk_app_is_recommend', '1');
                    }
                    
                    // 设置分类
                    $term = get_term_by('slug', $app_data['category'], 'yyk_app_category');
                    if ($term) {
                        wp_set_object_terms($post_id, $term->term_id, 'yyk_app_category');
                    }
                }
            }
        }
    }
}