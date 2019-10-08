<?php

   /**
     * Provides a consistent way to enqueue all administrative-related stylesheets.
     *
     * Implements the Assets_Interface by defining the init function and the
     * enqueue function.
     *
     * The first is responsible for hooking up the enqueue
     * callback to the proper WordPress hook. The second is responsible for
     * actually registering and enqueuing the file.
     *
     * @implements Assets_Interface
     * @since      0.2.0
     */

namespace VjaDirectory\Util;


class JsLoader implements AssetsInterface
{

    /**
     * Registers the 'enqueue' function with the proper WordPress hook for
     * registering stylesheets.
     */
    public function init() {
        add_action(
            'wp_enqueue_scripts',
            array( $this, 'enqueue' )
        );

    }

    /**
     * Defines the functionality responsible for loading the file.
     */
    public function enqueue() {
        // Add the script only if the page template is 'page-directory-1.php'
        // and the user is logged in
        if (is_page() && is_user_logged_in()) {
            global $wp_query;

            $template_name = get_post_meta( $wp_query->post->ID, '_wp_page_template', true );
            if($template_name == 'page-directory1.php') {


                wp_register_script('cad-directory-script',
                    plugins_url('main.js', VJADIRECTORY_PLUGIN_PATH . "/js/directory/main.js"),
                    array('jquery', 'underscore', 'backbone', 'wp-api'),
                    false,
                    //           filemtime( VJADIRECTORY_PLUGIN_PATH . '/js/directory/main.js'),
                    true // Load JS in footer so that templates in DOM can be referenced.
                );
                $data = array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('cad-directory'),
                    'data' => array(),
                    'nomember' => __('No member found', 'vja_dir')
                );
                wp_localize_script('cad-directory-script', 'VjaJS', $data);

                wp_enqueue_script(
                    'cad-directory-script'
                );
            }
        }

    }
}