<?php
/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：使用教程模块
 =  📄 文件：tutorial.php
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：插件使用教程页面的模板文件，包含短代码、小工具、ST采集等使用说明
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

if (!defined('ABSPATH')) {
    exit;
}
?>
<div class="wrap yyk-tutorial-page">
    <h1><?php _e('应用下载管理器 - 使用教程', 'yyk-app-download'); ?></h1>
    
    <div class="yyk-tutorial-content">
        <!-- 短码教程 -->
        <div class="yyk-tutorial-section">
            <h2><span class="dashicons dashicons-shortcode"></span> 短码使用教程</h2>
            
            <div class="yyk-tutorial-box">
                <h3>基础短码</h3>
                <div class="yyk-code-block">
                    <code>[yyk_app_list]</code>
                </div>
                <p>显示默认的应用列表，使用后台设置中的配置。</p>
            </div>
            
            <div class="yyk-tutorial-box">
                <h3>所有可用参数</h3>
                <table class="yyk-tutorial-table">
                    <thead>
                        <tr>
                            <th>参数</th>
                            <th>说明</th>
                            <th>可选值</th>
                            <th>默认值</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>count</code></td>
                            <td>显示数量</td>
                            <td>数字</td>
                            <td>6</td>
                        </tr>
                        <tr>
                            <td><code>style</code></td>
                            <td>卡片样式</td>
                            <td>card, gamebox, compact</td>
                            <td>card</td>
                        </tr>
                        <tr>
                            <td><code>layout</code></td>
                            <td>布局方式</td>
                            <td>grid, carousel, list, compact-carousel</td>
                            <td>grid</td>
                        </tr>
                        <tr>
                            <td><code>columns</code></td>
                            <td>列数（grid布局时）</td>
                            <td>1-12</td>
                            <td>3</td>
                        </tr>
                        <tr>
                            <td><code>category</code></td>
                            <td>分类ID或slug</td>
                            <td>分类ID或slug</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td><code>orderby</code></td>
                            <td>排序方式</td>
                            <td>date, title, rand, modified</td>
                            <td>date</td>
                        </tr>
                        <tr>
                            <td><code>order</code></td>
                            <td>排序方向</td>
                            <td>ASC, DESC</td>
                            <td>DESC</td>
                        </tr>
                        <tr>
                            <td><code>show_title</code></td>
                            <td>是否显示标题</td>
                            <td>true, false</td>
                            <td>true</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            
            <div class="yyk-tutorial-box">
                <h3>实用示例</h3>
                
                <h4>1. 显示最新8个应用（卡片样式）</h4>
                <div class="yyk-code-block">
                    <code>[yyk_app_list count="8" style="card"]</code>
                </div>
                
                <h4>2. 显示游戏盒子样式</h4>
                <div class="yyk-code-block">
                    <code>[yyk_app_list count="6" style="gamebox"]</code>
                </div>
                
                <h4>3. 轮播布局</h4>
                <div class="yyk-code-block">
                    <code>[yyk_app_list style="card" layout="carousel" count="10"]</code>
                </div>
                
                <h4>4. 紧凑轮播布局（热门展示）</h4>
                <div class="yyk-code-block">
                    <code>[yyk_app_list style="compact" layout="carousel" count="12"]</code>
                </div>
                
                <h4>5. 指定分类</h4>
                <div class="yyk-code-block">
                    <code>[yyk_app_list category="动作游戏" count="6"]</code>
                </div>
                
                <h4>6. 随机显示</h4>
                <div class="yyk-code-block">
                    <code>[yyk_app_list orderby="rand" count="6"]</code>
                </div>
                
                <h4>7. 4列网格布局</h4>
                <div class="yyk-code-block">
                    <code>[yyk_app_list columns="4" count="8"]</code>
                </div>
            </div>
            
            <div class="yyk-tutorial-box">
                <h3>应用轮播短码</h3>
                <div class="yyk-code-block">
                    <code>[yyk_app_carousel]</code>
                </div>
                <p>展示应用轮播，使用与归档页相同的样式。</p>
                
                <h4>应用轮播短码参数</h4>
                <table class="yyk-tutorial-table">
                    <thead>
                        <tr>
                            <th>参数</th>
                            <th>说明</th>
                            <th>可选值</th>
                            <th>默认值</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>title</code></td>
                            <td>标题</td>
                            <td>文本</td>
                            <td>热门展示</td>
                        </tr>
                        <tr>
                            <td><code>count</code></td>
                            <td>显示数量</td>
                            <td>数字</td>
                            <td>12</td>
                        </tr>
                        <tr>
                            <td><code>category</code></td>
                            <td>分类ID</td>
                            <td>分类ID</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td><code>orderby</code></td>
                            <td>排序方式</td>
                            <td>date, title, rand</td>
                            <td>date</td>
                        </tr>
                        <tr>
                            <td><code>order</code></td>
                            <td>排序方向</td>
                            <td>ASC, DESC</td>
                            <td>DESC</td>
                        </tr>
                        <tr>
                            <td><code>show_view_more</code></td>
                            <td>显示查看全部</td>
                            <td>true, false</td>
                            <td>true</td>
                        </tr>
                    </tbody>
                </table>
                
                <h4>应用轮播短码示例</h4>
                <div class="yyk-code-block">
                    <code>[yyk_app_carousel]
[yyk_app_carousel title="精选推荐" count="15"]
[yyk_app_carousel category="5" orderby="rand"]
[yyk_app_carousel show_view_more="false"]</code>
                </div>
            </div>
            
            <div class="yyk-tutorial-box">
                <h3>视频播放器短码</h3>
                <div class="yyk-code-block">
                    <code>[yyk_video_player]</code>
                </div>
                <p>展示视频播放器，支持随机展示采集的视频或指定应用ID。</p>
                
                <h4>视频播放器短码参数</h4>
                <table class="yyk-tutorial-table">
                    <thead>
                        <tr>
                            <th>参数</th>
                            <th>说明</th>
                            <th>可选值</th>
                            <th>默认值</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>id</code></td>
                            <td>应用ID</td>
                            <td>数字</td>
                            <td>0</td>
                        </tr>
                        <tr>
                            <td><code>count</code></td>
                            <td>视频数量</td>
                            <td>数字</td>
                            <td>5</td>
                        </tr>
                    </tbody>
                </table>
                
                <h4>视频播放器短码示例</h4>
                <div class="yyk-code-block">
                    <code>[yyk_video_player]
[yyk_video_player id="123"]
[yyk_video_player count="10"]
[yyk_video_player id="123" count="5"]</code>
                </div>
            </div>
            
            <div class="yyk-tutorial-box">
                <h3>Logo轮播短码</h3>
                <div class="yyk-code-block">
                    <code>[yyk_logo_carousel]</code>
                </div>
                <p>展示Logo轮播，支持自定义Logo或自动采集的应用Logo。</p>
                
                <h4>Logo轮播短码参数</h4>
                <table class="yyk-tutorial-table">
                    <thead>
                        <tr>
                            <th>参数</th>
                            <th>说明</th>
                            <th>可选值</th>
                            <th>默认值</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><code>title</code></td>
                            <td>标题</td>
                            <td>文本</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td><code>subtitle</code></td>
                            <td>副标题</td>
                            <td>文本</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td><code>show_header</code></td>
                            <td>显示标题区域</td>
                            <td>true, false</td>
                            <td>true</td>
                        </tr>
                        <tr>
                            <td><code>logo_style</code></td>
                            <td>Logo样式</td>
                            <td>default, theme, muted, transparent</td>
                            <td>theme</td>
                        </tr>
                        <tr>
                            <td><code>logo_size</code></td>
                            <td>Logo大小（像素）</td>
                            <td>数字</td>
                            <td>100</td>
                        </tr>
                        <tr>
                            <td><code>custom_logos</code></td>
                            <td>自定义Logo</td>
                            <td>文本</td>
                            <td>-</td>
                        </tr>
                        <tr>
                            <td><code>animation_speed</code></td>
                            <td>动画速度（秒）</td>
                            <td>数字</td>
                            <td>30</td>
                        </tr>
                        <tr>
                            <td><code>pause_on_hover</code></td>
                            <td>鼠标悬停暂停</td>
                            <td>true, false</td>
                            <td>true</td>
                        </tr>
                    </tbody>
                </table>
                
                <h4>Logo轮播短码示例</h4>
                <div class="yyk-code-block">
                    <code>[yyk_logo_carousel]
[yyk_logo_carousel title="合作伙伴" subtitle="我们与以下品牌合作"]
[yyk_logo_carousel logo_style="default" logo_size="80"]
[yyk_logo_carousel custom_logos="https://example.com/logo1.png|https://example.com/link1&#10;https://example.com/logo2.png|https://example.com/link2"]
[yyk_logo_carousel animation_speed="20" pause_on_hover="false"]</code>
                </div>
            </div>
        </div>
        
        <!-- 小工具教程 -->
        <div class="yyk-tutorial-section">
            <h2><span class="dashicons dashicons-admin-generic"></span> 小工具使用教程</h2>
            
            <div class="yyk-tutorial-box">
                <h3>添加小工具</h3>
                <ol>
                    <li>进入 <strong>外观 → 小工具</strong></li>
                    <li>找到你想要的小工具</li>
                    <li>拖动到你想要的侧边栏区域</li>
                    <li>配置小工具选项</li>
                    <li>点击保存</li>
                </ol>
            </div>
            
            <div class="yyk-tutorial-box">
                <h3>应用下载列表小工具</h3>
                <ul>
                    <li><strong>标题</strong> - 小工具显示的标题</li>
                    <li><strong>显示数量</strong> - 显示多少个应用</li>
                    <li><strong>卡片样式</strong> - 选择卡片、游戏盒子或紧凑样式</li>
                    <li><strong>布局方式</strong> - 选择网格或轮播布局</li>
                    <li><strong>分类筛选</strong> - 选择特定分类的应用</li>
                    <li><strong>排序方式</strong> - 按日期、标题或随机排序</li>
                </ul>
            </div>
            
            <div class="yyk-tutorial-box">
                <h3>应用轮播展示小工具</h3>
                <ul>
                    <li><strong>标题</strong> - 小工具显示的标题</li>
                    <li><strong>分类选择</strong> - 选择特定分类的应用</li>
                    <li><strong>应用数量</strong> - 显示多少个应用（1-50）</li>
                    <li><strong>排序方式</strong> - 发布日期/标题/随机</li>
                    <li><strong>排序方向</strong> - 降序/升序</li>
                    <li><strong>显示查看全部按钮</strong> - 是否显示查看全部按钮</li>
                </ul>
            </div>
            
            <div class="yyk-tutorial-box">
                <h3>应用视频展示小工具</h3>
                <ul>
                    <li><strong>标题</strong> - 小工具显示的标题</li>
                    <li><strong>视频来源</strong> - 随机展示采集的视频/手动添加视频</li>
                    <li><strong>视频数量</strong> - 显示多少个视频（1-20）</li>
                    <li><strong>自定义视频</strong> - 每行一个，格式：视频地址|标题</li>
                </ul>
            </div>
            
            <div class="yyk-tutorial-box">
                <h3>Logo轮播展示小工具</h3>
                <ul>
                    <li><strong>标题</strong> - 小工具显示的标题</li>
                    <li><strong>副标题</strong> - 小工具显示的副标题</li>
                    <li><strong>显示标题区域</strong> - 是否显示标题和副标题</li>
                    <li><strong>Logo样式</strong> - 选择Logo的样式（默认/主题/灰色/透明）</li>
                    <li><strong>Logo大小</strong> - 设置Logo的大小（像素）</li>
                    <li><strong>自定义Logo</strong> - 手动添加Logo，每行一个，格式：图片地址|链接地址</li>
                    <li><strong>动画速度</strong> - 设置轮播动画的速度（秒）</li>
                    <li><strong>鼠标悬停暂停</strong> - 鼠标悬停时是否暂停轮播</li>
                </ul>
            </div>
        </div>
        
        <!-- 归档页教程 -->
        <div class="yyk-tutorial-section">
            <h2><span class="dashicons dashicons-archive"></span> 归档页设置</h2>
            
            <div class="yyk-tutorial-box">
                <h3>访问归档页</h3>
                <p>访问以下地址查看应用归档页：</p>
                <div class="yyk-code-block">
                    <code><?php echo get_post_type_archive_link('yyk_app_download'); ?></code>
                </div>
            </div>
            
            <div class="yyk-tutorial-box">
                <h3>分类页</h3>
                <p>每个分类都有单独的页面，URL格式：</p>
                <div class="yyk-code-block">
                    <code><?php echo home_url('/'); ?>yyk_app_category/分类别名/</code>
                </div>
            </div>
        </div>
        
        <!-- 模板说明 -->
        <div class="yyk-tutorial-section">
            <h2><span class="dashicons dashicons-layout"></span> 模板文件</h2>
            
            <div class="yyk-tutorial-box">
                <h3>模板位置</h3>
                <p>所有模板文件位于：<code>wp-content/plugins/app-download-manager/public/templates/</code></p>
                <ul>
                    <li><strong>archive-app.php</strong> - 归档页模板</li>
                    <li><strong>single-app.php</strong> - 详情页模板</li>
                    <li><strong>card.php</strong> - 卡片样式模板</li>
                    <li><strong>gamebox.php</strong> - 游戏盒子样式模板</li>
                    <li><strong>compact.php</strong> - 紧凑样式模板</li>
                </ul>
            </div>
            
            <div class="yyk-tutorial-box">
                <h3>自定义模板</h3>
                <p>如果要自定义模板，将模板文件复制到你的主题目录下即可：</p>
                <div class="yyk-code-block">
                    <code>wp-content/themes/你的主题/archive-app.php</code>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.yyk-tutorial-page {
    max-width: 900px;
}

.yyk-tutorial-section {
    background: white;
    border: 1px solid #e5e5e5;
    border-radius: 8px;
    padding: 25px;
    margin-bottom: 25px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.05);
}

.yyk-tutorial-section h2 {
    margin-top: 0;
    margin-bottom: 20px;
    font-size: 20px;
    color: #1d2327;
    display: flex;
    align-items: center;
    gap: 10px;
}

.yyk-tutorial-section h2 .dashicons {
    color: #2271b1;
}

.yyk-tutorial-box {
    margin-bottom: 25px;
}

.yyk-tutorial-box:last-child {
    margin-bottom: 0;
}

.yyk-tutorial-box h3 {
    font-size: 16px;
    color: #1d2327;
    margin-bottom: 12px;
}

.yyk-tutorial-box h4 {
    font-size: 14px;
    color: #1d2327;
    margin: 15px 0 10px;
}

.yyk-code-block {
    background: #1e1e1e;
    color: #d4d4d4;
    padding: 15px 20px;
    border-radius: 6px;
    margin: 10px 0;
    overflow-x: auto;
}

.yyk-code-block code {
    font-family: 'Consolas', 'Monaco', monospace;
    font-size: 14px;
    color: #e6db74;
}

.yyk-tutorial-table {
    width: 100%;
    border-collapse: collapse;
    margin: 15px 0;
}

.yyk-tutorial-table th,
.yyk-tutorial-table td {
    padding: 12px 15px;
    text-align: left;
    border: 1px solid #e5e5e5;
}

.yyk-tutorial-table th {
    background: #f6f7f7;
    font-weight: 600;
    color: #1d2327;
}

.yyk-tutorial-table tr:nth-child(even) {
    background: #f9f9f9;
}

.yyk-tutorial-table code {
    background: #f0f0f1;
    padding: 2px 6px;
    border-radius: 3px;
    font-size: 13px;
}

.yyk-tutorial-box ol,
.yyk-tutorial-box ul {
    margin-left: 20px;
}

.yyk-tutorial-box li {
    margin-bottom: 8px;
}
</style>
