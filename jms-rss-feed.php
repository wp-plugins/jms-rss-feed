<?php
/* 
Plugin Name: JMS Rss Feed
Plugin URI: http://www.jmsliu.com/products/jms-rss-feed
Description: Adds the featured image tag <jms-featured-image> to your posts to the RSS feed.
Author: James Liu
Version: 2.0
Author URI: http://jmsliu.com/
*/

	add_action('rss2_item', 'add_jms_img_rss_node');
	
	//work together
	add_filter('query_vars', 'add_jms_single_post_var');
	add_action('parse_request', 'return_jms_single_post');

	function add_jms_img_rss_node() {
		global $post;
		if(has_post_thumbnail($post->ID)) {
			$thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), "full");
			echo "<jms-featured-image>".$thumbnail[0]."</jms-featured-image>";
		}
	}
	
	function add_jms_single_post_var($vars) {
		$vars[] = 'jms_rss_post_url';
		return $vars;
	}
	
	function return_jms_single_post($wp) {
		// only process requests with "my-plugin=ajax-handler"
		if (array_key_exists('jms_rss_post_url', $wp->query_vars)) {
			$url = $wp->query_vars['jms_rss_post_url'];
			$postid = url_to_postid( $url );
			$post = get_post($postid);
			//print_r($post);
			echo $post->post_content;
			exit;
		}
	}
?>