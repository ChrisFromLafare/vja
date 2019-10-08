<?php
/**
 * The template for displaying the footer
 *
 * Contains footer content and the closing of the #main and #page div elements.
 *
 * @package WordPress
 * @subpackage Twenty_Thirteen
 * @since Twenty Thirteen 1.0
 */
?>

		</div><!-- #main -->
		<footer id="colophon" class="site-footer" role="contentinfo">
			<?php get_sidebar( 'main' ); ?>

			<div class="site-info">
				<div class="webmaster"><a href="mailto://<?php echo get_bloginfo('admin_email'); ?>">Contact</a></div><!--.webmaster -->
				<div class="proprio">
					Ce site est la propri&eacute;t&eacute; de "Ventabren Jogging Aventure"
				</div>
				<div class="mentions-legales">
					<a href="<?php echo site_url('/informations-legales/'); ?>" title="Mentions légales de www.vja.fr">Mentions l&eacute;gales</a>
				</div>
			</div><!-- .site-info -->
		</footer><!-- #colophon -->
	</div><!-- #page -->

	<?php wp_footer(); ?>
</body>
</html>