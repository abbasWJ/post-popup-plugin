<?php

use Elementor\Controls_Manager;


if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly.
}

class Essential_Elementor_Card_Widget extends \Elementor\Widget_Base
{

    public function get_name()
    {
        return 'card';
    }

    public function get_title()
    {
        return esc_html__('Custom Post Grid', 'iq-post-plugin');
    }

    public function get_icon()
    {
        return 'eicon-header';
    }

    public function get_custom_help_url()
    {
        return '';
    }

    public function get_categories()
    {
        return ['general'];
    }

    public function get_keywords()
    {
        return ['card', 'service', 'highlight', 'essential'];
    }

    protected function register_controls()
    {
        $this->start_controls_section(
            'content_section',
            [
                'label' => __('Content', 'iq-post-plugin'),
                'tab' => \Elementor\Controls_Manager::TAB_CONTENT,
            ]
        );

        // Add controls for filter buttons here
        // Example: Categories dropdown
        $this->add_control(
            'categories',
            [
                'label' => __('Categories', 'iq-post-plugin'),
                'type' => \Elementor\Controls_Manager::SELECT2,
                'options' => $this->get_categories_options(),
                'multiple' => true,
                'default' => [],
            ]
        );

        $this->end_controls_section();
    }

    // Function to get category options for dropdown
    private function get_categories_options()
    {
        $categories = get_categories(['taxonomy' => 'category']);
        $options = [];
        foreach ($categories as $category) {
            $options[$category->term_id] = $category->name;
        }
        return $options;
    }

    protected function render()
    {
        $settings = $this->get_settings_for_display();
        // wp_enqueue_style('essential-widget-css', plugin_dir_url(__FILE__) . '../assets/css/base.css');

        // Query posts based on selected categories
        $query_args = [
            'post_type' => 'post',
            'posts_per_page' => 9,
            'category__in' => $settings['categories'],
        ];

        $posts = new WP_Query($query_args);

        if ($posts->have_posts()) {
?>
            <div class="post-filter-widget">
                <div class="filter-buttons">
                    <?php
                        // Display filter buttons for categories
                        $categories = get_categories(array('taxonomy' => 'category'));?>
                        <button class="filter-button" data-category-id="all">All</button>
                        <?php  
                        foreach ($categories as $category) {
                            ?>
                            <button class="filter-button" data-category-id="<?php echo $category->term_id; ?>"><?php echo $category->name; ?></button>
                    <?php } ?>
                </div>
                <div class="posts-wrapper">
                    <?php while ($posts->have_posts()) : $posts->the_post(); ?>
                        <div class="post-box" data-post-id="<?php the_ID(); ?>">
                        <div class="post-thumbnail">
                            <?php the_post_thumbnail( 'medium' ); ?>
                        </div>
                        <h2><?php the_title(); ?></h2>
                        <?php //the_content( 'Read more ...' ); ?>
                        <a href="#" class="open-popup" data-post-id="<?php the_ID(); ?>">Read more</a>
                        </div>
                    <?php endwhile; ?>
                </div>
            </div>
            <div id="popup-container"></div>
            <div class="backdrop"></div>

            <script>
                jQuery(document).ready(function($) {
                    $(document).on('click','.open-popup', function(e) {
                        e.preventDefault();
                        $('#popup-container').show();   
                        $('.backdrop').show();
                        var postID = $(this).data('post-id');
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'load_post_content',
                                post_id: postID,
                                nonce: '<?php echo wp_create_nonce('load_post_content_nonce'); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    var postTitle = response.data.title;
                                    var postContent = response.data.content;

                                    // Populate the popup/modal with the post content
                                    $('#popup-container').html('<div class="popup-content"><span class="close-popup">x</span><h2>' + postTitle + '</h2><div class="content">' + postContent + '</div></div>').show();
                                } else {
                                    console.error('Error:', response.data);
                                }
                            },
                            error: function(xhr, status, error) {
                                console.error(xhr.responseText);
                            }
                        });
                    });

                    $('.backdrop').click(function() {
                        $('#popup-container').hide();
                        $('#popup-container').html('');
                        $('.backdrop').hide();
                    });

                    $(document).on('click','.close-popup', function(){
                        $('#popup-container').hide();
                        $('#popup-container').html('');
                        $('.backdrop').hide();
                    });


                    $('.filter-button').on('click', function () {
                        var categoryId = $(this).data('category-id');
                        $.ajax({
                            url: '<?php echo admin_url('admin-ajax.php'); ?>',
                            type: 'POST',
                            data: {
                                action: 'filter_posts_by_category',
                                category_id: categoryId,
                                nonce: '<?php echo wp_create_nonce('filter_posts_nonce'); ?>'
                            },
                            success: function (response) {
                                if (response.success) {
                                    $('.posts-wrapper').html(response.data.posts);
                                } else {
                                    console.error('Error:', response.data);
                                }
                            },
                            error: function (xhr, status, error) {
                                console.error(xhr.responseText);
                            }
                        });
                    });

                    
                });
            </script>
<?php
            wp_reset_postdata();
        }
    }
}
