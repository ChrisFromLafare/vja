<?php
/**
 * Provides a consistent way to enqueue all administrative-related stylesheets.
 */
 
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

class CSSLoader implements AssetsInterface 
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
 
        wp_enqueue_style(
            'cad-directory-style',
            plugins_url( 'cad-directory-style.css', VJADIRECTORY_PLUGIN_PATH."/css/cad-directory-style.css" ),
            array(),
            filemtime( VJADIRECTORY_PLUGIN_PATH . 'css/cad-directory-style.css' )
        );
 
    }
}