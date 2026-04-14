<?php
/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：ST采集页面模块
 =  📄 文件：st-collect-page.php
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：ST手游采集页面的模板文件，包含采集、设置、发布管理等界面
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

if (!defined('ABSPATH')) {
    exit;
}

$api_domain = get_option('yyk_st_api_domain', 'https://www.steamsy.com');
$cps_id = get_option('yyk_st_cps_id', '15907108869');
$collector = YYK_ST_Collector::get_instance();

global $wpdb;
$st_table = $wpdb->prefix . 'yyk_games';
$st_table_exists = $wpdb->get_var("SHOW TABLES LIKE '$st_table'");
$st_total = 0;
if ($st_table_exists) {
    $st_total = $wpdb->get_var("SELECT COUNT(*) FROM $st_table");
}
?>
<div class="wrap yyk-st-collect-page">
    <h1 class="yyk-page-title">
        <span class="dashicons dashicons-download"></span>
        ST手游接口采集
    </h1>
    
    <div class="yyk-st-content">
        <!-- 统计卡片 -->
        <div class="yyk-st-cards">
            <div class="yyk-st-card">
                <div class="yyk-st-card-icon" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);">
                    <span class="dashicons dashicons-games"></span>
                </div>
                <div class="yyk-st-card-info">
                    <div class="yyk-st-card-value"><?php echo number_format($st_total); ?></div>
                    <div class="yyk-st-card-label">已采集游戏</div>
                </div>
            </div>
            
            <div class="yyk-st-card">
                <div class="yyk-st-card-icon" style="background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);">
                    <span class="dashicons dashicons-category"></span>
                </div>
                <div class="yyk-st-card-info">
                    <div class="yyk-st-card-value"><?php echo wp_count_terms('yyk_app_category', ['hide_empty' => false]); ?></div>
                    <div class="yyk-st-card-label">游戏分类</div>
                </div>
            </div>
            
            <div class="yyk-st-card">
                <div class="yyk-st-card-icon" style="background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);">
                    <span class="dashicons dashicons-clock"></span>
                </div>
                <div class="yyk-st-card-info">
                    <div class="yyk-st-card-value"><?php echo current_time('Y-m-d'); ?></div>
                    <div class="yyk-st-card-label">当前日期</div>
                </div>
            </div>
        </div>
        
        <!-- 选项卡导航 -->
        <div class="yyk-st-tabs">
            <div class="yyk-st-tab yyk-st-tab-active" data-tab="settings">
                <span class="dashicons dashicons-admin-settings"></span>
                采集设置
            </div>
            <div class="yyk-st-tab" data-tab="collect">
                <span class="dashicons dashicons-update"></span>
                采集操作
            </div>
            <div class="yyk-st-tab" data-tab="logs">
                <span class="dashicons dashicons-list-view"></span>
                采集日志
            </div>
        </div>
        
        <!-- 设置选项卡 -->
        <div id="settings" class="yyk-st-tab-content yyk-st-tab-content-active">
            <div class="yyk-st-panel">
                <div class="yyk-st-panel-header">
                    <h2>API配置</h2>
                </div>
                <div class="yyk-st-panel-body">
                    <form id="st-settings-form">
                        <div class="yyk-st-form-row">
                            <div class="yyk-st-form-group">
                                <label>接口域名</label>
                                <input type="text" name="api_domain" value="<?php echo esc_attr($api_domain); ?>" class="yyk-st-input">
                                <p class="yyk-st-help">默认：https://www.steamsy.com</p>
                            </div>
                        </div>
                        
                        <div class="yyk-st-form-row">
                            <div class="yyk-st-form-group">
                                <label>渠道ID (CPS ID)</label>
                                <input type="text" name="cps_id" value="<?php echo esc_attr($cps_id); ?>" class="yyk-st-input">
                                <p class="yyk-st-help">您的渠道ID，用于统计分成</p>
                            </div>
                        </div>
                        
                        <div class="yyk-st-form-actions">
                            <button type="submit" class="yyk-st-btn yyk-st-btn-primary">
                                <span class="dashicons dashicons-saved"></span>
                                保存设置
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <!-- 采集操作选项卡 -->
        <div id="collect" class="yyk-st-tab-content">
            <div class="yyk-st-panel">
                <div class="yyk-st-panel-header">
                    <h2>快速采集</h2>
                </div>
                <div class="yyk-st-panel-body">
                    <div class="yyk-st-buttons">
                        <button id="collect-categories" class="yyk-st-btn yyk-st-btn-info">
                            <span class="dashicons dashicons-category"></span>
                            采集分类
                        </button>
                        <button id="collect-games" class="yyk-st-btn yyk-st-btn-success">
                            <span class="dashicons dashicons-games"></span>
                            采集游戏列表
                        </button>
                        <button id="collect-reserve" class="yyk-st-btn yyk-st-btn-warning">
                            <span class="dashicons dashicons-calendar"></span>
                            采集预约列表
                        </button>
                        <button id="collect-rankings" class="yyk-st-btn yyk-st-btn-secondary">
                            <span class="dashicons dashicons-chart-bar"></span>
                            采集排行榜
                        </button>
                        <button id="collect-all" class="yyk-st-btn yyk-st-btn-primary">
                            <span class="dashicons dashicons-update"></span>
                            一键全部采集
                        </button>
                    </div>
                    
                    <div id="collect-result" class="yyk-st-alert" style="display: none;"></div>
                </div>
            </div>
        </div>
        
        <!-- 日志选项卡 -->
        <div id="logs" class="yyk-st-tab-content">
            <div class="yyk-st-panel">
                <div class="yyk-st-panel-header">
                    <h2>采集日志</h2>
                    <button id="clear-logs" class="yyk-st-btn yyk-st-btn-danger">
                        <span class="dashicons dashicons-trash"></span>
                        清空日志
                    </button>
                </div>
                <div class="yyk-st-panel-body">
                    <div id="logs-table"><?php $collector->render_logs_table(); ?></div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.yyk-st-collect-page {
    max-width: 1200px;
}

.yyk-page-title {
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
    margin-bottom: 25px;
}

.yyk-page-title .dashicons {
    font-size: 28px;
    width: 28px;
    height: 28px;
    color: #667eea;
}

/* 统计卡片 */
.yyk-st-cards {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
    gap: 20px;
    margin-bottom: 25px;
}

.yyk-st-card {
    background: white;
    border-radius: 16px;
    padding: 24px;
    display: flex;
    align-items: center;
    gap: 20px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
    border: 1px solid #e8e8e8;
    transition: all 0.3s ease;
}

.yyk-st-card:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
}

.yyk-st-card-icon {
    width: 64px;
    height: 64px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}

.yyk-st-card-icon .dashicons {
    font-size: 32px;
    width: 32px;
    height: 32px;
    color: white;
}

.yyk-st-card-info {
    flex: 1;
}

.yyk-st-card-value {
    font-size: 28px;
    font-weight: 700;
    color: #1d2327;
    line-height: 1.2;
    margin-bottom: 4px;
}

.yyk-st-card-label {
    font-size: 14px;
    color: #64748b;
    font-weight: 500;
}

/* 选项卡 */
.yyk-st-tabs {
    display: flex;
    gap: 8px;
    margin-bottom: 20px;
    background: white;
    padding: 8px;
    border-radius: 12px;
    border: 1px solid #e8e8e8;
}

.yyk-st-tab {
    flex: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 20px;
    border-radius: 8px;
    cursor: pointer;
    font-size: 14px;
    font-weight: 500;
    color: #64748b;
    transition: all 0.3s ease;
}

.yyk-st-tab:hover {
    background: #f8f9fa;
    color: #475569;
}

.yyk-st-tab.yyk-st-tab-active {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.yyk-st-tab .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

/* 选项卡内容 */
.yyk-st-tab-content {
    display: none;
}

.yyk-st-tab-content.yyk-st-tab-content-active {
    display: block;
}

/* 面板 */
.yyk-st-panel {
    background: white;
    border-radius: 16px;
    border: 1px solid #e8e8e8;
    overflow: hidden;
}

.yyk-st-panel-header {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 20px 24px;
    border-bottom: 1px solid #f0f0f0;
}

.yyk-st-panel-header h2 {
    margin: 0;
    font-size: 18px;
    font-weight: 600;
    color: #1d2327;
}

.yyk-st-panel-body {
    padding: 24px;
}

/* 表单 */
.yyk-st-form-row {
    margin-bottom: 24px;
}

.yyk-st-form-group {
    display: flex;
    flex-direction: column;
    gap: 8px;
}

.yyk-st-form-group label {
    font-size: 14px;
    font-weight: 600;
    color: #1d2327;
}

.yyk-st-input {
    width: 100%;
    max-width: 500px;
    padding: 12px 16px;
    border: 2px solid #e8e8e8;
    border-radius: 10px;
    font-size: 14px;
    transition: all 0.3s ease;
}

.yyk-st-input:focus {
    outline: none;
    border-color: #667eea;
    box-shadow: 0 0 0 4px rgba(102, 126, 234, 0.1);
}

.yyk-st-help {
    font-size: 13px;
    color: #64748b;
    margin: 0;
}

.yyk-st-form-actions {
    margin-top: 20px;
}

/* 按钮 */
.yyk-st-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 12px;
}

.yyk-st-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 24px;
    border: none;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 600;
    cursor: pointer;
    transition: all 0.3s ease;
    text-decoration: none;
}

.yyk-st-btn .dashicons {
    font-size: 18px;
    width: 18px;
    height: 18px;
}

.yyk-st-btn-primary {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
}

.yyk-st-btn-primary:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
}

.yyk-st-btn-success {
    background: linear-gradient(135deg, #11998e 0%, #38ef7d 100%);
    color: white;
}

.yyk-st-btn-success:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(17, 153, 142, 0.4);
}

.yyk-st-btn-info {
    background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
    color: white;
}

.yyk-st-btn-info:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(79, 172, 254, 0.4);
}

.yyk-st-btn-warning {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
}

.yyk-st-btn-warning:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 20px rgba(240, 147, 251, 0.4);
}

.yyk-st-btn-secondary {
    background: #f8f9fa;
    color: #475569;
    border: 2px solid #e8e8e8;
}

.yyk-st-btn-secondary:hover {
    background: #e8e8e8;
}

.yyk-st-btn-danger {
    background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
    color: white;
    padding: 10px 18px;
    font-size: 13px;
}

.yyk-st-btn-danger:hover {
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(240, 147, 251, 0.3);
}

/* 警告框 */
.yyk-st-alert {
    margin-top: 20px;
    padding: 16px 20px;
    border-radius: 10px;
    font-size: 14px;
    font-weight: 500;
}

.yyk-st-alert.success {
    background: #d4edda;
    color: #155724;
    border: 1px solid #c3e6cb;
}

.yyk-st-alert.error {
    background: #f8d7da;
    color: #721c24;
    border: 1px solid #f5c6cb;
}

.yyk-st-alert.loading {
    background: #d1ecf1;
    color: #0c5460;
    border: 1px solid #bee5eb;
}

/* 表格 */
.yyk-st-panel-body .wp-list-table {
    border: 1px solid #e8e8e8;
    border-radius: 10px;
    overflow: hidden;
}
</style>

<script>
jQuery(document).ready(function($) {
    var nonce = '';
    $.ajax({ url: ajaxurl, type: 'POST', data: { action: 'yyk_get_st_nonce' }, async: false, success: function(r) {
        if (r.success) nonce = r.data.nonce;
    }});
    
    function collect(action, data, msg) {
        data.action = action;
        data.nonce = nonce;
        var $result = $('#collect-result');
        $result.removeClass('success error loading').addClass('loading').html('⏳ ' + msg + '...').show();
        
        $.post(ajaxurl, data, function(r) {
            if (r.success) {
                $result.removeClass('loading error').addClass('success').html('✅ ' + r.message);
            } else {
                $result.removeClass('loading success').addClass('error').html('❌ ' + (r.message || '采集失败'));
            }
            setTimeout(function() { $result.fadeOut(); }, 4000);
        }).fail(function() {
            $result.removeClass('loading success').addClass('error').html('❌ 请求失败');
        });
    }
    
    $('#collect-categories').click(function() { 
        collect('st_collect_categories', {}, '采集中分类'); 
    });
    $('#collect-games').click(function() { 
        collect('st_collect_games', { page:1, limit:20 }, '采集中游戏列表'); 
    });
    $('#collect-reserve').click(function() { 
        collect('st_collect_reserve', { page:1, limit:20 }, '采集中预约列表'); 
    });
    $('#collect-rankings').click(function() { 
        collect('st_collect_rankings', { toptype:0, days:7, limit:20 }, '采集中排行榜'); 
    });
    $('#collect-all').click(function() {
        if (confirm('确定一键采集所有数据吗？')) {
            collect('st_collect_all', {}, '一键采集中');
        }
    });
    
    $('#st-settings-form').submit(function(e) {
        e.preventDefault();
        var $btn = $(this).find('button[type="submit"]');
        $btn.prop('disabled', true).html('<span class="dashicons dashicons-update"></span> 保存中...');
        
        $.post(ajaxurl, { 
            action: 'st_save_settings', 
            nonce: nonce, 
            api_domain: $('[name="api_domain"]').val(), 
            cps_id: $('[name=cps_id]').val() 
        }, function(r) {
            if (r.success) {
                $btn.html('<span class="dashicons dashicons-saved"></span> 保存成功!');
                setTimeout(function() {
                    $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> 保存设置');
                }, 1500);
            } else {
                alert('保存失败');
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-saved"></span> 保存设置');
            }
        });
    });
    
    $('#clear-logs').click(function() {
        if (confirm('确定清空所有日志吗？')) {
            $.post(ajaxurl, { action: 'st_clear_logs', nonce: nonce }, function() {
                location.reload();
            });
        }
    });
    
    $('.yyk-st-tab').click(function() {
        var tabId = $(this).data('tab');
        $('.yyk-st-tab').removeClass('yyk-st-tab-active');
        $(this).addClass('yyk-st-tab-active');
        $('.yyk-st-tab-content').removeClass('yyk-st-tab-content-active');
        $('#' + tabId).addClass('yyk-st-tab-content-active');
    });
});
</script>
