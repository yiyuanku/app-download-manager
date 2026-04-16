/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：ST采集脚本模块
 =  📄 文件：st-collect.js
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：ST手游采集功能的JavaScript文件，包含游戏采集、发布管理、API交互等功能
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

jQuery(document).ready(function($) {
    'use strict';
    
    var nonce = '';
    var isCollecting = false;
    
    // 获取安全令牌
    $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: { action: 'yyk_get_st_nonce' },
        async: false,
        success: function(r) {
            if (r.success) {
                nonce = r.data.nonce;
            }
        }
    });
    
    // 通用采集函数
    function collect(action, data, msg, callback) {
        if (isCollecting) {
            alert('采集中，请稍后再试');
            return;
        }
        
        isCollecting = true;
        data.action = action;
        data.nonce = nonce;
        
        var $result = $('#collect-result');
        $result.removeClass('success error loading').addClass('loading').html('⏳ ' + msg + '...').show();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: data,
            success: function(r) {
                if (r.success) {
                    $result.removeClass('loading error').addClass('success').html('✅ ' + (r.message || '采集完成'));
                } else {
                    $result.removeClass('loading success').addClass('error').html('❌ ' + (r.message || '采集失败'));
                }
                if (callback) callback(r);
            },
            error: function() {
                $result.removeClass('loading success').addClass('error').html('❌ 请求失败');
            },
            complete: function() {
                isCollecting = false;
                setTimeout(function() { $result.fadeOut(); }, 3000);
            }
        });
    }
    
    // 采集分类
    $('#collect-categories').click(function() {
        collect('st_collect_categories', {}, '采集中分类');
    });
    
    // 采集游戏列表
    $('#collect-games').click(function() {
        collect('st_collect_games', { page: 1, limit: 20 }, '采集中游戏列表');
    });
    
    // 采集预约列表
    $('#collect-reserve').click(function() {
        collect('st_collect_reserve', { page: 1, limit: 20 }, '采集中预约列表');
    });
    
    // 采集排行榜
    $('#collect-rankings').click(function() {
        collect('st_collect_rankings', { toptype: 0, days: 7, limit: 20 }, '采集中排行榜');
    });
    
    // 一键全部采集
    $('#collect-all').click(function() {
        if (confirm('确定一键采集所有数据吗？这可能需要几分钟时间。')) {
            collect('st_collect_all', {}, '一键采集中');
        }
    });
    
    // 保存设置
    $('#st-settings-form').submit(function(e) {
        e.preventDefault();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'st_save_settings',
                nonce: nonce,
                api_domain: $('[name=api_domain]').val(),
                cps_id: $('[name=cps_id]').val()
            },
            success: function(r) {
                alert(r.success ? '设置已保存' : '保存失败');
            },
            error: function() {
                alert('保存失败');
            }
        });
    });
    
    // 清空日志
    $('#clear-logs').click(function() {
        if (confirm('确定清空所有日志吗？')) {
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: { action: 'st_clear_logs', nonce: nonce },
                success: function() {
                    location.reload();
                }
            });
        }
    });
    
    // 刷新日志
    $('#refresh-logs').click(function() {
        location.reload();
    });
    
    // Tab切换
    $('.nav-tab').click(function(e) {
        e.preventDefault();
        $('.nav-tab').removeClass('nav-tab-active');
        $(this).addClass('nav-tab-active');
        $('.tab-content').hide();
        $($(this).attr('href')).show();
    });
});