<?php
/*============================================================
 =  🚀 项目名称：壹元库应用下载插件
 =  📦 模块名称：文章类型模块
 =  📄 文件：class-post-type.php
 =  👤 作者：壹元库 <815116566@qq.com>
 =  🌐 官网：https://yiyuanku.cn
 =  🔢 版本：1.0.0
 =  📅 日期：2026-04-15
 =  📝 说明：应用下载文章类型和分类法注册类，负责自定义文章类型和分类法的注册和管理
 =  © 版权：2026 壹元库. All Rights Reserved.
 ============================================================*/

if (!defined('ABSPATH')) {
    exit;
}

if (!class_exists('YYK_App_Post_Type')) {

    class YYK_App_Post_Type {
        
        private static $instance = null;
        
        public static function get_instance() {
            if (null === self::$instance) {
                self::$instance = new self();
            }
            return self::$instance;
        }
        
        private function __construct() {}
        
        public function init() {
            add_action('init', [$this, 'register_post_type'], 5); // 优先级5，尽早注册
            add_action('init', [$this, 'register_taxonomy'], 5);
            add_filter('manage_yyk_app_download_posts_columns', [$this, 'add_custom_columns']);
            add_action('manage_yyk_app_download_posts_custom_column', [$this, 'display_custom_columns'], 10, 2);
            
            // 添加批量操作（应用列表）
            add_filter('bulk_actions-edit-yyk_app_download', [$this, 'add_app_bulk_actions'], 999);
            
            // 添加批量操作（分类列表）- 使用正确的过滤器名称
            add_filter('bulk_actions-edit-yyk_app_category', [$this, 'add_category_bulk_actions'], 999);
            
            // 添加批量编辑UI
            add_action('admin_footer', [$this, 'add_category_bulk_edit_ui']);
            
            // 处理批量操作
            add_action('load-edit-tags.php', [$this, 'handle_category_bulk_actions']);
            
            // 添加行操作（编辑、快速编辑）
            add_filter('post_row_actions', [$this, 'add_post_row_actions'], 10, 2);
            add_filter('tag_row_actions', [$this, 'add_category_row_actions'], 10, 2);
            
            // 强制重新注册的钩子
            add_action('yyk_force_register_post_type', [$this, 'register_post_type']);
            add_action('yyk_force_register_taxonomy', [$this, 'register_taxonomy']);
        }
        
        public function add_post_row_actions($actions, $post) {
            if ($post->post_type !== 'yyk_app_download') {
                return $actions;
            }
            
            // 确保编辑、快速编辑、删除都有
            // WordPress默认应该有，但这里可以确保
            return $actions;
        }
        
        public function add_category_row_actions($actions, $tag) {
            if ($tag->taxonomy !== 'yyk_app_category') {
                return $actions;
            }
            
            // 确保分类的编辑、快速编辑、删除、查看都有
            // WordPress默认应该有，但这里可以强制添加
            if (!isset($actions['edit'])) {
                $edit_url = get_edit_term_link($tag->term_id, 'yyk_app_category');
                $actions['edit'] = '<a href="' . esc_url($edit_url) . '">' . __('编辑', 'yyk-app-download') . '</a>';
            }
            if (!isset($actions['inline hide-if-no-js'])) {
                $actions['inline hide-if-no-js'] = '<button type="button" class="button-link editinline">' . __('快速编辑', 'yyk-app-download') . '</button>';
            }
            if (!isset($actions['delete'])) {
                $delete_url = get_delete_term_link($tag->term_id, 'yyk_app_category');
                $actions['delete'] = '<a href="' . esc_url($delete_url) . '" class="submitdelete">' . __('删除', 'yyk-app-download') . '</a>';
            }
            if (!isset($actions['view'])) {
                $view_url = get_term_link($tag->term_id, 'yyk_app_category');
                $actions['view'] = '<a href="' . esc_url($view_url) . '">' . __('查看', 'yyk-app-download') . '</a>';
            }
            
            return $actions;
        }
        
        public function add_app_bulk_actions($actions) {
            // 恢复默认批量操作，确保编辑存在
            $default_actions = [
                'edit' => __('编辑', 'yyk-app-download'),
                'trash' => __('移至回收站', 'yyk-app-download'),
            ];
            
            // 合并默认操作，保留原有操作
            return array_merge($default_actions, $actions);
        }
        
        public function add_category_bulk_actions($actions) {
            // 直接添加，不检查GET参数，因为过滤器会自动处理
            $actions['change_parent'] = __('更改父分类', 'yyk-app-download');
            return $actions;
        }
        
        public function add_category_bulk_edit_ui() {
            // 只在应用分类页面添加UI
            $screen = get_current_screen();
            if (!$screen || $screen->id !== 'edit-yyk_app_category') {
                return;
            }
            ?>
            <script type="text/javascript">
            jQuery(document).ready(function($) {
                // 当选择批量操作时显示父分类选择框
                $('select[name="action"]').on('change', function() {
                    var action = $(this).val();
                    var action2 = $('select[name="action2"]').val();
                    
                    if (action === 'change_parent' || action2 === 'change_parent') {
                        if ($('#yyk-parent-category-select').length === 0) {
                            var selectHtml = '<div id="yyk-parent-category-select" style="display:inline-block; margin-left:10px;">' +
                                '<label for="yyk_new_parent" style="margin-right:5px; font-weight:600;"><?php _e("新父分类:", "yyk-app-download"); ?></label>' +
                                '<select name="yyk_new_parent" id="yyk_new_parent">' +
                                '<option value=""><?php _e("无父分类", "yyk-app-download"); ?></option>' +
                                '<?php 
                                    $categories = get_terms([
                                        "taxonomy" => "yyk_app_category",
                                        "hide_empty" => false,
                                        "hierarchical" => true,
                                    ]);
                                    foreach ($categories as $cat) {
                                        echo "<option value=\"" . $cat->term_id . "\">" . esc_html($cat->name) . "</option>";
                                    }
                                ?>' +
                                '</select>' +
                                '</div>';
                            $('.tablenav.top .actions').append(selectHtml);
                        } else {
                            $('#yyk-parent-category-select').show();
                        }
                    } else {
                        $('#yyk-parent-category-select').hide();
                    }
                });
                
                // 监听action2的变化
                $('select[name="action2"]').on('change', function() {
                    $('select[name="action"]').trigger('change');
                });
            });
            </script>
            <?php
        }
        
        public function handle_category_bulk_actions() {
            // 检查是否是应用分类页面
            if (!isset($_GET['taxonomy']) || $_GET['taxonomy'] !== 'yyk_app_category') {
                return;
            }
            
            // 检查是否有批量操作
            if ((isset($_REQUEST['action']) && $_REQUEST['action'] === 'change_parent') || 
                (isset($_REQUEST['action2']) && $_REQUEST['action2'] === 'change_parent')) {
                
                // 检查权限
                if (!current_user_can('manage_categories')) {
                    wp_die(__('您没有权限执行此操作', 'yyk-app-download'));
                }
                
                // 获取要处理的分类ID
                $term_ids = isset($_REQUEST['delete_tags']) ? array_map('intval', $_REQUEST['delete_tags']) : [];
                
                if (empty($term_ids)) {
                    return;
                }
                
                // 获取新的父分类ID
                $new_parent = isset($_REQUEST['yyk_new_parent']) ? intval($_REQUEST['yyk_new_parent']) : 0;
                
                // 批量更新父分类
                foreach ($term_ids as $term_id) {
                    // 更新分类的父级
                    wp_update_term($term_id, 'yyk_app_category', [
                        'parent' => $new_parent,
                    ]);
                    
                    // 如果有新的父分类，将该分类下的所有游戏也添加到新的父分类中
                    if ($new_parent > 0) {
                        // 获取该分类下的所有游戏
                        $games = get_posts([
                            'post_type' => 'yyk_app_download',
                            'posts_per_page' => -1,
                            'fields' => 'ids',
                            'tax_query' => [
                                [
                                    'taxonomy' => 'yyk_app_category',
                                    'field' => 'term_id',
                                    'terms' => $term_id,
                                ],
                            ],
                        ]);
                        
                        // 为每个游戏添加新的父分类
                        foreach ($games as $game_id) {
                            // 检查游戏是否已经有这个父分类，避免重复
                            if (!has_term($new_parent, 'yyk_app_category', $game_id)) {
                                // 添加新的父分类，不删除现有分类
                                wp_set_object_terms($game_id, [$new_parent], 'yyk_app_category', true);
                            }
                        }
                        
                        // 清理新父分类的计数缓存
                        wp_update_term_count_now([$new_parent], 'yyk_app_category');
                    }
                    
                    // 清理当前分类的计数缓存
                    wp_update_term_count_now([$term_id], 'yyk_app_category');
                }
                
                // 重定向回分类列表
                $redirect_url = add_query_arg([
                    'taxonomy' => 'yyk_app_category',
                    'post_type' => 'yyk_app_download',
                    'bulk_updated' => count($term_ids),
                ], admin_url('edit-tags.php'));
                
                wp_redirect($redirect_url);
                exit;
            }
        }
        
        public function register_post_type() {
            $labels = [
                'name' => _x('应用下载', '应用下载通用名称', 'yyk-app-download'),
                'singular_name' => _x('应用', '应用下载单数名称', 'yyk-app-download'),
                'menu_name' => __('应用下载', 'yyk-app-download'),
                'add_new' => __('添加应用', 'yyk-app-download'),
                'add_new_item' => __('添加应用', 'yyk-app-download'),
                'edit_item' => __('编辑应用', 'yyk-app-download'),
                'view_item' => __('查看应用', 'yyk-app-download'),
                'search_items' => __('搜索应用', 'yyk-app-download'),
                'not_found' => __('没有找到应用', 'yyk-app-download'),
                'all_items' => __('所有应用', 'yyk-app-download'),
                'featured_image' => __('应用图标', 'yyk-app-download'),
                'set_featured_image' => __('设置应用图标', 'yyk-app-download'),
                'remove_featured_image' => __('移除应用图标', 'yyk-app-download'),
            ];
            
            $args = [
                'labels' => $labels,
                'public' => true,
                'publicly_queryable' => true,
                'show_ui' => true,
                'show_in_menu' => true, // 确保显示在菜单中
                'show_in_nav_menus' => true,
                'show_in_admin_bar' => true,
                'query_var' => true,
                'rewrite' => ['slug' => 'app-download', 'with_front' => false],
                'capability_type' => 'post',
                'capabilities' => [
                    'edit_post' => 'edit_post',
                    'read_post' => 'read_post',
                    'delete_post' => 'delete_post',
                    'edit_posts' => 'edit_posts',
                    'edit_others_posts' => 'edit_others_posts',
                    'publish_posts' => 'publish_posts',
                    'read_private_posts' => 'read_private_posts',
                ],
                'map_meta_cap' => true, // 重要：启用元能力映射
                'has_archive' => true,
                'hierarchical' => false,
                'menu_position' => 26, // 在"页面"(20)和"评论"(25)之后
                'menu_icon' => 'dashicons-download',
                'supports' => ['title', 'editor', 'thumbnail', 'excerpt', 'custom-fields', 'author'],
                'show_in_rest' => true, // 支持古腾堡编辑器
                'rest_base' => 'yyk-app-download',
            ];
            
            register_post_type('yyk_app_download', $args);
            
            // 记录日志
            if (current_user_can('manage_options')) {
                error_log('YYK应用下载管理器: 文章类型已注册 - yyk_app_download');
            }
        }
        
        public function register_taxonomy() {
            $labels = [
                'name' => _x('应用分类', '应用分类通用名称', 'yyk-app-download'),
                'singular_name' => _x('应用分类', '应用分类单数名称', 'yyk-app-download'),
                'search_items' => __('搜索分类', 'yyk-app-download'),
                'all_items' => __('所有分类', 'yyk-app-download'),
                'parent_item' => __('父分类', 'yyk-app-download'),
                'parent_item_colon' => __('父分类:', 'yyk-app-download'),
                'edit_item' => __('编辑分类', 'yyk-app-download'),
                'update_item' => __('更新分类', 'yyk-app-download'),
                'add_new_item' => __('添加新分类', 'yyk-app-download'),
                'new_item_name' => __('新分类名称', 'yyk-app-download'),
                'menu_name' => __('应用分类', 'yyk-app-download'),
            ];
            
            $args = [
                'labels' => $labels,
                'hierarchical' => true,
                'public' => true,
                'show_ui' => true,
                'show_admin_column' => true,
                'show_in_nav_menus' => true,
                'show_tagcloud' => false,
                'show_in_rest' => true,
                'rewrite' => ['slug' => 'app-category', 'with_front' => false],
                'capabilities' => [
                    'manage_terms' => 'manage_categories',
                    'edit_terms' => 'manage_categories',
                    'delete_terms' => 'manage_categories',
                    'assign_terms' => 'edit_posts',
                ],
            ];
            
            register_taxonomy('yyk_app_category', ['yyk_app_download'], $args);
            
            // 记录日志
            if (current_user_can('manage_options')) {
                error_log('YYK应用下载管理器: 分类法已注册 - yyk_app_category');
            }
        }
        
        public function add_custom_columns($columns) {
            $new_columns = [];
            
            // 图标在最前面
            $new_columns['yyk_app_icon'] = __('图标', 'yyk-app-download');
            
            foreach ($columns as $key => $value) {
                $new_columns[$key] = $value;
                
                if ('title' === $key) {
                    $new_columns['yyk_app_version'] = __('版本', 'yyk-app-download');
                    $new_columns['yyk_app_status'] = __('状态', 'yyk-app-download');
                }
            }
            
            return $new_columns;
        }
        
        public function display_custom_columns($column, $post_id) {
            switch ($column) {
                case 'yyk_app_icon':
                    $thumbnail_id = get_post_thumbnail_id($post_id);
                    if ($thumbnail_id) {
                        $image = wp_get_attachment_image($thumbnail_id, [60, 60], true, ['style' => 'width: 60px; height: 60px; object-fit: cover; border-radius: 12px;']);
                        if ($image) {
                            echo $image;
                        } else {
                            echo '<span class="dashicons dashicons-smartphone" style="font-size: 48px; color: #d1d5db;"></span>';
                        }
                    } else {
                        $app_meta = get_post_meta($post_id);
                        $found_url = false;
                        foreach ($app_meta as $key => $value) {
                            if (strpos($key, 'icon') !== false && !empty($value[0])) {
                                if (filter_var($value[0], FILTER_VALIDATE_URL)) {
                                    echo '<img src="' . esc_url($value[0]) . '" alt="" style="width: 60px; height: 60px; object-fit: cover; border-radius: 12px;" />';
                                    $found_url = true;
                                    break;
                                }
                            }
                        }
                        if (!$found_url) {
                            echo '<span class="dashicons dashicons-smartphone" style="font-size: 48px; color: #d1d5db;"></span>';
                        }
                    }
                    break;
                    
                case 'yyk_app_version':
                    $version = get_post_meta($post_id, '_yyk_app_version', true);
                    echo $version ? esc_html($version) : '<em>' . __('未设置', 'yyk-app-download') . '</em>';
                    break;
                    
                case 'yyk_app_status':
                    $is_hot = get_post_meta($post_id, '_yyk_app_is_hot', true);
                    $is_recommend = get_post_meta($post_id, '_yyk_app_is_recommend', true);
                    
                    if ($is_hot) {
                        echo '<span class="yyk-badge yyk-hot">' . __('热门', 'yyk-app-download') . '</span> ';
                    }
                    if ($is_recommend) {
                        echo '<span class="yyk-badge yyk-recommend">' . __('推荐', 'yyk-app-download') . '</span>';
                    }
                    if (!$is_hot && !$is_recommend) {
                        echo '<span class="yyk-badge yyk-normal">' . __('普通', 'yyk-app-download') . '</span>';
                    }
                    break;
            }
        }
    }
}