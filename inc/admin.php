<?php
// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * function for add option page into admin side
 * @return create page at admin side
 */
add_action('admin_menu','wod_plugin_menu');
function wod_plugin_menu(){
	add_menu_page( 'Woocommerce Order Delivery', 'Order Delivery Settings', 'manage_options', 'wod_general_settings', 'wod_admin_settings_form','dashicons-calendar-alt', 56 );
}

function wod_admin_settings_form(){
?>
	<h2>Order Delivery Date & Time Management for Woo Commerce</h2>
	<?php
		if(isset($_REQUEST['settings-updated']) && $_REQUEST['settings-updated'] == 'true'){
			echo '<div class="notice notice-success is-dismissible"><p> Settings Successfully Updated 😀 </p></div>';
		}
	?>
	<br />
	<form method="post" action="options.php">
		<?php
			settings_fields("section");
			do_settings_sections("wod_plugin_options");
			submit_button(); 
		?>
	</form>
<?php
}

/**
 * function for add time field
 * @return display time field at option page
 */
function wod_opening_hours(){
	$from = get_option('wod_opening_hours_from');
	$to = get_option('wod_opening_hours_to');
?>
	<label>From</label>
	<input type="time" name="wod_opening_hours_from" style="width:30%; padding:7px;" value="<?php if(isset($from)) echo $from;  ?>" />

	<label>To</label>
	<input type="time" name="wod_opening_hours_to" style="width:30%; padding:7px;" value="<?php if(isset($to)) echo $to; ?>" />
<?php
}

/**
 *  function for add Date Range Selection field
 *  @return display dtaerangepicker at option page for select dates
 */
function wod_opening_week(){
?>
	<input type="text" id="date_range" name="date_range" style="width:50%; padding:7px;" value="<?php echo get_option('date_range'); ?>" autocomplete="off" readonly="readonly" />
<?php
}

// admin menu page & oprions.
add_action('admin_init', 'wod_plugin_settings_fields');
function wod_plugin_settings_fields(){
	add_settings_section("section", "", null, "wod_plugin_options");
	add_settings_field("wod_opening_hours", "Opening Hours :", "wod_opening_hours", "wod_plugin_options", "section");
	add_settings_field("wod_opening_week", "Select Dates :", "wod_opening_week", "wod_plugin_options", "section");

	register_setting("section", "wod_opening_hours_from");
	register_setting("section", "wod_opening_hours_to");
	register_setting("section", "date_range");
}

// Woo Commerce Order Page
add_filter('manage_edit-shop_order_columns', 'wod_order_columns_function', 20);
function wod_order_columns_function($columns){
	$columns['shipping_type'] = __('Shipping Type', 'woocommerce');
	$columns['delivery_date'] = __('Delivery Date', 'woocommerce');
	return $columns;
}

add_action( 'manage_shop_order_posts_custom_column', 'wod_order_columns_values_function' );
function wod_order_columns_values_function( $column ) {
	if( $column == 'shipping_type' ) {
		echo get_post_meta(get_the_ID(),'Delivery Type',true);
	} elseif ($column == 'delivery_date') {
		echo get_post_meta(get_the_ID(),'Delivery Date',true);
	}
}

?>