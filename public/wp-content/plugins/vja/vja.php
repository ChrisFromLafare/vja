<?php
/*
Plugin Name: VJA Plugin
Description: Useful snippets for VJA Site
Version: 2.0
Author: Christian ARNAUD
Text Domain: vja-plugin
*/

/* Modify default meta widget */
/* Create specific RSS feeds getting the posts and the comments published during the last day */
/* Create a shortcode for Tablepress, allowing to properly output the number of items contained in the table */
/* Create a Widget displaying the "run of the month" based the articles in the "inscription" category */

/* Changelog
Version 1.0: initial version
Version 2.0: Run of the month widget added
Version 2.1: deprecated constructors modified

/* =========================================================
Replicate the default meta widget and remove useless links
*/
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class CAD_meta_widget extends WP_Widget {
 
	function __construct() {
	// (constructor) Instantiate the parent object
		parent::__construct( /* Base ID */'CAD_meta_widget', /* Name */'CAD_meta_widget', array( 'description' => __('The default modified meta widget','vja-plugin')));
	}
 
	function form( $instance ) {
		// output the options form on admin
		// i.e. for now only the widget's title
		if ( $instance ) {
			$title = esc_attr( $instance[ 'title' ] );
		}
		else {
			$title = __( 'New title', 'vja-plugin' );
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title'); ?>"><?php _e('Title:','vja-plugin'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title'); ?>" name="<?php echo $this->get_field_name('title'); ?>" type="text" value="<?php echo $title; ?>" />
		</p>
		<?php
	}
 
	function update( $new_instance, $old_instance ) {
		// process widget options to be saved
		$instance = $old_instance;
		$instance['title'] = strip_tags($new_instance['title']);
		return $instance;
	}

    function widget( $args, $instance ) {
		// output the content of the widget
		extract( $args );
        /** @var string $before_title */
        /** @var string $after_title */
        /** @var string $before_widget */
        /** @var string $after_widget */
        $title = apply_filters( 'widget_title', $instance['title'] );
		echo $before_widget;
		if ( !empty( $title ) ) {
            echo $before_title . $title . $after_title; } ?>
		<ul>
			<?php wp_register(); ?>
			<li><?php wp_loginout(); ?></li>
			<li><a href="<?php bloginfo('rss2_url'); ?>" title="<?php echo esc_attr(__('Syndicate this site using RSS 2.0','vja-plugin')); ?>"><?php _e('Entries <abbr title="Really Simple Syndication">RSS</abbr>','vja-plugin'); ?></a></li>
			<li><a href="<?php bloginfo('comments_rss2_url'); ?>" title="<?php echo esc_attr(__('The latest comments to all posts in RSS','vja-plugin')); ?>"><?php _e('Comments <abbr title="Really Simple Syndication">RSS</abbr>','vja-plugin'); ?></a></li>
			<?php wp_meta(); ?>
		</ul>
		<?php echo $after_widget; 
	}
}
 
function CAD_register_meta_widget() {
	// register the plugin's available widgets
	register_widget( 'CAD_meta_widget' );
}


// plug in the widgets registration
add_action( 'widgets_init', 'CAD_register_meta_widget' );

/* Create specific RSS feeds */


add_action('init', 'CAD_custom_rss');

function CAD_custom_rss() {
	add_feed('yesterdayArticles', 'CAD_yesterdayArticles');
	add_feed('yesterdayComments', 'CAD_yesterdayComments');
	add_feed('yesterdayArticlesComments', 'CAD_yesterdayArticlesComments');
	add_feed('autoPost', 'CAD_autoPost');
}

function CAD_yesterdayArticles() {
	get_template_part('rss2','yesterdayarticles');
}

function CAD_yesterdayComments() {
	get_template_part('rss2','yesterdaycomments');
}

function CAD_yesterdayArticlesComments() {
	get_template_part('rss2','yesterdayarticlescomments');
}

function CAD_autoPost(){
	get_template_part('rss2', 'autopost');
}

/* Create shortcode [CAD_registered id= text="la course"]
** id= tablepress table id
** text = string used for output
** output:
** Pas d'inscrit pour "text" le "date de modif du tableau"
** 1 inscrit pour "text" le "date de modif du tableau"
** "nb de lignes du tableau" inscrits pour "text" le 'date de modif du tableau"
*/
function CAD_table_registered_shortcode($atts) {
	extract( shortcode_atts( array(
		'id' => '',
		'text' => __('the run','vja-plugin'),
	), $atts, 'registered'));
    /** @var int $id */
    /** @var string $text */
    if ('' == $id) return "";
	$n = do_shortcode("[table-info id=$id field=\"number_rows\"]");
	$date = do_shortcode("[table-info id=$id field=\"last_modified\"]");
	switch ($n) {
		case 0:
			return "<p>".sprintf(__("<strong>Nobody registered</strong> for %1s (updated on: %2s)","vja-plugin"), $text, $date)."</p>";
		case 1:
			return "<p>".sprintf(__("<strong>1 runner registered</strong> for %1s (updated on: %2s)","vja-plugin"), $text, $date)."</p>".do_shortcode("[table id=$id]");
		default:
			return "<p>".sprintf(__("<strong>%1s runners registered</strong> for %2s (updated on: %2s)", "vja-plugin"), $n, $text, $date)."</p>".do_shortcode("[table id=$id]");
	}
}

add_shortcode("CAD_registered", "CAD_table_registered_shortcode");

/* Create a widget displaying the "course du mois" based on articles
** containing metakeyword "Run_Date" & "Run_Title"
** The articles are only searches in the articles belonging to
** the "inscription" category
**
*/

class CAD_runofthemonth_widget extends WP_Widget {
 
	function __construct() {
	// (constructor) Instantiate the parent object
		parent::__construct( /* Base ID */'CAD_runofthemonth_widget', /* Name */'Run of the month', 
			 array( 'description' => 
			 	__('display the title and links to the "Run of the month"','vja-plugin')));
	}
 
	function form( $instance ) {
		// output the options form on admin
		// i.e. for now only the widget's title
		if ( $instance ) {
			$title_1 = esc_attr( $instance[ 'title_1' ] );
			$title_2 = esc_attr( $instance[ 'title_2' ] );
		}
		else {
			$title_1 = __( 'New title','vja-plugin');
			$title_2 = __( 'New title','vja-plugin');
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title_1'); ?>"><?php _e('Title (singular):','vja-plugin'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title_1'); ?>" name="<?php echo $this->get_field_name('title_1'); ?>" type="text" value="<?php echo $title_1; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('title_2'); ?>"><?php _e('Title (plural):','vja-plugin'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title_2'); ?>" name="<?php echo $this->get_field_name('title_2'); ?>" type="text" value="<?php echo $title_2; ?>" />
		</p>
		<?php
	}
 
	function update( $new_instance, $old_instance ) {
		// process widget options to be saved
		$instance = $old_instance;
		$instance['title_1'] = strip_tags($new_instance['title_1']);
		$instance['title_2'] = strip_tags($new_instance['title_2']);
		return $instance;
	}
 
	function widget( $args, $instance ) {
		global $post, $id;
		// store the posts in an array in order to sort them before displaying
		$run_list = array();
		// Get the most recent posts with "Inscription" as category
		// ordered by descending creation date - 10 should be enough
		$args_posts = array( 'posts_per_page' => 10, 'category_name' => "inscription",
					   'orderby' => 'date', 'order' => 'DESC');
		$myposts = get_posts( $args_posts );
		foreach ( $myposts as $post ) :
			setup_postdata( $post );
			$key="Run_Date";
			$run_date = get_post_meta($id, $key, $single = true);
			if (!empty($run_date) && ($run_date = strtotime($run_date)) && ($run_date >= time())):		
				$key="Run_Title"; 
				$run_title = get_post_meta($id, $key, $single = true);
				$run = array(get_permalink($id),!empty($run_title)?$run_title:get_the_title($id));
				$run_list[$run_date]=$run;
			endif;
		endforeach;
		wp_reset_postdata();
		// If posts found - order the table 
		if (!empty($run_list)) :
			ksort($run_list);
		else :
			$run_list[0] = array("#", __('No run scheduled','vja-plugin'));
		endif;
		// output the content of the widget
		extract( $args );
        /** @var string $before_widget */
        /** @var string $after_widget */
        /** @var string $before_title */
        /** @var string $after_title */
		if (count($run_list) <= 1):
			$title = apply_filters( 'widget_title', $instance['title_1'] );
		else:
			$title = apply_filters( 'widget_title', $instance['title_2'] );
		endif;
		echo $before_widget;
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } ?>
		<ul>
			<?php foreach ($run_list as $k => $val) : ?>
			<li>
				<a href="<?php echo $val[0] ?>"><?php echo $val[1]?></a>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php echo $after_widget; 
	}
}
 
function CAD_register_rotm_widget() {
	// register the plugin's available widgets
	register_widget( 'CAD_runofthemonth_widget' );
}


// plug in the widgets registration
add_action( 'widgets_init', 'CAD_register_rotm_widget' );


/* Create a widget displaying the "club's runs results" based on articles
** containing metakeyword "Run_Date" & "Run_Title"
** The articles are only searches in the articles belonging to
** the "résultat course club" category
** The articles are selected based on the Run_Date which
** should range between the current season (September to August)
**
*/

class CAD_clubrunresult_widget extends WP_Widget {
 
	function __construct() {
	// (constructor) Instantiate the parent object
		parent::__construct( /* Base ID */'CAD_clubrunresult_widget', /* Name */'Club\'s run results', 
			 array( 'description' => 
			 	__('display the title and links to the results of club\'s runs','vja-plugin')));
	}
 
	function form( $instance ) {
		// output the options form on admin
		// i.e. for now only the widget's title
		if ( $instance ) {
			$title_1 = esc_attr( $instance[ 'title_1' ] );
			$title_2 = esc_attr( $instance[ 'title_2' ] );
		}
		else {
			$title_1 = __( 'New title','vja-plugin');
			$title_2 = __( 'New title','vja-plugin');
		}
		?>
		<p>
			<label for="<?php echo $this->get_field_id('title_1'); ?>"><?php _e('Title (singular):','vja-plugin'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title_1'); ?>" name="<?php echo $this->get_field_name('title_1'); ?>" type="text" value="<?php echo $title_1; ?>" />
		</p>
		<p>
			<label for="<?php echo $this->get_field_id('title_2'); ?>"><?php _e('Title (plural):','vja-plugin'); ?></label>
			<input class="widefat" id="<?php echo $this->get_field_id('title_2'); ?>" name="<?php echo $this->get_field_name('title_2'); ?>" type="text" value="<?php echo $title_2; ?>" />
		</p>
		<?php
	}
	
	function update( $new_instance, $old_instance ) {
		// process widget options to be saved
		$instance = $old_instance;
		$instance['title_1'] = strip_tags($new_instance['title_1']);
		$instance['title_2'] = strip_tags($new_instance['title_2']);
		return $instance;
	}
 
	function getLowerBound() {
		$time = getdate();
		if ($time['mon'] >=9) 
			return mktime(0,0,0,9,1,$time['year']);
		else
			return mktime(0,0,0,9,1,$time['year']-1);
	}
 
	function getUpperBound() {
		$time = getdate();
		if ($time['mon'] < 9) 
			return mktime(0,0,0,8,31,$time['year']);
		else
			return mktime(0,0,0,8,31,$time['year']+1);
	}
 
	function widget( $args, $instance ) {
		global $post, $id;
		// store the posts in an array in order to sort them before displaying
		$run_list = array();
		// 
		// Get the most recent posts with "résultat course club" as category
		// 25 should be enough
		$args_posts = array( 'posts_per_page' => 25, 'category_name' => "résultat course club");
		$myposts = get_posts( $args_posts );
		foreach ( $myposts as $post ) :
			setup_postdata( $post );
			$key="Run_Date";
			$run_date = get_post_meta($id, $key, $single = true);
			if (!empty($run_date) && ($run_date = strtotime($run_date)) &&
			($run_date >= $this->getLowerBound()) && ($run_date <= $this->getUpperBound())):		
				$key="Run_Title"; 
				$run_title = get_post_meta($id, $key, $single = true);
				$run = array(get_permalink($id),!empty($run_title)?$run_title:get_the_title($id));
				$run_list[$run_date]=$run;
			endif;
		endforeach;
		wp_reset_postdata();
		// If posts found - order the table 
		if (!empty($run_list)) :
			ksort($run_list);
		else :
			$run_list[0] = array("#", __('No result yet','vja-plugin'));
		endif;
		// output the content of the widget
		extract( $args );
        /** @var string $before_widget */
        /** @var string $after_widget */
        /** @var string $before_title */
        /** @var string $after_title */
		if (count($run_list) <= 1):
			$title = apply_filters( 'widget_title', $instance['title_1'] );
		else:
			$title = apply_filters( 'widget_title', $instance['title_2'] );
		endif;
		echo $before_widget;
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } ?>
		<ul>
			<?php foreach ($run_list as $k => $val) : ?>
			<li>
				<a href="<?php echo $val[0] ?>"><?php echo $val[1]?></a>
			</li>
			<?php endforeach; ?>
		</ul>
		<?php echo $after_widget; 
	}
}
 
function CAD_register_crr_widget() {
	// register the plugin's available widgets
	register_widget( 'CAD_clubrunresult_widget' );
}

// plug in the widgets registration
add_action( 'widgets_init', 'CAD_register_crr_widget' );

// Setup internationalization
add_action('plugins_loaded', 'vja_plugin_load_textdomain');
function vja_plugin_load_textdomain() {
	load_plugin_textdomain( 'vja-plugin', false, dirname( plugin_basename(__FILE__) ) . '/languages' );
}

/**
Round distance to 1, 5, or 10m, depending of the distance
 * @param $speed : speed in km/h
 * @param $time : time in seconds
 * @return rounded distance
 */
function CAD_vma_calc_distance($speed, $time) {
    $d = $speed * $time / 3.6;
    if (400 > $d)
        $e = sprintf("%d", round($d, 0));
    elseif (1000 > $d)
        $e = sprintf("%d", round($d / 5, 0) * 5);
    else
        $e = sprintf("%d", round($d / 10, 0) * 10);
    return $e;
}

function CAD_vma_table($atts, $content = null, $tag = '') {
	$atts = array_change_key_case((array)$atts,CASE_LOWER);
	// Priorité au temps pour l'affichage du tableau'
	if (array_key_exists('distance_effort', $atts) && !array_key_exists('temps_effort', $atts)) 
		$effortdistance = true;
	else 
		$effortdistance = false;
	if (array_key_exists('distance_recup', $atts) && !array_key_exists('temps_recup', $atts)) 
		$recupdistance = true;
	else 
		$recupdistance = false;
	$atts = shortcode_atts(
		array(
			'vitesse_min' => 11.0,
			'vitesse_max' => 17.0,
			'step' => 0.5,
			'temps_effort' => '00:01:00',
			'distance_effort' => 1.0,
			'pourcentage_effort' => 0.8,
			'temps_recup' => '00:01:00',
			'distance_recup' => 0.4,
			'pourcentage_recup' => 0.6
		), $atts, $tag
	);
	/* Valider les attributs du shortcode */
	$atts['temps_effort']=str_pad($atts['temps_effort'],8,"00:00:01", STR_PAD_LEFT);
	$atts['temps_recup']=str_pad($atts['temps_recup'],8,"00:00:01", STR_PAD_LEFT);
	if (!(is_numeric($atts['vitesse_min']) && ($atts['vitesse_min'] <= 25.0) && ($atts['vitesse_min'] >= 9.0))) return '<p>verifier vitesse_min</p>';
	if (!(is_numeric($atts['vitesse_max']) && ($atts['vitesse_max'] <= 25.0) && ($atts['vitesse_max'] >= 9.0))) return '<p>verifier vitesse_max</p>';
	if ($atts['vitesse_min'] > $atts['vitesse_max']) return '<p>verifier vitesse_max > vitesse_min</p>';
	if (!(is_numeric($atts['step']) && ($atts['step'] > 0.0))) return '<p>verifier step</p>';
	if (!(is_numeric($atts['pourcentage_effort']) && ($atts['pourcentage_effort'] > 0) && ($atts['pourcentage_effort'] <= 1.2))) return '<p>verifier pourcentage_effort</p>';
	if (!(is_numeric($atts['pourcentage_recup']) && ($atts['pourcentage_recup'] > 0) && ($atts['pourcentage_recup'] <= 0.8))) return '<p>verifier pourcentage_recup</p>';
	if (!(preg_match('/^\d\d:\d\d:\d\d$/', $atts['temps_effort']))) return '<p>verifier temps_effort</p>';
	if (!(preg_match('/^\d\d:\d\d:\d\d$/', $atts['temps_recup']))) return '<p>verifier temps_recup</p>';
	if (!(is_numeric($atts['distance_effort']) && $atts['distance_effort'] > 0)) return '<p>verifier distance_effort</p>';
	if (!(is_numeric($atts['distance_recup']) && $atts['distance_recup'] > 0)) return '<p>verifier distance_recup</p>';
	

	/* Recupérer les temps en secondes - si la conversion n'est pas possible le temps vaut 0 */
	$temps_effort = DateTime::CreateFromFormat('!h:i:s',$atts['temps_effort']);
	$temps_effort===false?$temps_effort = 0 : $temps_effort = $temps_effort->GetTimestamp();
	$temps_recup = DateTime::CreateFromFormat('!h:i:s', $atts['temps_recup']);
	$temps_recup === false ? $temps_recup = 0 : $temps_recup = $temps_recup->GetTimestamp();
	
	$out = '';
	/* Générer l'entête du tableau */
	$out .= '<table class="vmatabletable">';
	$out .= ' <caption class="vmatablecaption">Temps et distances - ';
	if ($effortdistance)
		$out .= sprintf('Distance effort=%dm @ %2d%% - ',
					$atts['distance_effort'], $atts['pourcentage_effort']*100);
	else
		$out .= sprintf('Temps effort=%s @ %2d%% - ',
					date("H:i:s", $temps_effort), $atts['pourcentage_effort']*100);
	if ($recupdistance)
		$out .= sprintf('Distance recup=%dm @ %2d%%</caption>',
				$atts['distance_recup'], $atts['pourcentage_recup']*100);
	else
		$out .= sprintf('Temps recup=%s @ %2d%%</caption>',
					date("H:i:s", $temps_recup), $atts['pourcentage_recup']*100);
	$out .= ' <colgroup>';
	$out .= '  <col><col><col><col><col><col><col>';
	$out .= ' </colgroup>';
	$out .= ' <thead class="vmatablehead">';
	$out .= '  <tr class="vmatableheadrow">';
	$out .= '   <th class="vmatableheadcol"></th>';
	$out .= '   <th class="vmatableheadcol" colspan="3">Effort</th>';
	$out .= '   <th class="vmatableheadcol" colspan="3">Recupération</th>';
	$out .= '  </tr>';
	$out .= '  <tr class="vmatableheadrow">';
	$out .= '   <th class="vmatableheadcol">VMA<br>Km/h</th>';
	$out .= '   <th class="vmatableheadcol">Vitesse<br>(km/h)</th>';
	$out .= '   <th class="vmatableheadcol">Allure<br>(mn/km)</th>';
	if ($effortdistance)
		$out .= '   <th class="vmatableheadcol">Temps<br>(h:m:s)</th>';
	else
		$out .= '   <th class="vmatableheadcol">Distance<br>(m)</th>';
	$out .= '   <th class="vmatableheadcol">Vitesse<br>(km/h)</th>';
	$out .= '   <th class="vmatableheadcol">Allure<br>(mn/km)</th>';
	if ($recupdistance)
		$out .= '   <th class="vmatableheadcol">Temps<br>(h:m:s)</th>';
	else
		$out .= '   <th class="vmatableheadcol">Distance<br>(m)</th>';
	$out .= '  </tr>';
	$out .= ' </thead>';
	/* Générer le corps du tableau */
	$out .= ' <tbody class="vmatablebody">';
	for ($v = $atts['vitesse_max']; $v >= $atts['vitesse_min']; $v-=$atts['step']) {
		/* Calculer les vitesses arrondies à 1 décimale */
		$ve = round($v*$atts['pourcentage_effort'],1);
		$vr = round($v*$atts['pourcentage_recup'],1);
		if ($effortdistance)
			/* Calculer le temps */
			$e = date('H:i:s', $atts['distance_effort']*3.6/$ve);
		else
			/* Calculer la distance arrondies à 1,5, ou 10 metres en fct de la distance*/
            $e = CAD_vma_calc_distance($ve, $temps_effort);
		if ($recupdistance)
			/* Calculer le temps */
			$r = date('H:i:s', $atts['distance_recup']*3.6/$vr);
		else
			/* Calculer la distance arrondies à 1, 5, 10 metres */
			$r = CAD_vma_calc_distance($vr, $temps_recup);
		$out .= '  <tr class="vmatablebodyrow">';
		$out .= sprintf('   <td class="vmatablebodycol">%.1f</td>',$v);
		$out .= sprintf('   <td class="vmatablebodycol">%.1f</td>',$ve);
		$out .= sprintf('   <td class="vmatablebodycol">%s</td>',date('i:s',3600/$ve));
		$out .= sprintf('   <td class="vmatablebodycol">%s</td>',$e);
		$out .= sprintf('   <td class="vmatablebodycol">%.1f</td>',$vr);
		$out .= sprintf('   <td class="vmatablebodycol">%s</td>',date('i:s',3600/$vr));
		$out .= sprintf('   <td class="vmatablebodycol">%s</td>',$r);
		$out .= '   </tr>';
	}
	$out .= ' </tbody>';
	$out .= '</table>';
	return $out;
}

add_shortcode('CAD_VMA', 'CAD_vma_table');
/* 

   don't display tags starting with underscore _ 

*/
function CAD_exclude_tags($the_tags) {
	$new_tags = [];
	foreach ($the_tags as $tag) {
		if ('post_tag'===$tag->taxonomy && '_' === substr($tag->name,0,1)) continue;
		$new_tags[] = $tag;
	}
	return $new_tags;
}
add_filter('get_the_terms','CAD_exclude_tags');

/* Stop Adding Functions Below this Line */