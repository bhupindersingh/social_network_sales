<?php
/**
 * Plugin Name: Social Network Sales
 * Plugin URI: http://www.teevine.com
 * Description: Show sales coming from social network website. Will work for Twitter & Facebook.
 * Version: 1.0
 * Author: Bhupinder Singh
 * Author URI: http://www.macrew.net
 * License: GPL2
 */
global $social_db_version;
$social_db_version = "1.0";
function social_sales_install() {
   global $wpdb;
   global $social_db_version;
   $table_name = $wpdb->prefix . "social_sales";      
   $sql = "CREATE TABLE IF NOT EXISTS `$table_name` (
	  `id` int(11) NOT NULL AUTO_INCREMENT,
	  `order_id` int(11) DEFAULT '0',
	  `reference` char(50) DEFAULT '',
	  `time` datetime NOT NULL DEFAULT '0000-00-00 00:00:00',
	  `token` char(200) DEFAULT '',
	  UNIQUE KEY `id` (`id`)
	);";

   require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
   dbDelta( $sql ); 
   add_option( "social_db_version", $social_db_version );
}
register_activation_hook( __FILE__, 'social_sales_install');

function social_sales_uninstall() {
     global $wpdb;
     $table_name = $wpdb->prefix . "social_sales";
     $sql = "DROP TABLE IF EXISTS `$table_name`;";
     $wpdb->query($sql);
     delete_option("social_db_version");
}
register_deactivation_hook( __FILE__, 'social_sales_uninstall' );

add_action('admin_menu', 'social_sales_admin_actions');
add_action( 'wp_ajax_show_all_sales', 'show_all_sales' );
function social_sales_admin_actions() {
	add_menu_page("Social Network Sales", "Social Network Sales", 1, "Social_Network_Sales", "social_network_admin",plugins_url( '/images/menu-icon.png',__FILE__));
}
//Default function to show Sales Orders
function social_network_admin(){	
	include('display_report.php');	
}

add_action('init', 'check_referer',1);
function check_referer(){
	global $wpdb;
	if(!session_id())session_start();
	$token = md5(uniqid(rand(), true));
	$referer = '';
	if(!is_admin()):
		if(isset($_SERVER['HTTP_REFERER']) && $_SERVER['HTTP_REFERER'] != ''){
			$allowed_host_1 = 'facebook.com';
			$allowed_host_2 = 't.co';			
			$host = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
			if(substr($host, 0 - strlen($allowed_host_1)) == $allowed_host_1){
				$referer="facebook";				
			}
			else if(substr($host, 0 - strlen($allowed_host_2)) == $allowed_host_2){
				$referer="twitter";				
			}
			// check of there is already session exists then do nothing
			if(simpleSessionGet("referer") == '' && $referer != ''){
				simpleSessionSet("referer",$referer);
				simpleSessionSet("token",$token);
				$social_table = $wpdb->prefix . "social_sales";
				$query = $wpdb->prepare("INSERT INTO $social_table (reference, time, token) VALUES ( %s, %s, %s )",$referer,date("Y-m-d H:i:s"),$token);				
				if ($wpdb->query($query) === FALSE) {
					wp_die( __('Error: ' . $wpdb->last_error) ); 
				}
			}
		}
	endif;
}

add_action( 'woocommerce_checkout_order_processed', 'custom_process_order');
function custom_process_order($order_id) {
	global $wpdb;
	if(simpleSessionGet("referer") != '' && simpleSessionGet("token") != ''){
		$social_table = $wpdb->prefix . "social_sales";
		$query = $wpdb->prepare("UPDATE $social_table SET order_id = %d WHERE token = %s",$order_id,simpleSessionGet("token"));				
		if ($wpdb->query($query) === FALSE) {
			wp_die( __('Error: ' . $wpdb->last_error) ); 
		}	
	}	
}
//Function to show all sales
function show_all_sales(){
	parse_str($_REQUEST['form'], $form);
	$orders = get_orders_of_products($form);	
	if(!empty($orders)){
		foreach($orders as $val){
			$order_data[] = get_products_from_orders($val->order_id);
		}		
		$product_data = array();
		foreach($order_data as $key=>$val_p){
			foreach($val_p['line_items'] as $val_items){
				if(_value_in_array($product_data['products'],$val_items['product_id'])){
					$search_key = _key_in_array($product_data['products'],$val_items['product_id']);
					$product_data['products'][$search_key]['facebook_cnt'] = $product_data['products'][$search_key]['facebook_cnt'] + $val_items['facebook_cnt'];
					$product_data['products'][$search_key]['twitter_cnt'] = $product_data['products'][$search_key]['twitter_cnt'] + $val_items['twitter_cnt'];
					$product_data['products'][$search_key]['direct_cnt'] = $product_data['products'][$search_key]['direct_cnt'] + $val_items['direct_cnt'];;
				}
				else{					
					$product_data['products'][] = $val_items;
				}
			}
		}		
		include_once("list_sales.php");		
	}
	else{
			echo '<div class="error_2">Sorry! no record found.</div>';	
	}
	exit;	
}
function _key_in_array($array, $find){	
	foreach ($array as $key => $value){
	  if($find == $value['product_id']){		 
		   return $key;
	  }
	}
}

function _value_in_array($array, $find){	
	$exists=FALSE;
	foreach ($array as $key => $value){
	  if($find == $value['product_id']){
		   $exists = TRUE;
	  }
	}
	return $exists;
}

function get_orders_of_products($post){
	global $wpdb;
	$sqlDate = '';
	if($post['selDataBy']=="date_range"){
		$start_date=date("Y-m-d",strtotime($post['txtStartDate']));
		$end_date=date("Y-m-d",strtotime($post['txtEndDate']));	
		$sqlDate .= " AND date_format(meta_value,'%Y-%m-%d') BETWEEN '$start_date' AND '$end_date'";
	}
	// If he select a specific period
	if($post['selDataBy']=="period"){
		if($post['period']=="today"){
			$date=date("Y-m-d");
			$sqlDate .= " AND DATE_FORMAT(post.post_date,'%Y-%m-%d') = '$date'";
		}
		if($post['period']=="yesterday"){
			$date = date('Y-m-d', strtotime(' -1 day'));		
			$sqlDate .= " AND DATE_FORMAT(post.post_date,'%Y-%m-%d') = '$date'";
		}
		if($post['period']=="this-week"){
			$start_date = date('Y-m-d', strtotime("this week"));	
			$end_date = date("Y-m-d");
			$sqlDate .= " AND DATE_FORMAT(post.post_date,'%Y-%m-%d') BETWEEN '$start_date' AND '$end_date'";
		}
		if($post['period']=="last-week"){
			$start_date = date("Y-m-d",strtotime("last week"));
			$ts = strtotime($start_date);
			$start = (date('w', $ts) == 0) ? $ts : strtotime('last sunday', $ts);	
			$end_date = date('Y-m-d', strtotime('next sunday', $start));	
			$sqlDate .= " AND DATE_FORMAT(post.post_date,'%Y-%m-%d') BETWEEN '$start_date' AND '$end_date'";
		}
		if($post['period']=="this-month"){
			$start_date = date('Y-m-d',strtotime('first day of this month'));	
			$end_date = date('Y-m-d');	
			$sqlDate .= " AND DATE_FORMAT(post.post_date,'%Y-%m-%d') BETWEEN '$start_date' AND '$end_date'";
		}
		if($post['period']=="last-month"){
			$start_date = date('Y-m-d',strtotime('first day of last month'));	
			$end_date = date('Y-m-d',strtotime('last day of this month'));	
			$sqlDate .= " AND DATE_FORMAT(post.post_date,'%Y-%m-%d') BETWEEN '$start_date' AND '$end_date'";
		}
	}
	$product='';	
	if(isset($post['product'])){
		foreach($post['product'] as $val){
			$product .= $val.',';		
		}
	}
	$product=rtrim($product,',');
	$sql="SELECT DISTINCT i.order_id FROM wp_woocommerce_order_itemmeta oi, wp_woocommerce_order_items i 
	WHERE oi.meta_key = '_product_id' AND oi.meta_value IN(".$product.") AND oi.order_item_id = i.order_item_id";
	$orders = $wpdb->get_results( $wpdb->prepare($sql));
	// At last we have to refine the orders
	$ord='';
	foreach($orders as $val){
		$ord .= $val->order_id.',';	
	}
	$ord=rtrim($ord,',');		
	$sqlDateCheck="SELECT DISTINCT post_meta.post_id as order_id FROM wp_posts as post,wp_postmeta as post_meta WHERE post.ID=post_meta.post_id AND post_id IN (".$ord.")";
	// If admin select the date range OR period
	if($post['selDataBy'] != ''){ 
		$sqlDateCheck .= $sqlDate;
	}
	$orders = $wpdb->get_results($sqlDateCheck);			
	
	return $orders;
}
// Function to check if order is generated through Social Network Or Not
function get_order_origin($order_id){
	global $wpdb;
	$sqlOrderCheck="SELECT order_id,reference FROM wp_social_sales WHERE order_id = {$order_id}";
	$order = $wpdb->get_row($sqlOrderCheck);
	if(!empty($order))
		return $order->reference;
	else
		return '';
}
// Function to get products items related to the order
function get_products_from_orders( $order_id ) {
	global $wpdb;	
	$order = new WC_Order( $order_id );
	$reference = get_order_origin($order_id);
    //$order_post = get_post( $order_id );
	$order_data = array(
			'id'                        => $order->id,
			'order_number'              => $order->get_order_number(),
			'total_line_items_quantity' => $order->get_item_count(),			
			'email'      => $order->billing_email,
			'phone'      => $order->billing_phone			
		);
		// add line items		
		foreach( $order->get_items() as $item_id => $item ) {								
			$order_data['product_title'][]=$item['name'];			
			$lineItems = get_item_meta($item_id);		
			$order_data['line_items'][] = array(
				'product_id' => $item['product_id'],
				'name'       => $item['name'],
				//'quantity'   => (int) $item['qty'],				
				'facebook_cnt' =>  ($reference == 'facebook' ? (int) $item['qty'] : 0),
				'twitter_cnt' =>  ($reference == 'twitter' ? (int) $item['qty'] : 0),
				'direct_cnt' => ($reference == '' ? (int) $item['qty'] : 0),
			);
		}		
	return $order_data;
}
//Function to list down the products for all orders
function get_all_order_products(){
	global $wpdb;	
	$products = $wpdb->get_results( $wpdb->prepare("SELECT DISTINCT posts.ID, posts.post_title
	FROM {$wpdb->posts} AS posts, wp_woocommerce_order_itemmeta AS oi
	WHERE posts.post_type = 'product'
	AND posts.post_status = 'publish'
	AND oi.meta_key = '_product_id'
	AND oi.meta_value = posts.ID
	ORDER BY posts.ID DESC"));
	$options = "";
	foreach($products as $val){		
		$options .= "<option value='".$val->ID."'>".$val->post_title."</option>";		
	}	
	return $options;
}

/*Make Sure you set the sessions for use this plugin*/
add_action('wp_logout', 'simpleSessionDestroy');
add_action('wp_login', 'simpleSessionDestroy');

/**
 * destroy the session, this removes any data saved in the session over logout-login
 */              
function simpleSessionDestroy() {
    session_destroy ();
}
/**
 * get a value from the session array
 * @param type $key the key in the array
 * @param type $default the value to use if the key is not present. empty string if not present
 * @return type the value found or the default if not found
 */
function simpleSessionGet($key, $default='') {
    if(isset($_SESSION[$key])) {
        return $_SESSION[$key];
    } else {
        return $default;
    }
}
/**
 * set a value in the session array
 * @param type $key the key in the array
 * @param type $value the value to set
 */
function simpleSessionSet($key, $value) {
    $_SESSION[$key] = $value;
}