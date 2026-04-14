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
        </div>
        
        <!-- 小工具教程 -->
        <div class="yyk-tutorial-section">
            <h2><span class="dashicons dashicons-admin-generic"></span> 小工具使用教程</h2>
            
            <div class="yyk-tutorial-box">
                <h3>添加小工具</h3>
                <ol>
                    <li>进入 <strong>外观 → 小工具</strong></li>
                    <li>找到 <strong>YYK 应用下载</strong> 小工具</li>
                    <li>拖动到你想要的侧边栏区域</li>
                    <li>配置小工具选项</li>
                    <li>点击保存</li>
                </ol>
            </div>
            
            <div class="yyk-tutorial-box">
                <h3>小工具选项</h3>
                <ul>
                    <li><strong>标题</strong> - 小工具显示的标题</li>
                    <li><strong>显示数量</strong> - 显示多少个应用</li>
                    <li><strong>卡片样式</strong> - 选择卡片、游戏盒子或紧凑样式</li>
                    <li><strong>布局方式</strong> - 选择网格或轮播布局</li>
                    <li><strong>分类筛选</strong> - 选择特定分类的应用</li>
                    <li><strong>排序方式</strong> - 按日期、标题或随机排序</li>
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
