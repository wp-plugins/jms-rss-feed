<?php
/* 
Plugin Name: JMS Rss Feed
Plugin URI: http://www.jmsliu.com/products/jms-rss-feed
Description: Adds the featured image tag <jms-featured-image> to your posts to the RSS feed.
Author: James Liu
Version: 3.0
Author URI: http://jmsliu.com/
*/

	global $jms_rss_db_version;
	$jms_rss_db_version = '1.0';

	add_action('rss2_item', 'add_jms_img_rss_node');
	
	//work together
	add_filter('query_vars', 'add_jms_single_post_var');
	add_action('parse_request', 'return_jms_single_post');
	
	//install database
	register_activation_hook( __FILE__, 'install_jms' );

	function add_jms_img_rss_node() {
		global $post;
		if(has_post_thumbnail($post->ID)) {
			$thumbnail = wp_get_attachment_image_src(get_post_thumbnail_id($post->ID), "full");
			echo "<jms-featured-image>".$thumbnail[0]."</jms-featured-image>";
		}
	}
	
	function add_jms_single_post_var($vars) {
		$vars[] = 'jms_rss_post_url';
		$vars[] = 'jms_rss_action';
		$vars[] = 'jms_rss_token';
		return $vars;
	}
	
	function return_jms_single_post($wp) {
		global $wpdb;
		
		
		if (array_key_exists('jms_rss_action', $wp->query_vars)) {
			$action = $wp->query_vars['jms_rss_action'];
			
			if(!empty($action)) {
				if($action == "register_android") {
					$token = $wp->query_vars['jms_rss_token'];
					$table_name = $wpdb->prefix . 'jms_rss_android_listener';
					$wpdb->insert( 
						$table_name, 
						array(
							'device_token' => $token, 
							'state' => 1,
							'reg_date' => current_time('mysql', 1)
						),
						array( 
							'%s', 
							'%d',
							'%s'
						)
					);
					
					echo $wpdb->insert_id;
					exit;
				}
			}
		}
		
		if (array_key_exists('jms_rss_post_url', $wp->query_vars)) {
			//for old usage only
			$url = $wp->query_vars['jms_rss_post_url'];
			$postid = url_to_postid( $url );
			$post = get_post($postid);
			//print_r($post);
			echo $post->post_content;
			exit;
		}
	}
	
	function install_jms() {
		global $jms_rss_db_version;
		global $wpdb;
		
		$installed_ver = get_option( "jms_rss_db_version", null );
		if ( $installed_ver == null ) {
			$table_name = $wpdb->prefix . "jms_rss_android_listener";
			$charset_collate = $wpdb->get_charset_collate();
			
			$sql = "CREATE TABLE IF NOT EXISTS $table_name (
			  `id` INT NOT NULL AUTO_INCREMENT ,
			  `device_token` VARCHAR(255) NOT NULL ,
			  `state` TINYINT NOT NULL COMMENT '0 disabled\n1 enabled' ,
			  `reg_date` DATETIME NOT NULL ,
			  PRIMARY KEY (`id`),
			  UNIQUE INDEX `id_UNIQUE` (`id` ASC) ,
			  UNIQUE INDEX `device_token_UNIQUE` (`device_token` ASC)
			  ) ENGINE = INNODB $charset_collate;";
			
			require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
			dbDelta( $sql );
			
			add_option( "jms_rss_db_version", $jms_rss_db_version );
		}
	}
	
?>