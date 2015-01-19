<?php
/* 
Plugin Name: JMS Rss Feed
Plugin URI: http://www.jmsliu.com/products/jms-rss-feed
Description: Adds the featured image tag <jms-featured-image> to your posts to the RSS feed.
Author: James Liu
Version: 1.0
Author URI: http://jmsliu.com/
*/

	add_action('rss2_item', 'add_jms_img_rss_node');

	function add_jms_img_rss_node() {
		global $post;
		if(has_post_thumbnail($post->ID)) {
			$thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), "full");
			echo "<jms-featured-image>".$thumbnail[0]."</jms-featured-image>";
		}
	}
?>