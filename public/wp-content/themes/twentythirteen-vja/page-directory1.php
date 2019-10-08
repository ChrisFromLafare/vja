<?php
/**
 * The template for displaying all pages
 *
 * This is the template that displays all pages by default.
 * Please note that this is the WordPress construct of pages and that other
 * 'pages' on your WordPress site will use a different template.
 *
 * @package WordPress
 * @subpackage Twenty_Thirteen_vja
 * @since Twenty Thirteen 1.0
 * 
 Template Name: Directory1
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">

			<?php /* The loop */
			global $cadVjaDirectory;
			$cadVjaDirectory->init_page();
//			$page_number = max( 1, get_query_var('paged') );
            $spinner_url = plugins_url( 'spinner.gif', VJADIRECTORY_PLUGIN_PATH."/images/spinner.gif" )
			?>
            <div id="vja-directory-main">
                <h2 class="page-header text-center"><?php _e('List of members', 'vja_dir'); ?></h2>
                <?php if (is_user_logged_in())  :?>
                <div id="vja-directory-content">
                    <div id="vja-directory-modal">
                        <div id="vja-directory-modal-frame">
                        <img src="<?php echo $spinner_url; ?>">
                        <?php _e("Members are being fetched, please wait", 'vja_dir'); ?>
                        </div>
                    </div>
                    <div id="vja-directory-realcontent"></div>
                </div>
                <?php else: ?>
                <div id="vja-directory-login-error"> <?php _e("You must be logged in to access to the directory","vja-dir"); ?></div>
                <?php endif; ?>
            </div>
		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_sidebar('courses'); ?>
<?php get_footer(); ?>