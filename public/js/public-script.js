/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：前端脚本模块
 =  📄 文件：public-script.js
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：插件前端JavaScript文件，包含截图滚动、礼包码复制、下载记录等功能
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

// 五宣图滚动
function scrollScreenshots(direction) {
    const list = document.getElementById('yyk-screenshot-list');
    if (!list) return;
    
    const scrollAmount = 295;
    list.scrollBy({
        left: direction * scrollAmount,
        behavior: 'smooth'
    });
}

// 礼包码复制
function copyGiftCode(btn) {
    const code = btn.getAttribute('data-gift-code');
    if (!code) return;
    
    navigator.clipboard.writeText(code).then(function() {
        const originalText = btn.textContent;
        btn.textContent = '已复制';
        btn.classList.add('copied');
        
        setTimeout(function() {
            btn.textContent = originalText;
            btn.classList.remove('copied');
        }, 2000);
    }).catch(function(err) {
        const textarea = document.createElement('textarea');
        textarea.value = code;
        textarea.style.position = 'fixed';
        textarea.style.left = '-9999px';
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        
        const originalText = btn.textContent;
        btn.textContent = '已复制';
        btn.classList.add('copied');
        
        setTimeout(function() {
            btn.textContent = originalText;
            btn.classList.remove('copied');
        }, 2000);
    });
}

// 标签页切换
document.addEventListener('DOMContentLoaded', function() {
    const tabBtns = document.querySelectorAll('.yyk-tab-btn');
    const tabPanels = document.querySelectorAll('.yyk-tab-panel');
    
    tabBtns.forEach(function(btn) {
        btn.addEventListener('click', function() {
            const tabId = this.getAttribute('data-tab');
            
            tabBtns.forEach(function(b) {
                b.classList.remove('active');
            });
            tabPanels.forEach(function(panel) {
                panel.classList.remove('active');
            });
            
            this.classList.add('active');
            const activePanel = document.getElementById('tab-' + tabId);
            if (activePanel) {
                activePanel.classList.add('active');
            }
        });
    });
});

(function($) {
    'use strict';
    
    $(document).ready(function() {
        // 轮播按钮功能（支持普通轮播和紧凑轮播）
        $('.yyk-layout-carousel, .yyk-layout-compact-carousel').each(function() {
            var $container = $(this);
            var $grid = $container.find('.yyk-widget-grid');
            var $prevBtn = $container.find('.yyk-carousel-btn-prev');
            var $nextBtn = $container.find('.yyk-carousel-btn-next');
            
            if (!$grid.length) return;
            
            console.log('轮播初始化成功');
            
            var animationId;
            var scrollSpeed = 1; // 滚动速度（像素/帧）
            var isPaused = false;
            
            // 复制内容实现无限循环
            var originalItems = $grid.html();
            $grid.append(originalItems);
            
            // 匀速滚动
            function scroll() {
                if (isPaused) {
                    animationId = requestAnimationFrame(scroll);
                    return;
                }
                
                var scrollLeft = $grid.scrollLeft();
                var halfScrollWidth = $grid[0].scrollWidth / 2;
                
                // 当滚动到一半时回到开头
                if (scrollLeft >= halfScrollWidth) {
                    $grid.scrollLeft(0);
                } else {
                    $grid.scrollLeft(scrollLeft + scrollSpeed);
                }
                
                animationId = requestAnimationFrame(scroll);
            }
            
            // 停止滚动
            function stopScroll() {
                if (animationId) {
                    cancelAnimationFrame(animationId);
                }
            }
            
            // 鼠标移入暂停
            $container.on('mouseenter', function() {
                isPaused = true;
            });
            
            // 鼠标移出继续
            $container.on('mouseleave', function() {
                isPaused = false;
            });
            
            // 获取滚动一个卡片的宽度
            function getScrollAmount() {
                var $firstCard = $grid.find('.yyk-template-card, .yyk-gamebox, .yyk-compact-card').first();
                if ($firstCard.length) {
                    return $firstCard.outerWidth() + 15; // 卡片宽度 + gap
                }
                return 135; // 默认值（针对紧凑卡片）
            }
            
            // 上一页按钮
            $prevBtn.on('click', function() {
                var scrollAmount = getScrollAmount();
                var scrollLeft = $grid.scrollLeft();
                var halfScrollWidth = $grid[0].scrollWidth / 2;
                
                if (scrollLeft - scrollAmount < 0) {
                    $grid.scrollLeft(halfScrollWidth - scrollAmount);
                } else {
                    $grid.scrollLeft(scrollLeft - scrollAmount);
                }
            });
            
            // 下一页按钮
            $nextBtn.on('click', function() {
                var scrollAmount = getScrollAmount();
                var scrollLeft = $grid.scrollLeft();
                var halfScrollWidth = $grid[0].scrollWidth / 2;
                
                if (scrollLeft + scrollAmount >= halfScrollWidth) {
                    $grid.scrollLeft(0);
                } else {
                    $grid.scrollLeft(scrollLeft + scrollAmount);
                }
            });
            
            // 开始滚动
            scroll();
            
            // 清理
            $(window).on('unload', function() {
                stopScroll();
            });
        });
        
        // 分页功能
        $('.yyk-widget-container.yyk-layout-grid').each(function() {
            var $container = $(this);
            var $pagination = $container.find('.yyk-pagination');
            
            if (!$pagination.length) return;
            
            var $grid = $container.find('.yyk-widget-grid');
            var $prevBtn = $pagination.find('.yyk-pagination-prev');
            var $nextBtn = $pagination.find('.yyk-pagination-next');
            var $info = $pagination.find('.yyk-pagination-info');
            
            var totalPages = parseInt($grid.data('total-pages')) || 1;
            var currentPage = 1;
            
            // 更新分页状态
            function updatePagination() {
                // 隐藏所有页面的卡片
                for (var i = 1; i <= totalPages; i++) {
                    $grid.find('.yyk-page-' + i).addClass('yyk-hidden');
                }
                
                // 显示当前页的卡片
                $grid.find('.yyk-page-' + currentPage).removeClass('yyk-hidden');
                
                // 更新按钮状态
                $prevBtn.prop('disabled', currentPage <= 1);
                $nextBtn.prop('disabled', currentPage >= totalPages);
                
                // 更新页码信息
                $info.text(currentPage + ' / ' + totalPages);
            }
            
            // 上一页
            $prevBtn.on('click', function() {
                if (currentPage > 1) {
                    currentPage--;
                    updatePagination();
                }
            });
            
            // 下一页
            $nextBtn.on('click', function() {
                if (currentPage < totalPages) {
                    currentPage++;
                    updatePagination();
                }
            });
            
            // 初始化
            updatePagination();
        });
        
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

// ===== 归档页实时搜索筛选功能 =====
document.addEventListener('DOMContentLoaded', function() {
    const searchInput = document.getElementById('yyk-realtime-search');
    const appGrid = document.getElementById('yyk-archive-app-grid');
    
    if (!searchInput || !appGrid) return;
    
    // 获取所有应用卡片
    const appCards = appGrid.querySelectorAll('.yyk-template-card');
    
    // 实时输入监听
    searchInput.addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase().trim();
        
        if (searchTerm) {
            filterApps(searchTerm);
        } else {
            showAllApps();
        }
    });
    
    // 搜索按钮点击事件
    const searchBtn = document.querySelector('.yyk-search-submit');
    if (searchBtn) {
        searchBtn.addEventListener('click', function() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            if (searchTerm) {
                filterApps(searchTerm);
            }
        });
    }
    
    // 筛选函数
    function filterApps(searchTerm) {
        let hasVisibleResults = false;
        let visibleCount = 0;
        
        appCards.forEach(card => {
            // 获取应用标题
            const titleElement = card.querySelector('.yyk-card-title');
            let appTitle = '';
            
            if (titleElement) {
                const titleLink = titleElement.querySelector('a');
                appTitle = titleLink ? titleLink.textContent : titleElement.textContent;
            }
            
            // 检查是否匹配
            const isVisible = appTitle.toLowerCase().includes(searchTerm);
            
            // 显示/隐藏卡片
            if (isVisible) {
                card.style.display = 'flex';
                hasVisibleResults = true;
                visibleCount++;
            } else {
                card.style.display = 'none';
            }
        });
        
        // 更新搜索结果信息
        updateSearchInfo(searchTerm, visibleCount);
        
        // 显示无结果提示
        showNoResultsMessage(hasVisibleResults, searchTerm);
    }
    
    // 显示所有应用
    function showAllApps() {
        let totalCount = 0;
        
        appCards.forEach(card => {
            card.style.display = 'flex';
            totalCount++;
        });
        
        // 恢复原始计数
        const searchInfo = document.querySelector('.yyk-search-info');
        if (searchInfo) {
            const countElement = searchInfo.querySelector('.yyk-count');
            if (countElement) {
                searchInfo.innerHTML = `共 <span class="yyk-count">${countElement.textContent}</span> 个应用`;
            }
        }
        
        // 移除无结果提示
        removeNoResultsMessage();
    }
    
    // 更新搜索信息
    function updateSearchInfo(searchTerm, count) {
        const searchInfo = document.querySelector('.yyk-search-info');
        if (searchInfo) {
            if (searchTerm) {
                searchInfo.innerHTML = `找到 <span class="yyk-count">${count}</span> 个匹配 "<strong>${searchTerm}</strong>" 的应用`;
            } else {
                const countElement = searchInfo.querySelector('.yyk-count');
                if (countElement) {
                    searchInfo.innerHTML = `共 <span class="yyk-count">${countElement.textContent}</span> 个应用`;
                }
            }
        }
    }
    
    // 显示无结果提示
    function showNoResultsMessage(hasResults, searchTerm) {
        removeNoResultsMessage();
        
        if (!hasResults && searchTerm) {
            const noResultsMsg = document.createElement('div');
            noResultsMsg.className = 'yyk-no-results-message';
            noResultsMsg.innerHTML = `
                <p>没有找到包含 "<strong>${searchTerm}</strong>" 的应用</p>
                <p>请尝试其他关键词，或<a href="javascript:void(0)" class="yyk-clear-search">清空搜索</a>查看所有应用</p>
            `;
            
            appGrid.appendChild(noResultsMsg);
            
            // 清空搜索功能
            const clearBtn = noResultsMsg.querySelector('.yyk-clear-search');
            if (clearBtn) {
                clearBtn.addEventListener('click', function() {
                    searchInput.value = '';
                    showAllApps();
                    searchInput.focus();
                });
            }
        }
    }
    
    // 移除无结果提示
    function removeNoResultsMessage() {
        const existingMsg = appGrid.querySelector('.yyk-no-results-message');
        if (existingMsg) {
            existingMsg.remove();
        }
    }
    
    // 页面加载时不需要额外操作
});

// ===== 视频播放器功能 =====
document.addEventListener('DOMContentLoaded', function() {
    const videoWrappers = document.querySelectorAll('.yyk-video-player-wrapper');
    
    videoWrappers.forEach(function(wrapper) {
        const videoItems = wrapper.querySelectorAll('.yyk-video-item');
        const playerId = videoItems.length > 0 ? videoItems[0].getAttribute('data-player-id') : null;
        
        if (!playerId) return;
        
        const mainVideo = document.getElementById('yyk-main-video-' + playerId);
        const videoTitle = wrapper.querySelector('.yyk-video-title');
        const videoListContainer = document.getElementById('yyk-video-list-' + playerId);
        const navUp = wrapper.querySelector('.yyk-video-nav-up');
        const navDown = wrapper.querySelector('.yyk-video-nav-down');
        
        if (!mainVideo || videoItems.length === 0) return;
        
        videoItems.forEach(function(item) {
            item.addEventListener('click', function() {
                const videoUrl = this.getAttribute('data-video');
                const title = this.getAttribute('data-title');
                
                videoItems.forEach(function(i) {
                    i.classList.remove('active');
                });
                this.classList.add('active');
                
                mainVideo.src = videoUrl;
                if (videoTitle) {
                    videoTitle.textContent = title;
                }
                
                mainVideo.play();
            });
        });
        
        const scrollAmount = 100;
        
        if (navUp) {
            navUp.addEventListener('click', function() {
                if (videoListContainer) {
                    videoListContainer.scrollBy({
                        top: -scrollAmount,
                        behavior: 'smooth'
                    });
                }
            });
        }
        
        if (navDown) {
            navDown.addEventListener('click', function() {
                if (videoListContainer) {
                    videoListContainer.scrollBy({
                        top: scrollAmount,
                        behavior: 'smooth'
                    });
                }
            });
        }
    });
});

// ===== 应用视频小工具功能 =====
document.addEventListener('DOMContentLoaded', function() {
    const videoWidgets = document.querySelectorAll('.yyk-video-widget');
    
    videoWidgets.forEach(function(widget) {
        const videoItems = widget.querySelectorAll('.yyk-video-item');
        const mainVideo = widget.querySelector('.yyk-main-video');
        const videoScroll = widget.querySelector('.yyk-video-scroll');
        const navUp = widget.querySelector('.yyk-video-nav-up');
        const navDown = widget.querySelector('.yyk-video-nav-down');
        
        if (!mainVideo || videoItems.length === 0) return;
        
        videoItems.forEach(function(item) {
            item.addEventListener('click', function() {
                const videoUrl = this.getAttribute('data-video');
                const posterUrl = this.getAttribute('data-poster');
                
                videoItems.forEach(function(i) {
                    i.classList.remove('yyk-video-item-active');
                });
                this.classList.add('yyk-video-item-active');
                
                mainVideo.src = videoUrl;
                if (posterUrl) {
                    mainVideo.poster = posterUrl;
                }
                mainVideo.play();
            });
        });
        
        const scrollAmount = 100;
        
        if (navUp) {
            navUp.addEventListener('click', function() {
                if (videoScroll) {
                    videoScroll.scrollBy({
                        top: -scrollAmount,
                        behavior: 'smooth'
                    });
                }
            });
        }
        
        if (navDown) {
            navDown.addEventListener('click', function() {
                if (videoScroll) {
                    videoScroll.scrollBy({
                        top: scrollAmount,
                        behavior: 'smooth'
                    });
                }
            });
        }
    });
});