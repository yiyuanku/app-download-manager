<?php
if (!defined('ABSPATH')) {
    exit;
}

$api_domain = get_option('yyk_st_api_domain', 'https://www.steamsy.com');
$cps_id = get_option('yyk_st_cps_id', '15907108869');
$collector = YYK_ST_Collector::get_instance();
?>
<div class="wrap">
    <h1>ST手游接口采集</h1>
    
    <div class="nav-tab-wrapper">
        <a href="#settings" class="nav-tab nav-tab-active">采集设置</a>
        <a href="#logs" class="nav-tab">采集日志</a>
    </div>
    
    <div id="settings" class="tab-content" style="padding:20px 0;">
        <form id="st-settings-form">
            <table class="form-table">
                <tr>
                    <th>接口域名</th>
                    <td>
                        <input type="text" name="api_domain" value="<?php echo esc_attr($api_domain); ?>" class="regular-text">
                        <p class="description">默认：https://www.steamsy.com</p>
                    </td>
                </tr>
                <tr>
                    <th>渠道ID</th>
                    <td>
                        <input type="text" name="cps_id" value="<?php echo esc_attr($cps_id); ?>" class="regular-text">
                        <p class="description">您的渠道ID</p>
                    </td>
                </tr>
            </table>
            <p><button type="submit" class="button button-primary">保存设置</button></p>
        </form>
        
        <hr>
        
        <h2>采集操作</h2>
        <div style="display:flex; gap:10px; flex-wrap:wrap; margin:15px 0;">
            <button id="collect-categories" class="button">采集分类</button>
            <button id="collect-games" class="button">采集游戏列表</button>
            <button id="collect-reserve" class="button">采集预约列表</button>
            <button id="collect-rankings" class="button">采集排行榜</button>
            <button id="collect-all" class="button button-primary">一键全部采集</button>
        </div>
        
        <div id="collect-result" style="margin-top:15px; padding:10px; border-radius:4px; display:none;"></div>
    </div>
    
    <div id="logs" class="tab-content" style="padding:20px 0; display:none;">
        <div id="logs-table"><?php $collector->render_logs_table(); ?></div>
        <p><button id="clear-logs" class="button">清空日志</button></p>
    </div>
</div>

<style>
.tab-content { display: block; }
.success { background:#d4edda; color:#155724; border:1px solid #c3e6cb; }
.error { background:#f8d7da; color:#721c24; border:1px solid #f5c6cb; }
.loading { background:#d1ecf1; color:#0c5460; border:1px solid #bee5eb; }
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
        $('#collect-result').removeClass('success error loading').addClass('loading').html('⏳ ' + msg + '...').show();
        $.post(ajaxurl, data, function(r) {
            if (r.success) {
                $('#collect-result').removeClass('loading error').addClass('success').html('✅ ' + r.message);
            } else {
                $('#collect-result').removeClass('loading success').addClass('error').html('❌ ' + (r.message || '采集失败'));
            }
            setTimeout(function() { $('#collect-result').fadeOut(); }, 3000);
        }).fail(function() {
            $('#collect-result').removeClass('loading success').addClass('error').html('❌ 请求失败');
        });
    }
    
    $('#collect-categories').click(function() { collect('st_collect_categories', {}, '采集中分类'); });
    $('#collect-games').click(function() { collect('st_collect_games', { page:1, limit:20 }, '采集中游戏列表'); });
    $('#collect-reserve').click(function() { collect('st_collect_reserve', { page:1, limit:20 }, '采集中预约列表'); });
    $('#collect-rankings').click(function() { collect('st_collect_rankings', { toptype:0, days:7, limit:20 }, '采集中排行榜'); });
    $('#collect-all').click(function() {
        if (confirm('确定一键采集所有数据吗？')) {
            collect('st_collect_all', {}, '一键采集中');
        }
    });
    
    $('#st-settings-form').submit(function(e) {
        e.preventDefault();
        $.post(ajaxurl, { action: 'st_save_settings', nonce: nonce, api_domain: $('[name=api_domain]').val(), cps_id: $('[name=cps_id]').val() }, function(r) {
            alert(r.success ? '设置已保存' : '保存失败');
        });
    });
    
    $('#clear-logs').click(function() {
        if (confirm('确定清空所有日志吗？')) {
            $.post(ajaxurl, { action: 'st_clear_logs', nonce: nonce }, function() {
                location.reload();
            });
        }
    });
    
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').hide();
        $($(this).attr('href')).show();
    });
});
</script>