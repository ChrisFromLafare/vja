<?php
/* Plugin Name: runMap
Description: Google Map with gpx path
Version: 1.0
Author: Christian ARNAUD
License: GPLv2 or later
*/
function cad_runmap_activation() {
}
register_activation_hook(__FILE__, 'cad_runmap_activation');


function cad_runmap_deactivation() {
}
register_deactivation_hook(__FILE__, 'cad_runmap_deactivation');

// Add the new filter to allow gpx
add_filter('upload_mimes', 'addUploadMimes');
 
/**
 * Adds new supported media types for upload.
 *
 * @see wp_check_filetype() or get_allowed_mime_types()
 * @param array $mimes Array of mime types keyed by the file extension regex corresponding to those types.
 * @return array
 */
function addUploadMimes($mimes)
{
	$mimes = array_merge($mimes, array(
		'gpx' => 'application/gpx+xml'
	));
	return $mimes;
}



add_action('wp_enqueue_scripts', 'cad_runmap_scripts');

function cad_runmap_scripts() {
	wp_register_script('googlemap_core', 'http://maps.googleapis.com/maps/api/js?key=AIzaSyAgVkNMp4O789j9FjdhHiV-Ql3w5WOo9sI&sensor=false', false);
	wp_enqueue_script('googlemap_core');
	wp_deregister_script('jquery');
	wp_register_script('jquery','http://ajax.googleapis.com/ajax/libs/jquery/1/jquery.min.js', false, '');
	wp_enqueue_script('jquery');
  wp_register_script('runmap_core', plugins_url('js/carto.js', __FILE__),array("jquery"));
  wp_enqueue_script('runmap_core');
	$wp_plugin_url = array('plugin_url' => plugins_url('',__FILE__));
	wp_localize_script('runmap_core','wp_params',$wp_plugin_url);
}


add_action('wp_enqueue_scripts', 'cad_runmap_styles');

function cad_runmap_styles() {
  wp_register_style('runmap_css', plugins_url('css/runmap.css', __FILE__));
  wp_enqueue_style('runmap_css');
  wp_register_style('runmap_fonts', plugins_url('css/font-awesome.min.css', __FILE__));
  wp_enqueue_style('runmap_fonts');
}

add_action('admin_enqueue_scripts', 'cad_runmap_admin_scripts');
 
function cad_runmap_admin_scripts() {
//    if (isset($_GET['page']) && $_GET['page'] == 'my_plugin_page') {
        wp_enqueue_media();
			  wp_register_script('runmap_admin', plugins_url('js/runmap_admin.js', __FILE__),array("jquery"));
        wp_enqueue_script('runmap_admin');
//    }
}

add_shortcode("runmap", "cad_runmap_display_map");
function cad_runmap_display_map($atts) {

  $plugins_url = plugins_url();
  $id = get_the_ID();
  $gallery_gpx = get_post_meta($id, "_cad_gallery_gpx", true);
  $gallery_gpx = ($gallery_gpx != '') ? json_decode($gallery_gpx) : array();
  $text = '<div class="container">
    <div id="runmap" class="runmap-div">
    </div>
  </div>';
  $text .= '<script type="text/javascript">
var myparcours = new Parcours();
$(function() {
	var map = new google.maps.Map(
		$("#runmap")[0],
		{center: new google.maps.LatLng(-34.397, 150.644), zoom: 12,}
	);
	var maMap = new runMap("'. $gallery_gpx.'", map);
	maMap.display(function(map) {
		map.setMarker();
	});
})
</script>';
  return $text;
}

add_action('init', 'cad_runmap_register_map');

function cad_runmap_register_map() {
	$labels = array(
		'menu_name' => _x('Runmap', 'runmap_map'),
	);
	$args = array(
		'labels' => $labels,
		'hierarchical' => true,
		'description' => 'RunMaps',
		'supports' => array('title', 'editor'),
		'public' => true,
		'show_ui' => true,
		'show_in_menu' => true,
		'show_in_nav_menus' => true,
		'publicly_queryable' => true,
		'exclude_from_search' => false,
		'has_archive' => true,
		'query_var' => true,
		'can_export' => true,
		'rewrite' => true,
		'capability_type' => 'post'
	);
	register_post_type('runmap_map', $args);
}

add_action('add_meta_boxes', 'cad_runmap_meta_box');

function cad_runmap_meta_box() {
  add_meta_box("cad_runmap_gpx", "GPX File", 'cad_runmap_gpx_box', "runmap_map", "normal");
}

function cad_runmap_gpx_box() {
  global $post;
  $gallery_gpx = get_post_meta($post->ID, "_cad_gallery_gpx", true);
  $gallery_gpx = ($gallery_gpx != '') ? json_decode($gallery_gpx) : array();
  // Use nonce for verification
  $html =  '<input type="hidden" name="cad_runmap_box_nonce" value="'. wp_create_nonce(basename(__FILE__)). '" />';
  $html .= '
'; 
  $html .= '
<label for="cad_runmap_upload">
	<input id="cad_runmap_upload" type="text" name="gallery_gpx" value="'.$gallery_gpx.'" />
	<input id="cad_runmap_upload_gpx_button" class="button" type="button" value="Upload gpx" />
    <br />Enter a URL or upload a gpx	
</label>
';
	echo $html;
}

add_action('save_post', 'cad_runmap_save_gpx_info');

function cad_runmap_save_gpx_info($post_id) {
	// verify nonce
	if (!wp_verify_nonce($_POST['cad_runmap_box_nonce'], basename(__FILE__))) {
		return $post_id;
	}
	// check autosave
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) {
		return $post_id;
	}
	// check permissions
	if ('runmap_map' == $_POST['post_type'] && current_user_can('edit_post', $post_id)) {
		/* Save the map */
		$gallery_gpx = (isset($_POST['gallery_gpx']) ? $_POST['gallery_gpx'] : '');
		$gallery_gpx = strip_tags(json_encode($gallery_gpx));
		update_post_meta($post_id, "_cad_gallery_gpx", $gallery_gpx);
	} else {
		 return $post_id;
	}
}

?>

