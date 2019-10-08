<?php
function twentythirteen_vja_widgets_init() {
	register_sidebar( array(
		'name'          => __( 'Courses Widget Area', 'twentythirteen-vja' ),
		'id'            => 'sidebar-3',
		'description'   => __( 'Appears on courses pages in the sidebar.', 'twentythirteen-vja' ),
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget'  => '</aside>',
		'before_title'  => '<h3 class="widget-title">',
		'after_title'   => '</h3>',
	) );
}
add_action( 'widgets_init', 'twentythirteen_vja_widgets_init' );
/**
* Extend the default WordPress body classes.
*
* Adds body classes to denote:
* 2. Active widgets in the sidebar to change the layout and spacing.
*
* @param array $classes A list of existing body class values.
* @return array The filtered body class list.
*/
function twentythirteen_vja_body_class( $classes ) {
	if ( is_active_sidebar( 'sidebar-3' ) && ! is_attachment() && ! is_404() )
		$classes[] = 'sidebar';
	return $classes;
}
add_filter( 'body_class', 'twentythirteen_vja_body_class' );
/**
* Modify the login screen header by using a specific style sheet
*
* @param none
* @return none
*/
function twentythirteen_vja_custom_login() {
	if (file_exists(get_stylesheet_directory() .'/customlogin.css'))
		echo '<link rel="stylesheet" type="text/css" href="' . get_stylesheet_directory_uri() .'/customlogin.css" />';
}

add_action('login_head', 'twentythirteen_vja_custom_login');
/**
* Modify the login URL
*
* @param none
* @return string the login URL made of the site's title
**/
function twentythirteen_vja_custom_loginurl() {
	return get_bloginfo('url');
}
add_filter('login_headerurl', 'twentythirteen_vja_custom_loginurl');
/**
* Modify the login title
*
* @param none
* @return string the new title (made of the sites's description)
**/
function twentythirteen_vja_custom_logintitle() {
	return get_bloginfo('description');
}
	add_filter('login_headertitle', 'twentythirteen_vja_custom_logintitle');
//interdire l'accès aux non admins
add_action( 'current_screen', 'redirect_non_authorized_user' );
function redirect_non_authorized_user() {
	// Si t'es pas admin, tu vires
	if ( is_user_logged_in() && ! current_user_can( 'manage_options' ) ) {
		wp_redirect( home_url( '/' ) );
		exit();
	}
}
//Supprimer la barre d'admin sauf pour admin, éditeur, auteur
add_action('after_setup_theme', 'remove_admin_bar');
function remove_admin_bar() {
	if (!current_user_can('edit_published_posts')) {
	show_admin_bar(false);
	}
}
//Changement définition d'un avatar, il peut être utilisé en changeant l'avatar par défaut
function gravatar_perso ($avatar_defaults) {
$myavatar =  get_stylesheet_directory_uri() . '/gravatar-perso.png';
$avatar_defaults[$myavatar] = "VJA - Man";
return $avatar_defaults;
}
add_filter( 'avatar_defaults', 'gravatar_perso' );
//Filtrer la forme d'abonnement aux feeds RSS si on n'est pas loggé
function remove_mc4wp_form_ifnotlogged($content) {
	if (!is_user_logged_in()) {
		$content = "<div>Veuillez vous identifier avant de vous abonner</div>";
	}
	return $content;
}
add_filter('mc4wp_form_content', 'remove_mc4wp_form_ifnotlogged', 10, 1);

// filtrer l'export du plugin WP-members de façon qu'il décode les caractères HTML
function remove_html_entities($args) {
    $args['entity_decode'] = true;
    return $args;
}
add_filter( 'wpmem_export_args', 'remove_html_entities');


?>