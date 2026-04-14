/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：后台脚本模块
 =  📄 文件：admin-script.js
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：插件后台管理的JavaScript文件，包含应用图标上传、元字段操作等功能
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

jQuery(document).ready(function($) {
    // 应用图标上传功能
    var app_icon_frame;
    
    console.log('YYK应用图标上传脚本已加载'); // 调试信息
    
    // 上传按钮点击事件
    $(document).on('click', '#yyk_upload_icon_btn', function(e) {
        e.preventDefault();
        console.log('上传按钮被点击'); // 调试信息
        
        // 如果媒体框架已存在，直接打开
        if (app_icon_frame) {
            app_icon_frame.open();
            return;
        }
        
        // 创建媒体上传框架
        app_icon_frame = wp.media({
            title: '选择应用图标',
            button: {
                text: '使用此图片'
            },
            library: {
                type: 'image'
            },
            multiple: false
        });
        
        // 选择图片后的处理
        app_icon_frame.on('select', function() {
            var attachment = app_icon_frame.state().get('selection').first().toJSON();
            console.log('选择了图片:', attachment); // 调试信息
            
            // 更新隐藏字段
            $('#yyk_app_icon_id').val(attachment.id);
            
            // 更新预览图片
            var previewHtml = '<img src="' + attachment.url + '" style="max-width:150px;height:auto;" alt="应用图标">';
            $('.yyk-app-icon-preview').html(previewHtml);
            
            // 更新按钮文字
            $('#yyk_upload_icon_btn').text('更换图标');
            
            // 显示移除按钮（如果不存在）
            if (!$('#yyk_remove_icon_btn').length) {
                $('#yyk_upload_icon_btn').after('<button type="button" class="button yyk-remove-icon-button" id="yyk_remove_icon_btn">移除图标</button>');
            }
        });
        
        // 打开媒体上传器
        app_icon_frame.open();
    });
    
    // 移除图标函数
    function removeAppIcon(e) {
        if (e) e.preventDefault();
        
        console.log('移除图标按钮被点击'); // 调试信息
        
        // 清除隐藏字段
        $('#yyk_app_icon_id').val('');
        
        // 恢复默认图标
        var defaultIconUrl = typeof yyk_admin_vars !== 'undefined' ? 
                            yyk_admin_vars.default_icon_url : 
                            $('.yyk-app-icon-preview img').data('default') || 
                            $('.yyk-app-icon-preview img').attr('src');
        
        console.log('默认图标URL:', defaultIconUrl); // 调试信息
        
        var previewHtml = '<img src="' + defaultIconUrl + '" style="max-width:150px;height:auto;" alt="默认图标">';
        $('.yyk-app-icon-preview').html(previewHtml);
        
        // 更新按钮文字
        $('#yyk_upload_icon_btn').text('上传图标');
        
        // 移除移除按钮
        $('#yyk_remove_icon_btn').remove();
    }
    
    // 绑定移除按钮事件（使用事件委托，处理动态添加的按钮）
    $(document).on('click', '#yyk_remove_icon_btn', removeAppIcon);
    
    // 二维码上传按钮（如果存在）
    $(document).on('click', '.yyk-upload-button', function(e) {
        e.preventDefault();
        var targetInput = $(this).data('target');
        
        console.log('二维码上传按钮被点击:', targetInput); // 调试信息
        
        var qr_frame = wp.media({
            title: '选择二维码图片',
            button: {
                text: '使用此图片'
            },
            library: {
                type: 'image'
            },
            multiple: false
        });
        
        qr_frame.on('select', function() {
            var attachment = qr_frame.state().get('selection').first().toJSON();
            $('#' + targetInput).val(attachment.url);
            
            // 显示预览（如果存在预览区域）
            if ($('.yyk-qr-preview').length) {
                $('.yyk-qr-preview').html(
                    '<p><strong>二维码预览:</strong></p>' +
                    '<img src="' + attachment.url + '" alt="下载二维码" style="max-width:150px;border:1px solid #ddd;padding:5px;">'
                );
            }
        });
        
        qr_frame.open();
    });
    
    // 确保媒体上传器已加载
    if (typeof wp !== 'undefined' && typeof wp.media !== 'undefined') {
        console.log('WordPress媒体上传器已加载');
    } else {
        console.error('WordPress媒体上传器未加载');
    }
});