<?php

/**
 * Provide a public-facing view for the plugin
 *
 * This file is used to markup the public-facing aspects of the plugin.
 *
 * @link       http://example.com
 * @since      1.0.0
 *
 * @package    Beautiful_Taxonomy_Filters
 * @subpackage Beautiful_Taxonomy_Filters/public/partials
 */
 
 /*
 * Filters in this file
 * @ beautiful_filters_post_types
 * @ beautiful_filters_post_types
 * @ beautiful_filters_clear_all
 * @ beautiful_filters_dropdown_categories
 * @ beautiful_filters_taxonomy_label
 */
  /*
 * Actions in this file
 * @ beautiful_actions_before_form
 * @ beautiful_actions_beginning_form
 * @ beautiful_actions_end_form
 * @ beautiful_actions_after_form
 */
?>
<?php 
//Make sure we find the current post type! Put it as a hidden form input. This assures us that we'll know where to redirect inside the plugin at all times
// $current_post_type is used from the function where this file is included.
// $current_post_type_rewrite is used from the function where this file is included.

//Fetch the available settings for the filter modules behaviour
$show_clear_all = apply_filters( 'beautiful_filters_clear_all', get_option('beautiful_taxonomy_filters_clear_all'), $current_post_type );
$hide_empty = apply_filters( 'beautiful_filters_hide_empty', get_option('beautiful_taxonomy_filters_hide_empty'), $current_post_type );
$show_count = apply_filters( 'beautiful_filters_show_count', get_option('beautiful_taxonomy_filters_show_count'), $current_post_type );
$dropdown_behaviour = apply_filters( 'beautiful_filters_dropdown_behaviour', get_option('beautiful_taxonomy_filters_dropdown_behaviour'), $current_post_type );
$disable_select2 = (get_option('beautiful_taxonomy_filters_disable_select2') ? get_option('beautiful_taxonomy_filters_disable_select2') : false); 

//Get the taxonomies of the current post type and the excluded taxonomies
$excluded_taxonomies = apply_filters( 'beautiful_filters_taxonomies', get_option('beautiful_taxonomy_filters_taxonomies') ); 
//Also make sure we don't try to output the builtin taxonomies since they cannot be supported
if(is_array($excluded_taxonomies)){
	array_push($excluded_taxonomies, 'category', 'post_tag', 'post_format');
}else{
	$excluded_taxonomies = array(
		'category',
		'post_tag',
		'post_format'
	);
}

//Polylang support
if(function_exists('pll_current_language')){
	array_push($excluded_taxonomies, 'language', 'post_translations');
}

$current_taxonomies = get_object_taxonomies($current_post_type, 'objects');
//If we both have taxonomies on the post type AND we've set som excluded taxonomies in the plugins settings. Loop through them and unset those we don't want!
if($current_taxonomies && $excluded_taxonomies){
	foreach($current_taxonomies as $key => $value){
		if(in_array($key, $excluded_taxonomies)){
			unset($current_taxonomies[$key]);
		}
	}
}

?>
<div class="beautiful-taxonomy-filters" id="beautiful-taxonomy-filters-<?php echo $current_post_type_rewrite; ?>">
	<?php do_action( 'beautiful_actions_before_form', $current_post_type); //Allow custom markup before form ?>
	<form method="POST" class="clearfix" id="beautiful-taxonomy-filters-form">
		<input type="hidden" name="site-url" value="<?php echo get_bloginfo('url'); ?>" />
		<input type="hidden" name="post_type_rewrite" value="<?php echo $current_post_type_rewrite; ?>" />
		<input type="hidden" name="post_type" value="<?php echo $current_post_type; ?>" />
		<?php do_action( 'beautiful_actions_beginning_form', $current_post_type); //allow custom markup at beginning of form ?>
		<?php
		//Loop through the taxonomies and output their terms in a select dropdown 
		$count = count($current_taxonomies);	
		?>
		<div class="beautiful-taxonomy-filters-select-wrap clearfix">
			<?php do_action( 'beautiful_actions_beginning_form_inner', $current_post_type); //allow custom markup at beginning of form ?>
			<?php foreach($current_taxonomies as $key => $taxonomy): ?>
				<?php $terms = get_terms($key); ?>
				<?php if(!empty($terms) && !is_wp_error($terms)): ?>
					<div class="beautiful-taxonomy-filters-tax filter-count-<?php echo $count; if($count > 5){ echo ' filter-count-many'; } ?>" id="beautiful-taxonomy-filters-tax-<?php echo $key; ?>">
						<label for="select-<?php echo $key; ?>" class="beautiful-taxonomy-filters-label"><?php echo apply_filters( 'beautiful_filters_taxonomy_label', $taxonomy->labels->name, $taxonomy->name); ?></label>
						<?php
						/**
						* Output the dropdown with the terms of the taxonomy. 
						* Uses walker found in: public/class-beautiful-taxonomy-filters-walker.php
						*/
						$dropdown_args = array(
							'show_option_all' => $taxonomy->labels->all_items,
							'taxonomy'      => $key,
							'name'          => 'select-'.$key, //BUG?? For some reason we can't use the actual taxonomy slugs. If we do wordpress automatically fetches the correct posts without us even changing the URL HOWEVER it all breaks when the term has a non standard latin character in its name (not even in the slug which is what we actually use) such as åäö
							'show_count'    => $show_count,
							'hide_empty'    => $hide_empty,
							'orderby'       => apply_filters( 'beautiful_filters_dropdown_orderby', 'name', $key ),
							'order' 		=> apply_filters( 'beautiful_filters_dropdown_order', 'ASC', $key ),
							'hierarchical'  => true,
							'echo'          => false,
							'class'			=> 'beautiful-taxonomy-filters-select',
							'walker'        => new Walker_Slug_Value_Category_Dropdown
						);
						//Apply filter on the arguments to let users modify them first!
						$dropdown_args = apply_filters( 'beautiful_filters_dropdown_categories', $dropdown_args, $taxonomy->name );
						
						//But if they've selected placeholder we cant use the show_option_all (they still have to use select2 tho)
						if(!$disable_select2 && $dropdown_behaviour == 'show_placeholder_option'){
							$dropdown_args['show_option_all'] = ' ';
						}
						
						//create the dropdown
						$filterdropdown = wp_dropdown_categories( $dropdown_args );
						
						//If they didnt select placeholder just output the dropdown now (or if they've disabled select2)
						if($disable_select2 || !$dropdown_behaviour || $dropdown_behaviour == 'show_all_option'){
							echo $filterdropdown;
						}else{
							
							//They selected placeholder so now we need to choose what to display and then alter the dropdown before output.
							$new_label = apply_filters( 'beautiful_filters_dropdown_placeholder', $taxonomy->labels->all_items, $taxonomy->name );
							$filterdropdown = str_replace("value='0' selected='selected'", "", $filterdropdown);
							echo str_replace('<select ', '<select data-placeholder="' . $new_label . '"', $filterdropdown);
						}
						
						?>
					</div>
				<?php endif; ?>
			<?php endforeach; ?>
			<?php do_action( 'beautiful_actions_ending_form_inner', $current_post_type); //allow custom markup at end of inner form ?>
		</div>
		<?php do_action( 'beautiful_actions_before_submit_button', $current_post_type); //allow custom markup before submit button ?>
		<button type="submit" class="beautiful-taxonomy-filters-button"><?php echo apply_filters( 'beautiful_filters_apply_button', __('Apply filter', 'beautiful-taxonomy-filters') ); ?></button>
		<?php if($show_clear_all): ?>
			<a href="<?php echo get_post_type_archive_link($current_post_type); ?>" class="beautiful-taxonomy-filters-clear-all" title="<?php _e('Click to clear all active filters', 'beautiful-taxonomy-filters'); ?>"><?php _e('Clear all', 'beautiful-taxonomy-filters'); ?></a>
		<?php endif; ?>
		<?php do_action( 'beautiful_actions_ending_form', $current_post_type); //allow custom markup at beginning of form ?>
	</form>
	<?php do_action( 'beautiful_actions_after_form', $current_post_type); //Allow custom markup after form ?>
</div>