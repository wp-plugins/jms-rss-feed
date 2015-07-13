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
	add_action( 'publish_post', 'send_push_notification', 10, 2 );
	
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
		$vars[] = 'jms_rss_send';
		return $vars;
	}
	
	function send_push_notification($ID, $post) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'jms_rss_android_listener';
		$devicesList = $wpdb->get_results("SELECT * FROM $table_name WHERE state=1", "ARRAY_A");
		$wpdb->flush();
		
		if ( $devicesList )	{
			
			define("GOOGLE_API_KEY", "AIzaSyDYVTahqUmT4RX6brZnReUr6Rq2KNEUv90");
			
			$deviceList = array();
			
			foreach ( $devicesList as $device )	{
				$token = $device["device_token"];
				if(strlen($token) > 10) {
					$deviceList[] = $token;
				}
			}
			
			//$author = $post->post_author; //Post author ID
			//$name = get_the_author_meta( 'display_name', $author );
			//$email = get_the_author_meta( 'user_email', $author );
			$title = $post->post_title;
			//$permalink = get_permalink( $ID );
			//$edit = get_edit_post_link( $ID, '' );
			
			//$subject = sprintf( 'Published: %s', $title );
			$message = sprintf ('The article “%s” has been published.', $title);
			
			//$message .= sprintf( 'View: %s', $permalink );
			sendNotification($deviceList, $message);
		}
	}
	
	function return_jms_single_post($wp) {
		global $wpdb;
		
		//this is for test
		if(array_key_exists('jms_rss_send', $wp->query_vars)) {
			$action = $wp->query_vars['jms_rss_send'];
			if($action == '1') {
				$table_name = $wpdb->prefix . 'jms_rss_android_listener';
				$devicesList = $wpdb->get_results("SELECT * FROM $table_name WHERE state=1", "ARRAY_A");
				$wpdb->flush();
				
				if ( $devicesList )	{
					
					define("GOOGLE_API_KEY", "AIzaSyDYVTahqUmT4RX6brZnReUr6Rq2KNEUv90");
					
					$deviceList = array();
					
					foreach ( $devicesList as $device )	{
						$token = $device["device_token"];
						if(strlen($token) > 10) {
							$deviceList[] = $token;
						}
					}
					
					sendNotification($deviceList, "Hello World!");
				}
			}
			
			exit;
		}
		
		if (array_key_exists('jms_rss_action', $wp->query_vars)) {
			$action = $wp->query_vars['jms_rss_action'];
			
			if(!empty($action)) {
				if($action == "register_android") {
					$token = $wp->query_vars['jms_rss_token'];
					$table_name = $wpdb->prefix . 'jms_rss_android_listener';
					
					$wpdb->query( $wpdb->prepare( 
						"
							INSERT INTO $table_name
							( device_token, state, reg_date )
							VALUES ( %s, %d, %s )
						", 
							array(
								$token, 
								1, 
								current_time('mysql', 1)
							)
					) );
					
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
	
	function sendNotification($registrationIDs, $message) {
		//$registrationIDs = array( "reg id1","reg id2");
		$url = 'https://gcm-http.googleapis.com/gcm/send';
		
		$fields = array(
			'registration_ids' => $registrationIDs,
			'data' => array( "message" => $message ),
		);
		
		$headers = array(
			'Authorization: key=' . GOOGLE_API_KEY,
			'Content-Type: application/json'
		);
		
		// Open connection
		$ch = curl_init();

		// Set the URL, number of POST vars, POST data
		curl_setopt( $ch, CURLOPT_URL, $url);
		curl_setopt( $ch, CURLOPT_POST, true);
		curl_setopt( $ch, CURLOPT_HTTPHEADER, $headers);
		curl_setopt( $ch, CURLOPT_RETURNTRANSFER, true);
		//curl_setopt( $ch, CURLOPT_POSTFIELDS, json_encode( $fields));

		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		// curl_setopt($ch, CURLOPT_POST, true);
		// curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode( $fields));

		// Execute post
		$result = curl_exec($ch);

		// Close connection
		curl_close($ch);
		echo $result;
		//print_r($result);
		//var_dump($result);
	}
	
?>