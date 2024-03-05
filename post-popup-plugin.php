<?php

/**
 * Plugin Name: Elementor Post Popup
 * Description: Elementor custom popup widgets .
 * Plugin URI:  https://github.com/abbasWJ/post-popup-plugin/
 * Version:     1.0.0
 * Author:      Ghulam Abbas
 * Author URI:  https://github.com/abbasWJ
 *
 * Elementor tested up to: 3.19.4
 * Elementor Pro tested up to: 3.19.4
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * Register Widgets.
 *
 * Include widget file and register widget class.
 *
 * @since 1.0.0
 * @param \Elementor\Widgets_Manager $widgets_manager Elementor widgets manager.
 * @return void
 */
function register_essential_custom_widgets( $widgets_manager ) {

    require_once( __DIR__ . '/widgets/post-filter-widget.php' );  // include the widget file

    $widgets_manager->register( new \Essential_Elementor_Card_Widget() );  // register the widget

}
add_action( 'elementor/widgets/register', 'register_essential_custom_widgets' );

// Add AJAX handler
add_action('wp_ajax_load_post_content', 'load_post_content_callback');
add_action('wp_ajax_nopriv_load_post_content', 'load_post_content_callback');

function load_post_content_callback()
{
    check_ajax_referer('load_post_content_nonce', 'nonce');

    $post_id = $_POST['post_id'];
    $post = get_post($post_id);

    if ($post) {
        $post_title = get_the_title($post_id);
        $post_content = apply_filters('the_content', $post->post_content);

        $response = array(
            'title' => $post_title,
            'content' => $post_content,
        );

        wp_send_json_success($response);
    } else {
        wp_send_json_error('Error: Post not found.');
    }
}


// add_filter('the_content', 'strip_images',2);

// function strip_images($content){
//    return preg_replace('/<img[^>]+./','',$content);
// }

// Add AJAX action for filtering posts by category
add_action('wp_ajax_filter_posts_by_category', 'filter_posts_by_category');
add_action('wp_ajax_nopriv_filter_posts_by_category', 'filter_posts_by_category');

function filter_posts_by_category() {
    check_ajax_referer('filter_posts_nonce', 'nonce');

    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;

    $query_args = array(
        'post_type' => 'post',
        'posts_per_page' => 9,
        'category__in' => $category_id,
    );

    $posts_query = new WP_Query($query_args);

    ob_start();
    if ($posts_query->have_posts()) {
        while ($posts_query->have_posts()) {
            $posts_query->the_post();
            ?>
            <div class="post-box" data-post-id="<?php the_ID(); ?>">
                <div class="post-thumbnail">
                    <?php the_post_thumbnail('medium'); ?>
                </div>
                <h2><?php the_title(); ?></h2>
                <a href="#" class="open-popup" data-post-id="<?php the_ID(); ?>">Read more</a>
            </div>
        <?php
        }
    } else {
        echo '<p>No posts found.</p>';
    }
    $posts_content = ob_get_clean();

    wp_reset_postdata();

    wp_send_json_success(array('posts' => $posts_content));
    wp_die();
}

// Function to enqueue stylesheets
function my_plugin_enqueue_styles() {
    // Enqueue your plugin's stylesheet
    wp_enqueue_style('iq-post-grid-styles', plugins_url('assets/css/base.css', __FILE__), array(), '1.0.0', 'all');
}
// Hook into WordPress
add_action('wp_enqueue_scripts', 'my_plugin_enqueue_styles');

// Function to enqueue scripts and styles
function my_plugin_enqueue_scripts() {
    // Enqueue jQuery from the WordPress core
    wp_enqueue_script('jquery');
}

// Hook into WordPress
add_action('wp_enqueue_scripts', 'my_plugin_enqueue_scripts');