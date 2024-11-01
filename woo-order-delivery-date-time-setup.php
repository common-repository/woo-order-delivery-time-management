<?php
/**
 * Plugin Name: Setup Order Delivery Date & Time for Woocommerce
 * Description:	This plugin allows users to choices the order delivery type, order delivery date and, order delivery time on checkout page. From admin side store owner can setup the date rang and start time - end time for store pickup or deliver the order. Woo-Commerce Plugin should be installed and active to use this plugin.
 * Author: Kaushik Kalathiya
 * Author URI: https://kaushikkalathiya.github.io/kaushik/
 * License: GPLv2 or later
 * License URI: http://www.gnu.org/licenses/gpl-2.0.html
 * Version: 1.6.2
 * Tested up to: 6.5.5
 * Requires at least: 6.1
 * Requires PHP: 7.4
 * WC requires at least: 6.1
 * WC tested up to: 9.0.2
 * Requires Plugins: woocommerce
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) { die; }

/**
 *  function for check woocommerce plugin is available and active or not
 *  @return Error Message at new page and back to wordpress plugin page
 */
register_activation_hook( __FILE__ , 'wod_plugin_activate' );
function wod_plugin_activate(){
    // Require parent plugin
    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) and current_user_can( 'activate_plugins' ) ) {
        // Stop activation redirect and show error
        wp_die('Sorry, but this plugin requires WooCommerce Plugin to be installed and active. <br><a href="' . admin_url( 'plugins.php' ) . '">&laquo; Return to Plugins</a>');
    }
}

/**
 * function for Remove Custom Tables & Clear fields with value that's used by plugin.
 * @return Nothing
 */
register_uninstall_hook( __FILE__ , 'wod_plugin_uninstall');
function wod_plugin_uninstall(){
	delete_option('wod_opening_hours_from');
	delete_option('wod_opening_hours_to');
	delete_option('date_range');
}

// Add setting link into plugin listing page
add_filter('plugin_action_links_'.plugin_basename(__FILE__), 'wod_settings_page_link');
function wod_settings_page_link($links){
	$links[] = '<a href="'.admin_url('admin.php?page=wod_general_settings').'">'.__('Settings').'</a>';
	return $links;
}

// Enqueue CSS & JS into Admin side //
add_action('admin_enqueue_scripts', 'wod_wp_admin_style_scripts_loader');
function wod_wp_admin_style_scripts_loader(){
	wp_enqueue_style('custom_wp_admin_style', plugin_dir_url(__FILE__).'inc/css/daterangepicker.css');
	wp_enqueue_script('moment_script', plugin_dir_url(__FILE__).'inc/js/moment.min.js', array('jquery'), '', true);
	wp_enqueue_script('daterangepicker_script', plugin_dir_url(__FILE__).'inc/js/daterangepicker.js', array('jquery'), '', true);
	wp_enqueue_script('custom_script', plugin_dir_url(__FILE__).'inc/js/custom.js', array('jquery'), '', true);
}

/**
 *  function for Add the field to the checkout page using woocommerce hooks
 *  @return Display custom added fields to checkout page
 */
add_action('woocommerce_after_order_notes', 'customise_checkout_field');
function customise_checkout_field($checkout){

	//get time value from wp_option
	$from = (int)get_option('wod_opening_hours_from');
	$to = (int)get_option('wod_opening_hours_to');

	//generates array of from - to time and change time formate
	while ($from <= $to) {
		$d = mktime($from,0);
		$range_arr[$from] = date("h:i a", $d);
		$from++;
	}

	/**
 	 *  function for generates array of from - to date with custom date formate. Date Interval - 1 Day
 	 *  @param  varchar $startDate - starting date of array
 	 *			varchar $endDate - ending date of array
 	 *			varchar $format - specified the format of date default format - 'm-d-Y'
 	 *  @return Array of startDate to endDate with custom format in php
 	 *  @example array( ['01/31/2018'] => ['02/27/2018'], )
 	 */
	function createDateRange($startDate, $endDate, $format = "m-d-Y"){
		$begin = new DateTime($startDate);
		$end = new DateTime($endDate);

		$interval = new DateInterval('P1D'); // 1 Day
		$dateRange = new DatePeriod($begin, $interval, $end);

		$range = [];
		foreach ($dateRange as $date) {
		    $range[$date->format($format)] = $date->format($format);
		}

		return $range;
	}

	$get_date = get_option('date_range');
	$date_range = explode('-',$get_date,2);
	$start_date = (string)$date_range[0];
	$end_date = (string)$date_range[1];
	$date_range_arr = createDateRange($start_date,$end_date);


	// Design of custom checkout fields
	echo '<div id="customise_checkout_field"><h2>' . 'Order Delivery Options' . '</h2>';

	woocommerce_form_field('wod_radio', array(
		'type' => 'select',
		'class' => array( 'wod_radio' ) ,
		'label' => 'Delivery type' ,
		'placeholder' => 'Select date' ,
		'options' =>  array(
			'Home Delivery' => 'Home Delivery',
			'Self Pickup' => 'Self Pickup',
		)
	) , $checkout->get_value('wod_radio') );

	woocommerce_form_field('wod_date', array(
		'type' => 'select',
		'class' => array( 'wod_date' ) ,
		'label' => 'Delivery Date' ,
		'placeholder' => 'Select date' ,
		'options' => $date_range_arr
	) , $checkout->get_value('wod_date') );

	woocommerce_form_field('wod_time', array(
		'type' => 'select',
		'class' => array( 'wod_time'),
		'label' => 'Delivery Time ',
		'placeholder' => 'Select Time',
		'options' => $range_arr 
	) , $checkout->get_value('wod_time') );

	echo '</div>';
}

// Validate the custom checkout fields with error Woocommerce error message format
add_action('woocommerce_checkout_process', 'wod_custom_field_validation');
function wod_custom_field_validation(){
	$wod_radio = sanitize_text_field($_POST['wod_radio']);
	if (!$wod_radio){
		wc_add_notice( '<strong>Delivery Type</strong> is a required', 'error' );
	}

	$wod_date = sanitize_text_field($_POST['wod_date']);
	if (!$wod_date){
		wc_add_notice( '<strong>Delivery Date</strong> is a required', 'error' );
	}

	$wod_time = sanitize_text_field($_POST['wod_time']);
	if (!$wod_time){
		wc_add_notice( '<strong>Delivery Time</strong> is a required', 'error' );
	}
}

//Update the order meta or store custom checkout fields values into database
add_action('woocommerce_checkout_update_order_meta', 'wod_custom_field_update');
function wod_custom_field_update( $order_id ) {
	if ($_POST['wod_radio']) update_post_meta( $order_id, 'Delivery Type', sanitize_text_field($_POST['wod_radio']));
	if ($_POST['wod_date']) update_post_meta( $order_id, 'Delivery Date', sanitize_text_field($_POST['wod_date']));
	if ($_POST['wod_time']) update_post_meta( $order_id, 'Delivery Time', sanitize_text_field($_POST['wod_time']));
}

// Add order information into customer order email & admin notification email
add_filter('woocommerce_email_order_meta', 'wod_email_order_meta', 10, 3);
function wod_email_order_meta( $order_obj, $sent_to_admin, $plain_text ) {
	$shipping_type = get_post_meta($order_obj->get_id(),'Delivery Type',true);
	$shipping_date = get_post_meta($order_obj->get_id(),'Delivery Date',true);
	$shipping_time = get_post_meta($order_obj->get_id(),'Delivery Time',true);

	// we will add the separate version for plaintext emails
	if ( $plain_text === false ) {
		echo '<h2> Order Information </h2>
				<ul>
					<li><strong> Shipping Type : </strong>'. $shipping_type .'</li>
					<li><strong> Shipping Date :</strong> ' . $shipping_date . '</li>
					<li><strong> Shipping Time : </strong> ' . $shipping_time . ':00 </li>
				</ul>';
	} else {
		echo "Order Information\n
			Shipping Type: $shipping_type
			Shipping Date: $shipping_date
			Shipping Time: $shipping_time:00";
	}
}

//include wordpress admin option page
include('inc/admin.php');
?>