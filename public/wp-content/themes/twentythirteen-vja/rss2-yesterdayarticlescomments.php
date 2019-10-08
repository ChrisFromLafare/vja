<?php
/**
 * RSS2 Feed Template for displaying RSS2 Posts & comments.
 * Filtered to posts published in the last days
 *
 */

/**
 * Query posts
 *
 */

global $wp_query;
$args = array(
	'date_query' => array(
		array(
			'after'     => 'yesterday',
			'before'    => 'tomorrow',
			'inclusive' => true,
		),
	),
	'posts_per_page' => -1,
);

/**
 * Query comments
 *
 */

$wp_query = new WP_Query( $args );
$args = array(
	'date_query' => array(
		array(
			'after'     => 'yesterday',
			'before'    => 'tomorrow',
			'inclusive' => true,
		),
	),
);
$comments_query = new WP_Comment_Query;
$comments = $comments_query->query( $args ); 

/**
  * Find the date of latest item
  *
  */
 $datep = mysql2date('U', get_lastpostmodified('GMT'));
 $datec = mysql2date('U', get_lastcommentmodified('GMT'));
 $datelatest = max($datep,$datec);
 
header('Content-Type: ' . feed_content_type('rss2') . '; charset=' . get_option('blog_charset'), true);
$more = 1;

echo '<?xml version="1.0" encoding="'.get_option('blog_charset').'"?'.">\n";

/**
 * Fires between the xml and rss tags in a feed.
 *
 * @since 4.0.0
 *
 * @param string $context Type of feed. Possible values include 'rss2', 'rss2-comments',
 *                        'rdf', 'atom', and 'atom-comments'.
 */
do_action( 'rss_tag_pre', 'rss2' );
?>
<rss version="2.0"
	xmlns:content="http://purl.org/rss/1.0/modules/content/"
	xmlns:wfw="http://wellformedweb.org/CommentAPI/"
	xmlns:dc="http://purl.org/dc/elements/1.1/"
	xmlns:atom="http://www.w3.org/2005/Atom"
	xmlns:sy="http://purl.org/rss/1.0/modules/syndication/"
	xmlns:slash="http://purl.org/rss/1.0/modules/slash/"
	<?php
	/**
	 * Fires at the end of the RSS root to add namespaces.
	 *
	 * @since 2.0.0
	 */
	do_action( 'rss2_ns' );
	?>
>
<channel>
	<title><?php bloginfo_rss('name'); wp_title_rss(); ?></title>
	<atom:link href="<?php self_link(); ?>" rel="self" type="application/rss+xml" />
	<link><?php bloginfo_rss('url') ?></link>
	<description><?php bloginfo_rss("description") ?></description>
	<lastBuildDate><?php echo date('r', $datelatest); ?></lastBuildDate>
	<language><?php bloginfo_rss( 'language' ); ?></language>
	<sy:updatePeriod><?php
		$duration = 'hourly';

		/**
		 * Filter how often to update the RSS feed.
		 *
		 * @since 2.1.0
		 *
		 * @param string $duration The update period. Accepts 'hourly', 'daily', 'weekly', 'monthly',
		 *                         'yearly'. Default 'hourly'.
		 */
		echo apply_filters( 'rss_update_period', $duration );
	?></sy:updatePeriod>
	<sy:updateFrequency><?php
		$frequency = '1';

		/**
		 * Filter the RSS update frequency.
		 *
		 * @since 2.1.0
		 *
		 * @param string $frequency An integer passed as a string representing the frequency
		 *                          of RSS updates within the update period. Default '1'.
		 */
		echo apply_filters( 'rss_update_frequency', $frequency );
	?></sy:updateFrequency>
	<?php
	/**
	 * Fires at the end of the RSS2 Feed Header.
	 *
	 * @since 2.0.0
	 */
	do_action( 'rss2_head');

	while( have_posts()) : the_post();
	?>
	<item>
		<title><?php the_title_rss() ?></title>
		<link><?php the_permalink_rss() ?></link>
		<comments><?php comments_link_feed(); ?></comments>
		<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_post_time('Y-m-d H:i:s', true), false); ?></pubDate>
		<dc:creator><![CDATA[<?php the_author() ?>]]></dc:creator>
		<?php the_category_rss('rss2') ?>

		<guid isPermaLink="false"><?php the_guid(); ?></guid>
<?php if (get_option('rss_use_excerpt')) : ?>
		<description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
<?php else : ?>
		<description><![CDATA[<?php the_excerpt_rss(); ?>]]></description>
	<?php $content = get_the_content_feed('rss2'); ?>
	<?php if ( strlen( $content ) > 0 ) : ?>
		<content:encoded><![CDATA[<?php echo $content; ?>]]></content:encoded>
	<?php else : ?>
		<content:encoded><![CDATA[<?php the_excerpt_rss(); ?>]]></content:encoded>
	<?php endif; ?>
<?php endif; ?>
		<wfw:commentRss><?php echo esc_url( get_post_comments_feed_link(null, 'rss2') ); ?></wfw:commentRss>
		<slash:comments><?php echo get_comments_number(); ?></slash:comments>
<?php rss_enclosure(); ?>
	<?php
	/**
	 * Fires at the end of each RSS2 feed item.
	 *
	 * @since 2.0.0
	 */
	do_action( 'rss2_item' );
	?>
	</item>
	<?php endwhile; 
    if ( !empty( $comments ) ) : foreach ( $comments as $comment ) :
        $comment_post = $GLOBALS['post'] = get_post( $comment->comment_post_ID );
	?>
	<item>
		<title><?php
			if ( !is_singular() ) {
				$title = get_the_title($comment_post->ID);
				/** This filter is documented in wp-includes/feed.php */
				$title = apply_filters( 'the_title_rss', $title );
				printf(ent2ncr(__('Comment on %1$s by %2$s')), $title, get_comment_author_rss());
			} else {
				printf(ent2ncr(__('By: %s')), get_comment_author_rss());
			}
		?></title>
		<link><?php comment_link() ?></link>
		<dc:creator><![CDATA[<?php echo get_comment_author_rss() ?>]]></dc:creator>
		<pubDate><?php echo mysql2date('D, d M Y H:i:s +0000', get_comment_time('Y-m-d H:i:s', true, false), false); ?></pubDate>
		<guid isPermaLink="false"><?php comment_guid() ?></guid>
<?php if ( post_password_required($comment_post) ) : ?>
		<description><?php echo ent2ncr(__('Protected Comments: Please enter your password to view comments.')); ?></description>
		<content:encoded><![CDATA[<?php echo get_the_password_form() ?>]]></content:encoded>
<?php else : // post pass ?>
		<description><![CDATA[<?php comment_text_rss() ?>]]></description>
		<content:encoded><![CDATA[<?php comment_text() ?>]]></content:encoded>
<?php endif; // post pass
	/**
	 * Fires at the end of each RSS2 comment feed item.
	 *
	 * @since 2.1.0
	 *
	 * @param int $comment->comment_ID The ID of the comment being displayed.
	 * @param int $comment_post->ID    The ID of the post the comment is connected to.
	 */
	do_action( 'commentrss2_item', $comment->comment_ID, $comment_post->ID );
?>
	</item>
<?php endforeach; endif; ?>
</channel>
</rss>
