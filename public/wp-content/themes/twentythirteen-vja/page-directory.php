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
 Template Name: Directory
 */

get_header(); ?>

	<div id="primary" class="content-area">
		<div id="content" class="site-content" role="main">

			<?php /* The loop */
			global $cadVjaDirectory;
			$page_number = max( 1, get_query_var('paged') );
			$cadVjaDirectory->page_length = 20;
			$sport = isset($_POST['sport'])?$_POST['sport']:'';
			$cadVjaDirectory->sport_filter = $sport;
			$results = $cadVjaDirectory->get_users($page_number);
			?>
            <div name="vjaDir-directorylist">
			    <legend><?php echo __('Number of members: ','vja_dir').$cadVjaDirectory->get_users_total()?> </legend>
                <form name="form" method="post" action="." id="" class="form">
                    <select id="sport" name="sport" class="dropdown" onchange="this.form.submit();">
                        <option value="" <?php echo (''==$sport)?'selected':'';?>> <?php _e("All members", "vja_dir"); ?></option>
                        <option value="Course" <?php echo ('Course'==$sport)?'selected':'';?>> <?php _e("Runners", "vja_dir"); ?></option>
                        <option value="Marche" <?php echo ('Marche'==$sport)?'selected':'';?>> <?php _e("Walkers", "vja_dir"); ?></option>
                    </select>
                </form>
                <?php
                // User Loop
                if ( ! empty( $results ) ) {
                    foreach ( $results as $user ) {
                        echo $user->entry();
                    }
                } else {
                    _e('No users found.', 'vja_dir');
                } ?>
                <div id="pagination" class="clearfix">
                    <?php echo paginate_links( array(
                      'base' => get_pagenum_link(1) . '%_%',
                      'format' => 'page/%#%/',
                      'prev_text' => __('Previous Page'), // text for previous page
                      'next_text' => __('Next Page'), // text for next page
                      'current' => $page_number,
                      'total' => $cadVjaDirectory->get_total_pages(),
                    ) ); ?>
                </div>
            </div>
		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_sidebar('courses'); ?>
<?php get_footer(); ?>