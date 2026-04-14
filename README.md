# 应用下载管理器 (YYK App Download Manager)

一个专业的WordPress应用下载管理插件，支持多种展示样式、ST手游采集、完整的后台管理系统。

## 🚀 功能特性

### 核心功能
- ✅ **自定义文章类型** - 应用下载（yyk_app_download）
- ✅ **自定义分类法** - 应用分类（yyk_app_category）
- ✅ **三种展示样式** - 卡片样式、游戏盒子样式、紧凑样式
- ✅ **四种布局方式** - 网格布局、轮播布局、列表布局、紧凑轮播
- ✅ **ST手游采集** - 集成SteamSy手游采集接口
- ✅ **小工具支持** - 应用列表小工具
- ✅ **短代码支持** - 灵活的短代码参数
- ✅ **数据统计** - 完整的后台统计面板
- ✅ **系统诊断** - 健康检查和自动修复工具
- ✅ **使用教程** - 详细的使用说明文档

### 展示效果
- 🎨 **现代化设计** - 渐变色彩、圆角卡片、阴影效果
- 📱 **响应式布局** - 完美适配各种屏幕尺寸
- 🎠 **轮播组件** - 流畅的水平滚动轮播
- 🔍 **搜索功能** - 支持关键词搜索
- 📊 **下载统计** - 记录和展示下载次数
- 🎯 **推荐标识** - 热门、推荐等标签

## 📦 系统要求

- WordPress 5.0 或更高版本
- PHP 7.0 或更高版本
- MySQL 5.6 或更高版本

## 🛠️ 安装说明

### 方法一：通过WordPress后台安装
1. 下载插件压缩包
2. 登录WordPress后台
3. 进入「插件」→「安装插件」→「上传插件」
4. 选择压缩包并上传
5. 激活插件

### 方法二：通过FTP安装
1. 解压插件压缩包
2. 将 `app-download-manager` 文件夹上传到 `/wp-content/plugins/` 目录
3. 登录WordPress后台
4. 进入「插件」页面，找到「应用下载管理器」并激活

## 📖 使用教程

### 1. 添加应用

1. 进入后台「应用下载」→「添加应用」
2. 填写应用信息：
   - 应用名称（标题）
   - 应用介绍（内容）
   - 应用图标（特色图片）
   - 版本号
   - 应用大小
   - 下载次数
   - 下载地址
   - 平台（Android/iOS/Windows/双端）
   - 应用状态（正常/热门/推荐）
3. 选择分类
4. 发布

### 2. 短代码使用

#### 基础短码
```
[yyk_app_list]
```

#### 所有可用参数
| 参数 | 说明 | 可选值 | 默认值 |
|------|------|--------|--------|
| `count` | 显示数量 | 数字 | 6 |
| `style` | 卡片样式 | card, gamebox, compact | card |
| `layout` | 布局方式 | grid, carousel, list, compact-carousel | grid |
| `columns` | 列数 | 1-12 | 3 |
| `category` | 分类ID或slug | 分类ID或slug | - |
| `orderby` | 排序方式 | date, title, rand, modified | date |
| `order` | 排序方向 | ASC, DESC | DESC |
| `show_title` | 是否显示标题 | true, false | true |

#### 实用示例
```
// 显示最新8个应用（卡片样式）
[yyk_app_list count="8" style="card"]

// 显示游戏盒子样式
[yyk_app_list count="6" style="gamebox"]

// 轮播布局
[yyk_app_list layout="carousel"]

// 指定分类
[yyk_app_list category="games"]

// 随机排序
[yyk_app_list orderby="rand"]
```

### 3. 小工具使用

1. 进入「外观」→「小工具」
2. 找到「应用下载列表」小工具
3. 拖拽到侧边栏或其他 widget 区域
4. 配置参数：
   - 标题
   - 显示数量
   - 卡片样式
   - 分类筛选

### 4. ST手游采集

1. 进入「应用下载」→「游戏采集」
2. 在「采集教程」标签页查看使用说明
3. 在「设置」标签页配置采集参数
4. 在「采集」标签页输入游戏ID或链接进行采集
5. 在「发布管理」标签页管理已采集的游戏

## 🎨 模板文件

插件提供以下模板文件，可在主题中覆盖：

```
wp-content/themes/your-theme/
├── archive-app.php          // 应用归档页
├── single-app.php           // 应用详情页
└── yyk-app-download/
    ├── card.php            // 卡片样式
    ├── gamebox.php         // 游戏盒子样式
    └── compact.php         // 紧凑样式
```

## 🔧 系统诊断

插件提供完整的系统诊断功能：

- ✅ 系统环境检查
- ✅ WordPress版本检查
- ✅ PHP版本检查
- ✅ 插件状态检查
- ✅ 自动修复工具
- ✅ 调试信息导出

进入「应用下载」→「系统诊断」使用。

## 📊 数据统计

插件提供完整的数据统计面板：

- 📈 已发布应用数量
- 📂 游戏分类数量
- 🎮 ST采集游戏总数
- ✅ 已发布ST游戏
- ⏸️ 未发布ST游戏
- 📋 最近10个应用
- 🏷️ 热门分类排行

进入「应用下载」→「数据统计」查看。

## 🔌 钩子（Hooks）

### 动作钩子（Actions）
```php
// 激活插件时
do_action('yyk_app_download_activated');

// 停用插件时
do_action('yyk_app_download_deactivated');

// 记录下载时
do_action('yyk_app_download_recorded', $post_id, $user_id);
```

### 过滤器钩子（Filters）
```php
// 过滤应用列表查询参数
apply_filters('yyk_app_list_query_args', $args);

// 过滤应用卡片HTML
apply_filters('yyk_app_card_html', $html, $post_id, $style);

// 过滤下载地址
apply_filters('yyk_app_download_url', $url, $post_id);
```

## 👥 开发者信息

- **作者**: 壹元库
- **版本**: 1.0.0
- **许可证**: GPL v2 or later
- **Text Domain**: yyk-app-download

## 🐛 常见问题

### Q: 插件安装后没有显示菜单？
A: 请访问「系统诊断」页面，点击「注册文章类型」按钮进行修复。

### Q: 应用详情页404错误？
A: 进入「设置」→「固定链接」，点击「保存更改」刷新重写规则。

### Q: 如何修改应用卡片样式？
A: 可以使用短代码的 `style` 参数，或在主题中覆盖模板文件。

### Q: ST采集失败怎么办？
A: 请检查API密钥是否正确，网络连接是否正常，查看系统诊断页面的错误日志。

## 📄 许可证

本插件采用 GPL v2 或更高版本许可证。详见 [LICENSE](LICENSE) 文件。

## 🤝 贡献

欢迎提交 Issue 和 Pull Request！

## 📞 支持

如有问题，请访问插件支持页面或联系作者。

---

**享受使用应用下载管理器！** 🎉
