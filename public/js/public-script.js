(function($) {
    'use strict';
    
    $(document).ready(function() {
        // 下载按钮点击跟踪
        $('.yyk-card-download, .yyk-gamebox-download, .yyk-single-download, .yyk-app-download').on('click', function(e) {
            var appId = $(this).data('app-id');
            
            if (appId && typeof yykAppAjax !== 'undefined') {
                // 发送AJAX请求记录下载
                $.ajax({
                    url: yykAppAjax.ajax_url,
                    type: 'POST',
                    data: {
                        action: 'yyk_record_download',
                        app_id: appId,
                        nonce: yykAppAjax.nonce
                    },
                    success: function(response) {
                        if (response.success) {
                            console.log('下载记录更新成功，总下载次数：' + response.data.download_count);
                        }
                    },
                    error: function() {
                        console.log('下载记录更新失败');
                    }
                });
            }
        });
        
        // 图片懒加载
        if ('IntersectionObserver' in window) {
            var lazyImages = document.querySelectorAll('.yyk-app-icon img[data-src], .yyk-card-icon img[data-src], .yyk-gamebox-icon img[data-src]');
            
            var imageObserver = new IntersectionObserver(function(entries, observer) {
                entries.forEach(function(entry) {
                    if (entry.isIntersecting) {
                        var img = entry.target;
                        img.src = img.dataset.src;
                        img.removeAttribute('data-src');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            lazyImages.forEach(function(img) {
                imageObserver.observe(img);
            });
        }
        
        // 卡片悬停效果
        $('.yyk-card, .yyk-gamebox').on('mouseenter', function() {
            $(this).addClass('yyk-hover');
        }).on('mouseleave', function() {
            $(this).removeClass('yyk-hover');
        });
        
        // 分类标签动画
        $('.yyk-category-tag').on('mouseenter', function() {
            $(this).addClass('yyk-tag-hover');
        }).on('mouseleave', function() {
            $(this).removeClass('yyk-tag-hover');
        });
        
        // 下载按钮动画
        $('.yyk-download-btn').on('mouseenter', function() {
            $(this).addClass('yyk-btn-hover');
        }).on('mouseleave', function() {
            $(this).removeClass('yyk-btn-hover');
        });
    });
    
})(jQuery);