<?php
/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：卸载模块
 =  📄 文件：uninstall.php
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：插件卸载脚本，负责清理插件产生的选项、数据表等数据
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

// 防止直接访问
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// 清理选项
delete_option('yyk_app_default_style');
delete_option('yyk_app_items_per_page');

// 清理文章类型数据（可选，谨慎使用）
// $apps = get_posts([
//     'post_type' => 'yyk_app_download',
//     'numberposts' => -1,
//     'post_status' => 'any'
// ]);
// 
// foreach ($apps as $app) {
//     wp_delete_post($app->ID, true);
// }

// 清理分类数据（可选，谨慎使用）
// $terms = get_terms([
//     'taxonomy' => 'yyk_app_category',
//     'hide_empty' => false
// ]);
// 
// foreach ($terms as $term) {
//     wp_delete_term($term->term_id, 'yyk_app_category');
// }

// 清理重写规则
flush_rewrite_rules();