<?php
//namespace VjaDirectory;
/**
 * Plugin Name: VJA Directory
 * Description: Directory of VJA members
 * Version: 1.0.0
 * Author: Christian ARNAUD
 */
use VjaDirectory\Users;
use VjaDirectory\Util;
use VjaDirectory\AdminPage;
use VjaDirectory\Directory;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'VJADIRECTORY_PLUGIN_PATH', plugin_dir_path(__FILE__ ) );

require_once( trailingslashit( dirname( __FILE__ ) ) . "vendor/GUMP-master/gump.class.php" );

register_activation_hook(   __FILE__, array( 'VJADirectorySetup', 'on_activation' ) );
register_deactivation_hook( __FILE__, array( 'VJADirectorySetup', 'on_deactivation' ) );
//register_uninstall_hook(    __FILE__, array( 'VJADirectorySetup', 'on_uninstall' ) );

require_once( trailingslashit( dirname( __FILE__ ) ) . 'Inc/autoloader.php' );

add_action( 'plugins_loaded', array('VJADirectorySetup','init') );

/**
 * Starts the plugin
 * 
 */
function vja_directory_init() {
}


class VJADirectorySetup
{
    protected static $instance;

    public static function init()
    {
        load_plugin_textdomain( 'vja_dir', false, dirname( plugin_basename(__FILE__) ) . '/languages' );
        is_null( self::$instance ) AND self::$instance = new self;
        return self::$instance;
    }

    public static function on_activation()
    {
        if ( ! current_user_can( 'activate_plugins' ) )
            return;
        $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
        check_admin_referer( "activate-plugin_{$plugin}" );

        # Check whether the default avatars have been loaded and remove then if needed
        if ($avatar = get_option('VjaDirDefaultAvatar')) {
            wp_delete_post($avatar['male'], true);
            wp_delete_post($avatar['female'],true);
        }
        # add them again
        add_filter('upload_dir',array('VJADirectorySetup','upload_dir'));
        copy(trailingslashit(VJADIRECTORY_PLUGIN_PATH)."images/avatar-female.png",(VJADIRECTORY_PLUGIN_PATH)."images/avatar-female-copy.png");
        copy(trailingslashit(VJADIRECTORY_PLUGIN_PATH)."images/avatar-male.png",(VJADIRECTORY_PLUGIN_PATH)."images/avatar-male-copy.png");
        $file_array = array(
            'name' => 'avatar-female.png',
            'tmp_name' => trailingslashit(VJADIRECTORY_PLUGIN_PATH)."images/avatar-female-copy.png"
        );
        $Avatar['female']=media_handle_sideload($file_array,0);
        $file_array = array(
            'name' => 'avatar-male.png',
            'tmp_name' => trailingslashit(VJADIRECTORY_PLUGIN_PATH)."images/avatar-male-copy.png"
        );
        $Avatar['male']=media_handle_sideload($file_array,0);
        remove_filter('upload_dir',array('VJADirectorySetup','upload_dir'));
        update_option('VjaDirDefaultAvatar',$Avatar);
    }

    public static function upload_dir($upload){
             $upload['subdir'] = '/avatars';
            $upload['path'] = $upload['basedir'] . $upload['subdir'];
            $upload['url'] = $upload['baseurl'] . $upload['subdir'];
        return $upload;
    }

    public static function on_deactivation()
    {
        if ( ! current_user_can( 'activate_plugins' ) )
            return;
        $plugin = isset( $_REQUEST['plugin'] ) ? $_REQUEST['plugin'] : '';
        check_admin_referer( "deactivate-plugin_{$plugin}" );
        # Check whether the default avatars have been loaded and remove then if needed
        if ($avatar = get_option('VjaDirDefaultAvatar')) {
            wp_delete_post($avatar['male'], true);
            wp_delete_post($avatar['female'],true);
        }
        delete_option('VjaDirDefaultAvatar');
    }

    public static function on_uninstall()
    {
        if ( ! current_user_can( 'activate_plugins' ) )
            return;
        check_admin_referer( 'bulk-plugins' );

        // Important: Check if the file is the one
        // that was registered during the uninstall hook.
        if ( __FILE__ != WP_UNINSTALL_PLUGIN )
            return;

        # Uncomment the following line to see the function in action
        # exit( var_dump( $_GET ) );
    }

    public function __construct()
    {
        global $cadVjaDirectory;
        $cadVjaDirectory = new Directory\VjaDir();
        $cssLoader = new Util\CSSLoader();
        $cssLoader->init();

        $cssAdminLoader = new Util\CSSAdminLoader();
        $cssAdminLoader->init();

        $jsLoader = new Util\JSLoader();
        $jsLoader->init();

        $cadVjaUserPage = new AdminPage\UserPage();

        $cadVjaAvatar = new Users\VjaAvatar();

        add_shortcode('VjaDirProfile',array('VjaDirectory\Users\VjaProfile','addShortcode'));

    }

}