<?php

// exit if accessed directly
if( ! defined( 'ABSPATH' ) ) exit;


// check if class already exists
if( !class_exists('hypergram_acf_field_page_link_multisite') ) :


class hypergram_acf_field_page_link_multisite extends acf_field {
	
	
	/*
	*  __construct
	*
	*  This function will setup the field type data
	*
	*  @type	function
	*  @date	5/03/2014
	*  @since	5.0.0
	*
	*  @param	n/a
	*  @return	n/a
	*/
	
	function __construct( $settings ) {
		
		/*
		*  name (string) Single word, no spaces. Underscores allowed
		*/
		
		$this->name = 'page_link_multisite';
		
		
		/*
		*  label (string) Multiple words, can include spaces, visible when selecting a field type
		*/
		
		$this->label = __('Page Link Multisite', 'acf-page-link-multisite');
		
		$this->category = 'relational';
		$this->defaults = array(
			'post_type'			=> array(),
			'taxonomy'			=> array(),
			'site'				=> array(),
			'allow_null' 		=> 0,
			'multiple'			=> 0,
			'allow_archives'	=> 1
		);
		
		/*
		*  l10n (array) Array of strings that are used in JavaScript. This allows JS strings to be translated in PHP and loaded via:
		*  var message = acf._e('page_link_multisite', 'error');
		*/
		
		$this->l10n = array(
			'error'	=> __('Error! Please enter a higher value', 'acf-page-link-multisite'),
		);
		$this->settings = $settings;
		
		
		add_action('wp_ajax_acf/fields/page_link_multisite/query',			array($this, 'ajax_query'));
		add_action('wp_ajax_nopriv_acf/fields/page_link_multisite/query',		array($this, 'ajax_query'));
    	
		// do not delete!
    	parent::__construct();
    	
	}
	
	
	/*
	*  render_field_settings()
	*
	*  Create extra settings for your field. These are visible when editing a field
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/
	
	function render_field_settings( $field ) {

		// default_value
		acf_render_field_setting( $field, array(
			'label'			=> __('Multisite','acf-relationship-multisite'),
			'instructions'	=> __('Select the site to get posts from','acf-relationship-multisite'),
			'type'			=> 'select',
			'name'			=> 'site',
			'choices'		=> $this->acf_get_sites(),
			'multiple'		=> 0,
			'ui'			=> 1,
			'allow_null'	=> 1,
			'placeholder'	=> __("Select site",'acf-relationship-multisite'),
		));

		// post_type
		acf_render_field_setting( $field, array(
			'label'			=> __('Filter by Post Type','acf'),
			'instructions'	=> '',
			'type'			=> 'select',
			'name'			=> 'post_type',
			'choices'		=> acf_get_pretty_post_types(),
			'multiple'		=> 1,
			'ui'			=> 1,
			'allow_null'	=> 1,
			'placeholder'	=> __("All post types",'acf'),
		));
		
		
		// taxonomy
		acf_render_field_setting( $field, array(
			'label'			=> __('Filter by Taxonomy','acf'),
			'instructions'	=> '',
			'type'			=> 'select',
			'name'			=> 'taxonomy',
			'choices'		=> acf_get_taxonomy_terms(),
			'multiple'		=> 1,
			'ui'			=> 1,
			'allow_null'	=> 1,
			'placeholder'	=> __("All taxonomies",'acf'),
		));
		
		
		// allow_null
		acf_render_field_setting( $field, array(
			'label'			=> __('Allow Null?','acf'),
			'instructions'	=> '',
			'name'			=> 'allow_null',
			'type'			=> 'true_false',
			'ui'			=> 1,
		));
		
	}
	
	
	
	/*
	*  render_field()
	*
	*  Create the HTML interface for your field
	*
	*  @param	$field (array) the $field being rendered
	*
	*  @type	action
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$field (array) the $field being edited
	*  @return	n/a
	*/
	
	function render_field( $field ) {
		
		switch_to_blog( $field['site'] );
		$field['type'] = 'select';
		$field['ui'] = 1;
		$field['ajax'] = 1;
		$field['choices'] = array();
		
		
		// populate choices if value exists
		if( !empty($field['value']) ) {
			// get posts
			$posts = $this->get_posts( $field['value'], $field );

			// set choices
			if( !empty($posts) ) {
				
				foreach( array_keys($posts) as $i ) {
					
					// vars
					$post = acf_extract_var( $posts, $i );
					
					
					if( is_object($post) ) {
						
						// append to choices
						$field['choices'][ $post->ID ] = $this->get_post_title( $post, $field );
					
					} else {
						
						// append to choices
						$field['choices'][ $post ] = $post;
												
					}
					
				}
				
			}
			
		}
		
		restore_current_blog();
		
		// render
		acf_render_field( $field );
	}
	
		
	/*
	*  input_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is created.
	*  Use this action to add CSS + JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_enqueue_scripts)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	
	function input_admin_enqueue_scripts() {
		
		// vars
		$url = $this->settings['url'];
		$version = $this->settings['version'];
		
		
		// register & include JS
		wp_register_script('acf-page-link-multisite', "{$url}assets/js/input.js", array('acf-input'), $version);
		wp_enqueue_script('acf-page-link-multisite');
		
		
		// register & include CSS
		wp_register_style('acf-page-link-multisite', "{$url}assets/css/input.css", array('acf-input'), $version);
		wp_enqueue_style('acf-page-link-multisite');
		
	}
	
	
	
	/*
	*  input_admin_head()
	*
	*  This action is called in the admin_head action on the edit screen where your field is created.
	*  Use this action to add CSS and JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_head)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*
		
	function input_admin_head() {
	
		
		
	}
	
	*/
	
	
	/*
   	*  input_form_data()
   	*
   	*  This function is called once on the 'input' page between the head and footer
   	*  There are 2 situations where ACF did not load during the 'acf/input_admin_enqueue_scripts' and 
   	*  'acf/input_admin_head' actions because ACF did not know it was going to be used. These situations are
   	*  seen on comments / user edit forms on the front end. This function will always be called, and includes
   	*  $args that related to the current screen such as $args['post_id']
   	*
   	*  @type	function
   	*  @date	6/03/2014
   	*  @since	5.0.0
   	*
   	*  @param	$args (array)
   	*  @return	n/a
   	*/
   	
   	/*
   	
   	function input_form_data( $args ) {
	   	
		
	
   	}
   	
   	*/
	
	
	/*
	*  input_admin_footer()
	*
	*  This action is called in the admin_footer action on the edit screen where your field is created.
	*  Use this action to add CSS and JavaScript to assist your render_field() action.
	*
	*  @type	action (admin_footer)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*
		
	function input_admin_footer() {
	
		
		
	}
	
	*/
	
	
	/*
	*  field_group_admin_enqueue_scripts()
	*
	*  This action is called in the admin_enqueue_scripts action on the edit screen where your field is edited.
	*  Use this action to add CSS + JavaScript to assist your render_field_options() action.
	*
	*  @type	action (admin_enqueue_scripts)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*
	
	function field_group_admin_enqueue_scripts() {
		
	}
	
	*/

	
	/*
	*  field_group_admin_head()
	*
	*  This action is called in the admin_head action on the edit screen where your field is edited.
	*  Use this action to add CSS and JavaScript to assist your render_field_options() action.
	*
	*  @type	action (admin_head)
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	n/a
	*  @return	n/a
	*/

	/*
	
	function field_group_admin_head() {
	
	}
	
	*/


	/*
	*  load_value()
	*
	*  This filter is applied to the $value after it is loaded from the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value found in the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*  @return	$value
	*/
	
	function load_value( $value, $post_id, $field ) {
		
		return $value;
		
	}
	
	/*
	*  update_value()
	*
	*  This filter is applied to the $value before it is saved in the db
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value found in the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*  @return	$value
	*/
	
	function update_value( $value, $post_id, $field ) {
		
		// Bail early if no value.
		if( empty($value) ) {
			return $value;
		}
		
		// Format array of values.
		// - ensure each value is an id.
		// - Parse each id as string for SQL LIKE queries.
		if( acf_is_sequential_array($value) ) {
			$value = array_map('acf_maybe_idval', $value);
			$value = array_map('strval', $value);
		
		// Parse single value for id.
		} else {
			$value = acf_maybe_idval( $value );
		}
		
		// Return value.
		return $value;
		
	}
	
	/*
	*  format_value()
	*
	*  This filter is appied to the $value after it is loaded from the db and before it is returned to the template
	*
	*  @type	filter
	*  @since	3.6
	*  @date	23/01/13
	*
	*  @param	$value (mixed) the value which was loaded from the database
	*  @param	$post_id (mixed) the $post_id from which the value was loaded
	*  @param	$field (array) the field array holding all the field options
	*
	*  @return	$value (mixed) the modified value
	*/
		
	// function format_value( $value, $post_id, $field ) {
		
	// 	// ACF4 null
	// 	if( $value === 'null' ) {
		
	// 		return false;
			
	// 	}
		
		
	// 	// bail early if no value
	// 	if( empty($value) ) {
			
	// 		return $value;
		
	// 	}
		
		
	// 	// get posts
	// 	$value = $this->get_posts( $value, $field );
		
		
	// 	// set choices
	// 	foreach( array_keys($value) as $i ) {
			
	// 		// vars
	// 		$post = acf_extract_var( $value, $i );
			
			
	// 		// convert $post to permalink
	// 		if( is_object($post) ) {
				
	// 			$post = get_permalink( $post );
			
	// 		}
			
			
	// 		// append back to $value
	// 		$value[ $i ] = $post;
			
	// 	}
		
			
	// 	// convert back from array if neccessary
	// 	if( !$field['multiple'] ) {
		
	// 		$value = array_shift($value);
			
	// 	}
		
		
	// 	// return value
	// 	return $value;
		
	// }
	
	
	
	/*
	*  validate_value()
	*
	*  This filter is used to perform validation on the value prior to saving.
	*  All values are validated regardless of the field's required setting. This allows you to validate and return
	*  messages to the user if the value is not correct
	*
	*  @type	filter
	*  @date	11/02/2014
	*  @since	5.0.0
	*
	*  @param	$valid (boolean) validation status based on the value and the field's required setting
	*  @param	$value (mixed) the $_POST value
	*  @param	$field (array) the field array holding all the field options
	*  @param	$input (string) the corresponding input name for $_POST value
	*  @return	$valid
	*/
	
	/*
	
	function validate_value( $valid, $value, $field, $input ){
		
		// Basic usage
		if( $value < $field['custom_minimum_setting'] )
		{
			$valid = false;
		}
		
		
		// Advanced usage
		if( $value < $field['custom_minimum_setting'] )
		{
			$valid = __('The value is too little!','acf-page-link-multisite'),
		}
		
		
		// return
		return $valid;
		
	}
	
	*/
	
	
	/*
	*  delete_value()
	*
	*  This action is fired after a value has been deleted from the db.
	*  Please note that saving a blank value is treated as an update, not a delete
	*
	*  @type	action
	*  @date	6/03/2014
	*  @since	5.0.0
	*
	*  @param	$post_id (mixed) the $post_id from which the value was deleted
	*  @param	$key (string) the $meta_key which the value was deleted
	*  @return	n/a
	*/
	
	/*
	
	function delete_value( $post_id, $key ) {
		
		
		
	}
	
	*/
	
	
	/*
	*  load_field()
	*
	*  This filter is applied to the $field after it is loaded from the database
	*
	*  @type	filter
	*  @date	23/01/2013
	*  @since	3.6.0	
	*
	*  @param	$field (array) the field array holding all the field options
	*  @return	$field
	*/
	
	/*
	
	function load_field( $field ) {
		
		return $field;
		
	}	
	
	*/
	
	
	/*
	*  update_field()
	*
	*  This filter is applied to the $field before it is saved to the database
	*
	*  @type	filter
	*  @date	23/01/2013
	*  @since	3.6.0
	*
	*  @param	$field (array) the field array holding all the field options
	*  @return	$field
	*/
	
	/*
	
	function update_field( $field ) {
		
		return $field;
		
	}	
	
	*/
	
	
	/*
	*  delete_field()
	*
	*  This action is fired after a field is deleted from the database
	*
	*  @type	action
	*  @date	11/02/2014
	*  @since	5.0.0
	*
	*  @param	$field (array) the field array holding all the field options
	*  @return	n/a
	*/
	
	/*
	
	function delete_field( $field ) {
		
		
		
	}	
	
	*/

	
	/*
	*  ajax_query
	*
	*  description
	*
	*  @type	function
	*  @date	24/10/13
	*  @since	5.0.0
	*
	*  @param	$post_id (int)
	*  @return	$post_id (int)
	*/
	
	function ajax_query() {
		
		// validate
		if( !acf_verify_ajax() ) die();
		
		
		// defaults
   		$options = acf_parse_args($_POST, array(
			'post_id'		=> 0,
			's'				=> '',
			'field_key'		=> '',
			'paged'			=> 1
		));
		
		
   		// vars
   		$results = array();
   		$args = array();
   		$s = false;
   		$is_search = false;
   		
		
		// paged
   		$args['posts_per_page'] = 20;
   		$args['paged'] = $options['paged'];
   		
   		
   		// search
		if( $options['s'] !== '' ) {
			
			// strip slashes (search may be integer)
			$s = wp_unslash( strval($options['s']) );
			
			
			// update vars
			$args['s'] = $s;
			$is_search = true;
			
		}
		
		
		// load field
		$field = acf_get_field( $options['field_key'] );
		if( !$field ) die();
		
		switch_to_blog($field['site']);
		
		// update $args
		if( !empty($field['post_type']) ) {
		
			$args['post_type'] = acf_get_array( $field['post_type'] );
			
		} else {
			
			$args['post_type'] = acf_get_post_types();
			
		}
		
		// create tax queries
		if( !empty($field['taxonomy']) ) {
			
			// append to $args
			$args['tax_query'] = array();
			
			
			// decode terms
			$taxonomies = acf_decode_taxonomy_terms( $field['taxonomy'] );
			
			
			// now create the tax queries
			foreach( $taxonomies as $taxonomy => $terms ) {
			
				$args['tax_query'][] = array(
					'taxonomy'	=> $taxonomy,
					'field'		=> 'slug',
					'terms'		=> $terms,
				);
				
			}
		}
		
		// add archives to $results
		if( $field['allow_archives'] && $args['paged'] == 1 ) {
			
			$archives = array();
			$archives[] = array(
				'id'	=> home_url(),
				'text'	=> home_url()
			);
			
			foreach( $args['post_type'] as $post_type ) {
				
				// vars
				$archive_link = get_post_type_archive_link( $post_type );
				
				
				// bail ealry if no link
				if( !$archive_link ) continue;
				
				
				// bail early if no search match
				if( $is_search && stripos($archive_link, $s) === false ) continue;
				
				
				// append
				$archives[] = array(
					'id'	=> $archive_link,
					'text'	=> $archive_link
				);
				
			}
			
			
			// append
			$results[] = array(
				'text'		=> __('Archives', 'acf'),
				'children'	=> $archives
			);
			
		}
		
		
		// get posts grouped by post type
		$groups = acf_get_grouped_posts( $args );
		
		
		// loop
		if( !empty($groups) ) {
			
			foreach( array_keys($groups) as $group_title ) {
				
				// vars
				$posts = acf_extract_var( $groups, $group_title );
				
				
				// data
				$data = array(
					'text'		=> $group_title,
					'children'	=> array()
				);
				
				
				// convert post objects to post titles
				foreach( array_keys($posts) as $post_id ) {
					
					$posts[ $post_id ] = $this->get_post_title( $posts[ $post_id ], $field, $options['post_id'], $is_search );
					
				}
				
				
				// order posts by search
				if( $is_search && empty($args['orderby']) ) {
					
					$posts = acf_order_by_search( $posts, $args['s'] );
					
				}
				
				
				// append to $data
				foreach( array_keys($posts) as $post_id ) {
					
					$data['children'][] = $this->get_post_result( $post_id, $posts[ $post_id ]);
					
				}
				
				
				// append to $results
				$results[] = $data;
				
			}
			
		}
		
		restore_current_blog();
		
		// return
		acf_send_ajax_results(array(
			'results'	=> $results,
			'limit'		=> $args['posts_per_page']
		));
		
	}
	
	
	/*
	*  get_post_result
	*
	*  This function will return an array containing id, text and maybe description data
	*
	*  @type	function
	*  @date	7/07/2016
	*  @since	5.4.0
	*
	*  @param	$id (mixed)
	*  @param	$text (string)
	*  @return	(array)
	*/
	
	function get_post_result( $id, $text ) {
		
		// vars
		$result = array(
			'id'	=> $id,
			'text'	=> $text
		);
		
		
		// look for parent
		$search = '| ' . __('Parent', 'acf') . ':';
		$pos = strpos($text, $search);
		
		if( $pos !== false ) {
			
			$result['description'] = substr($text, $pos+2);
			$result['text'] = substr($text, 0, $pos);
			
		}
		
		
		// return
		return $result;
			
	}
	
	
	/*
	*  get_post_title
	*
	*  This function returns the HTML for a result
	*
	*  @type	function
	*  @date	1/11/2013
	*  @since	5.0.0
	*
	*  @param	$post (object)
	*  @param	$field (array)
	*  @param	$post_id (int) the post_id to which this value is saved to
	*  @return	(string)
	*/
	
	function get_post_title( $post, $field, $post_id = 0, $is_search = 0 ) {
		
		// get post_id
		if( !$post_id ) $post_id = acf_get_form_data('post_id');
		
		
		// vars
		$title = acf_get_post_title( $post, $is_search );
			
		// return
		return $title;
		
	}
	
	/*
	*  get_posts
	*
	*  This function will return an array of posts for a given field value
	*
	*  @type	function
	*  @date	13/06/2014
	*  @since	5.0.0
	*
	*  @param	$value (array)
	*  @return	$value
	*/
	
	function get_posts( $value, $field ) {
		
		// force value to array
		$value = acf_get_array( $value );
		
		
		// get selected post ID's
		$post__in = array();
		
		foreach( $value as $k => $v ) {
			
			if( is_numeric($v) ) {
				
				// append to $post__in
				$post__in[] = (int) $v;
				
			}
			
		}
		
		
		// bail early if no posts
		if( empty($post__in) ) {
			
			return $value;
			
		}
		
		
		// get posts
		$posts = acf_get_posts(array(
			'post__in' => $post__in,
			'post_type'	=> $field['post_type']
		));
		
		
		// override value with post
		$return = array();
		
		
		// append to $return
		foreach( $value as $k => $v ) {
			
			if( is_numeric($v) ) {
				
				// extract first post
				$post = array_shift( $posts );
				
				
				// append
				if( $post ) {
					
					$return[] = $post;
					
				}
				
			} else {
				
				$return[] = $v;
				
			}
			
		}
		
		
		// return
		return $return;
		
	}
	
	
	function acf_get_sites() {

		$ref = array();
		$sites = get_sites();

		foreach ($sites as $site) {
			$current_blog_details = get_blog_details( array( 'blog_id' => $site->blog_id ) );
			$site_id = $site->blog_id;
			$label = $current_blog_details->blogname;
			$ref[$site_id] = $label;
		}
		return $ref;
	}

}


// initialize
new hypergram_acf_field_page_link_multisite( $this->settings );


// class_exists check
endif;

?>