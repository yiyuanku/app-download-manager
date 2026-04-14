<?php
/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：系统诊断模块
 =  📄 文件：class-diagnostic.php
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：系统诊断和修复工具类，提供健康检查、自动修复、调试信息等功能
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

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
            
            // 获取统计数据
            $app_count = wp_count_posts('yyk_app_download');
            $term_count = wp_count_terms(['taxonomy' => 'yyk_app_category', 'hide_empty' => false]);
            $published_apps = $app_count->publish;
            $total_apps = array_sum((array)$app_count);
            
            // 检查系统状态
            $wp_ok = version_compare(get_bloginfo('version'), '5.0', '>=');
            $php_ok = version_compare(PHP_VERSION, '7.0.0', '>=');
            $post_type_ok = get_post_type_object('yyk_app_download') ? true : false;
            $taxonomy_ok = get_taxonomy('yyk_app_category') ? true : false;
            
            $total_checks = 4;
            $passed_checks = ($wp_ok ? 1 : 0) + ($php_ok ? 1 : 0) + ($post_type_ok ? 1 : 0) + ($taxonomy_ok ? 1 : 0);
            $health_percent = round(($passed_checks / $total_checks) * 100);
            
            ?>
            <div class="wrap yyk-diagnostic-wrap">
                <h1 class="yyk-page-title">
                    <span class="dashicons dashicons-admin-tools"></span>
                    系统诊断
                </h1>
                
                <!-- 统计卡片 -->
                <div class="yyk-diagnostic-cards">
                    <div class="yyk-diagnostic-card">
                        <div class="yyk-diagnostic-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                            <span class="dashicons dashicons-heart"></span>
                        </div>
                        <div class="yyk-diagnostic-card-info">
                            <div class="yyk-diagnostic-card-value"><?php echo $health_percent; ?>%</div>
                            <div class="yyk-diagnostic-card-label">健康状态</div>
                        </div>
                    </div>
                    
                    <div class="yyk-diagnostic-card">
                        <div class="yyk-diagnostic-card-icon" style="background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);">
                            <span class="dashicons dashicons-smartphone"></span>
                        </div>
                        <div class="yyk-diagnostic-card-info">
                            <div class="yyk-diagnostic-card-value"><?php echo number_format($total_apps); ?></div>
                            <div class="yyk-diagnostic-card-label">应用总数</div>
                        </div>
                    </div>
                    
                    <div class="yyk-diagnostic-card">
                        <div class="yyk-diagnostic-card-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                            <span class="dashicons dashicons-yes"></span>
                        </div>
                        <div class="yyk-diagnostic-card-info">
                            <div class="yyk-diagnostic-card-value"><?php echo number_format($published_apps); ?></div>
                            <div class="yyk-diagnostic-card-label">已发布</div>
                        </div>
                    </div>
                    
                    <div class="yyk-diagnostic-card">
                        <div class="yyk-diagnostic-card-icon" style="background: linear-gradient(135deg, #fa709a 0%, #fee140 100%);">
                            <span class="dashicons dashicons-category"></span>
                        </div>
                        <div class="yyk-diagnostic-card-info">
                            <div class="yyk-diagnostic-card-value"><?php echo number_format($term_count); ?></div>
                            <div class="yyk-diagnostic-card-label">分类数量</div>
                        </div>
                    </div>
                </div>
                
                <!-- 选项卡导航 -->
                <div class="yyk-diagnostic-tabs">
                    <div class="yyk-diagnostic-tab yyk-diagnostic-tab-active" data-tab="system-check">
                        <span class="dashicons dashicons-dashboard"></span>
                        系统检查
                    </div>
                    <div class="yyk-diagnostic-tab" data-tab="fix-tools">
                        <span class="dashicons dashicons-hammer"></span>
                        修复工具
                    </div>
                    <div class="yyk-diagnostic-tab" data-tab="debug-info">
                        <span class="dashicons dashicons-code-standards"></span>
                        调试信息
                    </div>
                </div>
                
                <!-- 系统检查选项卡 -->
                <div id="system-check" class="yyk-diagnostic-tab-content yyk-diagnostic-tab-content-active">
                    <div class="yyk-diagnostic-section">
                        <div class="yyk-diagnostic-section-header">
                            <h2>系统环境检查</h2>
                        </div>
                        <div class="yyk-diagnostic-section-body">
                            <div class="yyk-check-list">
                                <div class="yyk-check-item">
                                    <div class="yyk-check-item-header">
                                        <span class="yyk-check-label">WordPress版本</span>
                                        <span class="yyk-check-value"><?php echo esc_html(get_bloginfo('version')); ?></span>
                                    </div>
                                    <div class="yyk-check-item-footer">
                                        <?php if ($wp_ok): ?>
                                            <span class="yyk-status-good">
                                                <span class="dashicons dashicons-yes-alt"></span>
                                                正常
                                            </span>
                                        <?php else: ?>
                                            <span class="yyk-status-warning">
                                                <span class="dashicons dashicons-warning"></span>
                                                建议升级到5.0+
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="yyk-check-item">
                                    <div class="yyk-check-item-header">
                                        <span class="yyk-check-label">PHP版本</span>
                                        <span class="yyk-check-value"><?php echo esc_html(PHP_VERSION); ?></span>
                                    </div>
                                    <div class="yyk-check-item-footer">
                                        <?php if ($php_ok): ?>
                                            <span class="yyk-status-good">
                                                <span class="dashicons dashicons-yes-alt"></span>
                                                正常
                                            </span>
                                        <?php else: ?>
                                            <span class="yyk-status-error">
                                                <span class="dashicons dashicons-dismiss"></span>
                                                需要7.0+
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="yyk-check-item">
                                    <div class="yyk-check-item-header">
                                        <span class="yyk-check-label">插件版本</span>
                                        <span class="yyk-check-value"><?php echo esc_html(YYK_APP_VERSION); ?></span>
                                    </div>
                                    <div class="yyk-check-item-footer">
                                        <span class="yyk-status-good">
                                            <span class="dashicons dashicons-yes-alt"></span>
                                            最新
                                        </span>
                                    </div>
                                </div>
                                
                                <div class="yyk-check-item">
                                    <div class="yyk-check-item-header">
                                        <span class="yyk-check-label">调试模式</span>
                                        <span class="yyk-check-value"><?php echo defined('WP_DEBUG') && WP_DEBUG ? '已开启' : '已关闭'; ?></span>
                                    </div>
                                    <div class="yyk-check-item-footer">
                                        <?php if (defined('WP_DEBUG') && WP_DEBUG): ?>
                                            <span class="yyk-status-warning">
                                                <span class="dashicons dashicons-warning"></span>
                                                开发模式
                                            </span>
                                        <?php else: ?>
                                            <span class="yyk-status-good">
                                                <span class="dashicons dashicons-yes-alt"></span>
                                                正常
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="yyk-diagnostic-section">
                        <div class="yyk-diagnostic-section-header">
                            <h2>插件状态检查</h2>
                        </div>
                        <div class="yyk-diagnostic-section-body">
                            <div class="yyk-check-list">
                                <div class="yyk-check-item">
                                    <div class="yyk-check-item-header">
                                        <span class="yyk-check-label">文章类型</span>
                                        <span class="yyk-check-value"><?php echo $post_type_ok ? '已注册' : '未注册'; ?></span>
                                    </div>
                                    <div class="yyk-check-item-footer">
                                        <?php if ($post_type_ok): ?>
                                            <span class="yyk-status-good">
                                                <span class="dashicons dashicons-yes-alt"></span>
                                                正常
                                            </span>
                                        <?php else: ?>
                                            <span class="yyk-status-error">
                                                <span class="dashicons dashicons-dismiss"></span>
                                                异常
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="yyk-check-item">
                                    <div class="yyk-check-item-header">
                                        <span class="yyk-check-label">分类法</span>
                                        <span class="yyk-check-value"><?php echo $taxonomy_ok ? '已注册' : '未注册'; ?></span>
                                    </div>
                                    <div class="yyk-check-item-footer">
                                        <?php if ($taxonomy_ok): ?>
                                            <span class="yyk-status-good">
                                                <span class="dashicons dashicons-yes-alt"></span>
                                                正常
                                            </span>
                                        <?php else: ?>
                                            <span class="yyk-status-error">
                                                <span class="dashicons dashicons-dismiss"></span>
                                                异常
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="yyk-check-item">
                                    <div class="yyk-check-item-header">
                                        <span class="yyk-check-label">小工具</span>
                                        <span class="yyk-check-value">
                                            <?php 
                                            global $wp_widget_factory;
                                            echo isset($wp_widget_factory->widgets['YYK_App_Widget']) ? '已注册' : '未注册';
                                            ?>
                                        </span>
                                    </div>
                                    <div class="yyk-check-item-footer">
                                        <?php 
                                        global $wp_widget_factory;
                                        if (isset($wp_widget_factory->widgets['YYK_App_Widget'])):
                                        ?>
                                            <span class="yyk-status-good">
                                                <span class="dashicons dashicons-yes-alt"></span>
                                                正常
                                            </span>
                                        <?php else: ?>
                                            <span class="yyk-status-error">
                                                <span class="dashicons dashicons-dismiss"></span>
                                                异常
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div class="yyk-check-item">
                                    <div class="yyk-check-item-header">
                                        <span class="yyk-check-label">短代码</span>
                                        <span class="yyk-check-value"><?php echo shortcode_exists('yyk_app_list') ? '已注册' : '未注册'; ?></span>
                                    </div>
                                    <div class="yyk-check-item-footer">
                                        <?php if (shortcode_exists('yyk_app_list')): ?>
                                            <span class="yyk-status-good">
                                                <span class="dashicons dashicons-yes-alt"></span>
                                                正常
                                            </span>
                                        <?php else: ?>
                                            <span class="yyk-status-error">
                                                <span class="dashicons dashicons-dismiss"></span>
                                                异常
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 修复工具选项卡 -->
                <div id="fix-tools" class="yyk-diagnostic-tab-content">
                    <div class="yyk-diagnostic-section">
                        <div class="yyk-diagnostic-section-header">
                            <h2>快速修复</h2>
                        </div>
                        <div class="yyk-diagnostic-section-body">
                            <form method="post" class="yyk-fix-form">
                                <?php wp_nonce_field('yyk_fix_actions', 'yyk_fix_nonce'); ?>
                                
                                <div class="yyk-fix-grid">
                                    <button type="submit" name="yyk_fix_action" value="force_register_post_type" class="yyk-fix-btn yyk-fix-btn-primary">
                                        <span class="dashicons dashicons-admin-post"></span>
                                        <div class="yyk-fix-btn-content">
                                            <strong>注册文章类型</strong>
                                            <small>修复左侧菜单不显示</small>
                                        </div>
                                    </button>
                                    
                                    <button type="submit" name="yyk_fix_action" value="force_register_taxonomy" class="yyk-fix-btn yyk-fix-btn-info">
                                        <span class="dashicons dashicons-category"></span>
                                        <div class="yyk-fix-btn-content">
                                            <strong>注册分类法</strong>
                                            <small>修复分类不显示</small>
                                        </div>
                                    </button>
                                    
                                    <button type="submit" name="yyk_fix_action" value="force_register_widget" class="yyk-fix-btn yyk-fix-btn-success">
                                        <span class="dashicons dashicons-admin-users"></span>
                                        <div class="yyk-fix-btn-content">
                                            <strong>注册小工具</strong>
                                            <small>修复小工具不可用</small>
                                        </div>
                                    </button>
                                    
                                    <button type="submit" name="yyk_fix_action" value="flush_rewrite_rules" class="yyk-fix-btn yyk-fix-btn-warning">
                                        <span class="dashicons dashicons-update"></span>
                                        <div class="yyk-fix-btn-content">
                                            <strong>刷新重写规则</strong>
                                            <small>修复404错误</small>
                                        </div>
                                    </button>
                                    
                                    <button type="submit" name="yyk_fix_action" value="clear_transients" class="yyk-fix-btn yyk-fix-btn-secondary">
                                        <span class="dashicons dashicons-trash"></span>
                                        <div class="yyk-fix-btn-content">
                                            <strong>清理缓存</strong>
                                            <small>清除临时数据</small>
                                        </div>
                                    </button>
                                    
                                    <button type="submit" name="yyk_fix_action" value="create_sample_data" class="yyk-fix-btn yyk-fix-btn-purple">
                                        <span class="dashicons dashicons-plus"></span>
                                        <div class="yyk-fix-btn-content">
                                            <strong>创建示例数据</strong>
                                            <small>添加测试应用</small>
                                        </div>
                                    </button>
                                    
                                    <button type="submit" name="yyk_fix_action" value="repair_post_meta" class="yyk-fix-btn yyk-fix-btn-orange">
                                        <span class="dashicons dashicons-admin-tools"></span>
                                        <div class="yyk-fix-btn-content">
                                            <strong>修复文章元数据</strong>
                                            <small>检查并修复meta</small>
                                        </div>
                                    </button>
                                    
                                    <button type="submit" name="yyk_fix_action" value="reset_settings" class="yyk-fix-btn yyk-fix-btn-danger">
                                        <span class="dashicons dashicons-warning"></span>
                                        <div class="yyk-fix-btn-content">
                                            <strong>重置插件设置</strong>
                                            <small>恢复默认值</small>
                                        </div>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <div class="yyk-diagnostic-section">
                        <div class="yyk-diagnostic-section-header">
                            <h2>操作说明</h2>
                        </div>
                        <div class="yyk-diagnostic-section-body">
                            <div class="yyk-info-list">
                                <div class="yyk-info-item">
                                    <span class="dashicons dashicons-info"></span>
                                    <div>
                                        <strong>注册文章类型</strong>
                                        <p>如果左侧菜单没有显示"应用下载"，请点击此按钮重新注册文章类型</p>
                                    </div>
                                </div>
                                <div class="yyk-info-item">
                                    <span class="dashicons dashicons-info"></span>
                                    <div>
                                        <strong>注册分类法</strong>
                                        <p>如果应用分类无法正常显示或使用，请点击此按钮重新注册分类法</p>
                                    </div>
                                </div>
                                <div class="yyk-info-item">
                                    <span class="dashicons dashicons-info"></span>
                                    <div>
                                        <strong>刷新重写规则</strong>
                                        <p>如果归档页或详情页出现404错误，点击此按钮刷新WordPress重写规则</p>
                                    </div>
                                </div>
                                <div class="yyk-info-item">
                                    <span class="dashicons dashicons-info"></span>
                                    <div>
                                        <strong>清理缓存</strong>
                                        <p>清除插件的所有临时缓存数据，解决显示异常问题</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- 调试信息选项卡 -->
                <div id="debug-info" class="yyk-diagnostic-tab-content">
                    <div class="yyk-diagnostic-section">
                        <div class="yyk-diagnostic-section-header">
                            <h2>系统调试信息</h2>
                            <button onclick="copyDebugInfo()" class="yyk-st-btn yyk-st-btn-secondary">
                                <span class="dashicons dashicons-clipboard"></span>
                                复制信息
                            </button>
                        </div>
                        <div class="yyk-diagnostic-section-body">
                            <div class="yyk-debug-info" id="debug-info-text"><?php echo esc_html($this->collect_debug_info()); ?></div>
                        </div>
                    </div>
                </div>
            </div>
            
            <style>
            .yyk-diagnostic-wrap {
                max-width: 1200px;
            }
            
            .yyk-page-title {
                display: flex;
                align-items: center;
                gap: 10px;
                font-size: 24px;
                font-weight: 600;
                color: #1e293b;
                margin: 20px 0 30px 0;
            }
            
            .yyk-page-title .dashicons {
                font-size: 28px;
                width: 28px;
                height: 28px;
                color: #667eea;
            }
            
            /* 统计卡片 */
            .yyk-diagnostic-cards {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
                gap: 20px;
                margin-bottom: 30px;
            }
            
            .yyk-diagnostic-card {
                display: flex;
                align-items: center;
                gap: 16px;
                padding: 24px;
                background: white;
                border-radius: 16px;
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
                border: 1px solid #f0f0f0;
                transition: all 0.3s ease;
            }
            
            .yyk-diagnostic-card:hover {
                transform: translateY(-4px);
                box-shadow: 0 8px 24px rgba(0, 0, 0, 0.1);
            }
            
            .yyk-diagnostic-card-icon {
                display: flex;
                align-items: center;
                justify-content: center;
                width: 56px;
                height: 56px;
                border-radius: 14px;
                flex-shrink: 0;
            }
            
            .yyk-diagnostic-card-icon .dashicons {
                font-size: 28px;
                width: 28px;
                height: 28px;
                color: white;
            }
            
            .yyk-diagnostic-card-info {
                flex: 1;
            }
            
            .yyk-diagnostic-card-value {
                font-size: 28px;
                font-weight: 700;
                color: #1e293b;
                line-height: 1.2;
            }
            
            .yyk-diagnostic-card-label {
                font-size: 14px;
                color: #64748b;
                margin-top: 4px;
            }
            
            /* 选项卡 */
            .yyk-diagnostic-tabs {
                display: flex;
                gap: 8px;
                background: white;
                padding: 8px;
                border-radius: 12px;
                margin-bottom: 24px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
            }
            
            .yyk-diagnostic-tab {
                display: flex;
                align-items: center;
                gap: 8px;
                padding: 12px 24px;
                border-radius: 8px;
                cursor: pointer;
                font-size: 14px;
                font-weight: 600;
                color: #64748b;
                transition: all 0.3s ease;
            }
            
            .yyk-diagnostic-tab:hover {
                background: #f8f9fa;
                color: #475569;
            }
            
            .yyk-diagnostic-tab.yyk-diagnostic-tab-active {
                background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
                color: white;
            }
            
            .yyk-diagnostic-tab .dashicons {
                font-size: 18px;
                width: 18px;
                height: 18px;
            }
            
            .yyk-diagnostic-tab-content {
                display: none;
            }
            
            .yyk-diagnostic-tab-content.yyk-diagnostic-tab-content-active {
                display: block;
            }
            
            /* 区域 */
            .yyk-diagnostic-section {
                background: white;
                border-radius: 16px;
                box-shadow: 0 2px 8px rgba(0, 0, 0, 0.04);
                margin-bottom: 24px;
                overflow: hidden;
            }
            
            .yyk-diagnostic-section-header {
                display: flex;
                align-items: center;
                justify-content: space-between;
                padding: 20px 24px;
                border-bottom: 1px solid #f0f0f0;
            }
            
            .yyk-diagnostic-section-header h2 {
                margin: 0;
                font-size: 18px;
                font-weight: 600;
                color: #1e293b;
            }
            
            .yyk-diagnostic-section-body {
                padding: 24px;
            }
            
            /* 检查列表 */
            .yyk-check-list {
                display: flex;
                flex-direction: column;
                gap: 16px;
            }
            
            .yyk-check-item {
                padding: 20px;
                background: #f8f9fa;
                border-radius: 12px;
                border-left: 4px solid #cbd5e1;
                transition: all 0.3s ease;
            }
            
            .yyk-check-item:hover {
                transform: translateX(4px);
                box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
            }
            
            .yyk-check-item-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
                margin-bottom: 12px;
            }
            
            .yyk-check-label {
                font-size: 15px;
                font-weight: 600;
                color: #1e293b;
            }
            
            .yyk-check-value {
                font-size: 14px;
                color: #64748b;
                background: white;
                padding: 4px 12px;
                border-radius: 6px;
                font-family: monospace;
            }
            
            .yyk-status-good {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                color: #059669;
                font-weight: 600;
                font-size: 14px;
            }
            
            .yyk-status-warning {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                color: #d97706;
                font-weight: 600;
                font-size: 14px;
            }
            
            .yyk-status-error {
                display: inline-flex;
                align-items: center;
                gap: 6px;
                color: #dc2626;
                font-weight: 600;
                font-size: 14px;
            }
            
            /* 修复按钮网格 */
            .yyk-fix-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
                gap: 16px;
            }
            
            .yyk-fix-btn {
                display: flex;
                align-items: center;
                gap: 14px;
                padding: 20px;
                background: white;
                border: 2px solid #e8e8e8;
                border-radius: 12px;
                cursor: pointer;
                text-align: left;
                transition: all 0.3s ease;
                width: 100%;
            }
            
            .yyk-fix-btn:hover {
                transform: translateY(-2px);
                box-shadow: 0 6px 20px rgba(0, 0, 0, 0.1);
            }
            
            .yyk-fix-btn-primary:hover { border-color: #667eea; }
            .yyk-fix-btn-info:hover { border-color: #3b82f6; }
            .yyk-fix-btn-success:hover { border-color: #10b981; }
            .yyk-fix-btn-warning:hover { border-color: #f59e0b; }
            .yyk-fix-btn-secondary:hover { border-color: #64748b; }
            .yyk-fix-btn-purple:hover { border-color: #8b5cf6; }
            .yyk-fix-btn-orange:hover { border-color: #f97316; }
            .yyk-fix-btn-danger:hover { border-color: #ef4444; }
            
            .yyk-fix-btn .dashicons {
                font-size: 28px;
                width: 28px;
                height: 28px;
                flex-shrink: 0;
            }
            
            .yyk-fix-btn-primary .dashicons { color: #667eea; }
            .yyk-fix-btn-info .dashicons { color: #3b82f6; }
            .yyk-fix-btn-success .dashicons { color: #10b981; }
            .yyk-fix-btn-warning .dashicons { color: #f59e0b; }
            .yyk-fix-btn-secondary .dashicons { color: #64748b; }
            .yyk-fix-btn-purple .dashicons { color: #8b5cf6; }
            .yyk-fix-btn-orange .dashicons { color: #f97316; }
            .yyk-fix-btn-danger .dashicons { color: #ef4444; }
            
            .yyk-fix-btn-content {
                flex: 1;
            }
            
            .yyk-fix-btn-content strong {
                display: block;
                font-size: 15px;
                color: #1e293b;
                margin-bottom: 4px;
            }
            
            .yyk-fix-btn-content small {
                display: block;
                font-size: 13px;
                color: #64748b;
            }
            
            /* 信息列表 */
            .yyk-info-list {
                display: flex;
                flex-direction: column;
                gap: 16px;
            }
            
            .yyk-info-item {
                display: flex;
                gap: 16px;
                padding: 16px;
                background: #f8f9fa;
                border-radius: 10px;
            }
            
            .yyk-info-item .dashicons {
                font-size: 24px;
                width: 24px;
                height: 24px;
                color: #3b82f6;
                flex-shrink: 0;
                margin-top: 2px;
            }
            
            .yyk-info-item strong {
                display: block;
                font-size: 15px;
                color: #1e293b;
                margin-bottom: 4px;
            }
            
            .yyk-info-item p {
                margin: 0;
                font-size: 14px;
                color: #64748b;
                line-height: 1.6;
            }
            
            /* 调试信息 */
            .yyk-debug-info {
                background: #0f172a;
                color: #e2e8f0;
                padding: 24px;
                border-radius: 12px;
                font-family: 'Consolas', 'Monaco', monospace;
                font-size: 13px;
                line-height: 1.8;
                white-space: pre-wrap;
                max-height: 500px;
                overflow-y: auto;
            }
            
            .yyk-debug-info::-webkit-scrollbar {
                width: 8px;
            }
            
            .yyk-debug-info::-webkit-scrollbar-track {
                background: #1e293b;
                border-radius: 4px;
            }
            
            .yyk-debug-info::-webkit-scrollbar-thumb {
                background: #475569;
                border-radius: 4px;
            }
            </style>
            
            <script>
            jQuery(document).ready(function($) {
                $('.yyk-diagnostic-tab').click(function() {
                    var tabId = $(this).data('tab');
                    $('.yyk-diagnostic-tab').removeClass('yyk-diagnostic-tab-active');
                    $(this).addClass('yyk-diagnostic-tab-active');
                    $('.yyk-diagnostic-tab-content').removeClass('yyk-diagnostic-tab-content-active');
                    $('#' + tabId).addClass('yyk-diagnostic-tab-content-active');
                });
            });
            
            function copyDebugInfo() {
                const debugInfo = document.getElementById('debug-info-text').textContent;
                navigator.clipboard.writeText(debugInfo).then(function() {
                    alert('调试信息已复制到剪贴板！');
                }).catch(function() {
                    alert('复制失败，请手动复制');
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
                    do_action('yyk_force_register_post_type');
                    echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 文章类型已强制重新注册！</strong></p></div>';
                    break;
                    
                case 'force_register_taxonomy':
                    do_action('yyk_force_register_taxonomy');
                    echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 分类法已强制重新注册！</strong></p></div>';
                    break;
                    
                case 'force_register_widget':
                    if (class_exists('YYK_App_Widget')) {
                        register_widget('YYK_App_Widget');
                        echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 小工具已强制重新注册！</strong></p></div>';
                    }
                    break;
                    
                case 'flush_rewrite_rules':
                    flush_rewrite_rules();
                    echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 重写规则已刷新！</strong></p></div>';
                    break;
                    
                case 'clear_transients':
                    $this->clear_transients();
                    echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 缓存已清理！</strong></p></div>';
                    break;
                    
                case 'create_sample_data':
                    $this->create_sample_data();
                    echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 示例数据已创建！</strong></p></div>';
                    break;
                    
                case 'repair_post_meta':
                    $this->repair_post_meta();
                    echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 文章元数据已修复！</strong></p></div>';
                    break;
                    
                case 'reset_settings':
                    delete_option('yyk_app_default_style');
                    delete_option('yyk_app_items_per_page');
                    delete_option('yyk_app_show_version');
                    delete_option('yyk_app_show_size');
                    delete_option('yyk_app_show_download_count');
                    echo '<div class="notice notice-success is-dismissible"><p><strong>✅ 插件设置已重置！</strong></p></div>';
                    break;
            }
        }
        
        private function clear_transients() {
            global $wpdb;
            $wpdb->query("DELETE FROM $wpdb->options WHERE option_name LIKE '_transient_%' OR option_name LIKE '_site_transient_%'");
        }
        
        private function repair_post_meta() {
            $args = [
                'post_type' => 'yyk_app_download',
                'post_status' => 'any',
                'posts_per_page' => -1,
                'fields' => 'ids'
            ];
            $posts = get_posts($args);
            
            foreach ($posts as $post_id) {
                if (!get_post_meta($post_id, '_yyk_app_version', true)) {
                    update_post_meta($post_id, '_yyk_app_version', '1.0.0');
                }
                if (!get_post_meta($post_id, '_yyk_app_size', true)) {
                    update_post_meta($post_id, '_yyk_app_size', '未知');
                }
                if (!get_post_meta($post_id, '_yyk_app_download_url', true)) {
                    update_post_meta($post_id, '_yyk_app_download_url', '');
                }
            }
        }
        
        private function create_sample_data() {
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
                }
            }
        }
    }
}
