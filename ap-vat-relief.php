<?php
/**
 * Plugin name: VAT Relief for WooCommerce
 * Description: Allow customers to apply for VAT Relief or TAX Exempt on products at checkout.
 * Author: Ashish Patil
 * Version: 1.0.0
 * Plugin URI: https://ashishpatil.co.uk
 * Author URI: https://ashishpatil.co.uk
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * text-domain: ap_vat_relief
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * WooCommerce fallback notice.
 *
 * @since 4.1.2
 */
function ap_vat_relief_missing_wc_notice() {
	/* translators: 1. URL link. */
	echo '<div class="error"><p><strong>' . sprintf( esc_html__( 'VAT Exempt requires WooCommerce to be installed and active. You can download %s here.', 'ap_vat_relief' ), '<a href="https://woocommerce.com/" target="_blank">WooCommerce</a>' ) . '</strong></p></div>';
}
function ap_vat_relief_tax_not_enabled_notice() {
	/* translators: 1. URL link. */
	echo '<div class="notice notice-warning is-dismissible"><p><strong>' . sprintf( esc_html__( 'VAT Exempt requires WooCommerce TAX settings to be enabled and atleast one standard class set. You can enable tax settings %s.', 'ap_vat_relief' ), '<a href="'.get_admin_url().'admin.php?page=wc-settings">here</a>' ) . '</strong></p></div>';
}


add_action( 'plugins_loaded', 'ap_vat_relief_init' );
function ap_vat_relief_init(){
	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', 'ap_vat_relief_missing_wc_notice' );
		return;
	}

	if (get_option('woocommerce_calc_taxes') != 'yes'){
		add_action( 'admin_notices', 'ap_vat_relief_tax_not_enabled_notice' );
	}

	// Include all fields
	require_once( 'include/fields.php' );


	// Hooks
    add_action( 'init', 'register_script' );

	add_action( 'woocommerce_after_order_notes', 'ap_vat_relief_add_vat_relief_box' );
	add_action( 'woocommerce_before_calculate_totals', 'ap_vat_relief_remove_vat_from_products');
	add_action( 'woocommerce_checkout_update_order_meta', 'ap_vat_relief_save_checkout_fields' );
	add_action( 'woocommerce_thankyou', 'ap_vat_relief_display_on_thankyou_page' );
	add_action( 'woocommerce_admin_order_data_after_billing_address', 'ap_vat_relief_display_on_order_details' );
	add_action( 'woocommerce_email_after_order_table', 'ap_vat_relief_display_on_emails', 20, 4 );
    add_action( 'woocommerce_single_product_summary', 'ap_vat_relief_tag_on_single_product', 22 );
    add_action( 'woocommerce_after_shop_loop_item', 'ap_vat_relief_tag_inside_products_loop', 12 );

}

// Scritps and styles
function register_script(){
	wp_register_style('ap_vat_relief', plugins_url('/css/vat-relief.css',__FILE__ ), array() , '1.00');
	wp_enqueue_style('ap_vat_relief');
	wp_register_script( 'ap_vat_relief', plugins_url('/js/vat-relief.js',__FILE__ ), array('jquery'), '1.0.0' );
	wp_enqueue_script('ap_vat_relief');
}


// Remvove VAT from specific product, based on acf value from backend
function ap_vat_relief_remove_vat_from_products() {

	global $woocommerce;

	$zero_tax = 'zero-rate';

	if ( ! $_POST || ( is_admin() && ! is_ajax() ) ) {
		return;
	}

	if ( isset( $_POST['post_data'] ) ) {
		parse_str( $_POST['post_data'], $post_data );
	} else {
		$post_data = $_POST;
	}

	if ( !isset( $post_data['ap_vat_relief_confirm'] ) ) {
		return;
	}

	// Loop through cart items and set the vat to zero for eligible products
	foreach( WC()->cart->get_cart() as $cart_item_key => $cart_item ){
		if( get_post_meta( $cart_item['product_id'], '_product_vat_relief', true ) ) {
			$cart_item['data']->set_tax_class($zero_tax);
		}
	}


}

// Validate Person and Charity fields
add_action('woocommerce_checkout_process', 'ap_validate_vat_relief_fields');

function ap_validate_vat_relief_fields() {
    // Check if set, if its not set add an error.
	if ('person' === $_POST['vat_relief_type']){
		if ( ! $_POST['person_name'] || ! $_POST['person_claim_name'] || ! $_POST['person_disability'] )
			wc_add_notice( __( 'Person VAT exempt all fields are required.' ), 'error' );
	}elseif ('charity' === $_POST['vat_relief_type']){
		if ( ! $_POST['charity_person_name'] || ! $_POST['charity_name'] || ! $_POST['charity_number'] || ! $_POST['charity_address'] )
			wc_add_notice( __( 'Charity VAT exempt all fields are required.' ), 'error' );
	}

	// Validate confirmation checkbox
    if(('person' === $_POST['vat_relief_type'] || 'charity' === $_POST['vat_relief_type']) && ! $_POST['ap_vat_relief']){
        wc_add_notice( __( 'VAT exempt - Please confirm the VAT Exempt details are correct to remove VAT from eligible products.' ), 'error' );
    }
}


// SAVE - VAT Relief Person or Charity data

function ap_vat_relief_save_checkout_fields( $order_id ) {

	if ('person' === $_POST['vat_relief_type']){
		if ( $_POST['person_name'] ) update_post_meta( $order_id, '_person_name', sanitize_text_field( $_POST['person_name'] ) );
		if ( $_POST['person_claim_name'] ) update_post_meta( $order_id, '_person_claim_name', sanitize_text_field( $_POST['person_claim_name'] ) );
		if ( $_POST['person_disability'] ) update_post_meta( $order_id, '_person_disability', sanitize_text_field( $_POST['person_disability'] ) );
	}elseif ('charity' === $_POST['vat_relief_type']){
		if ( $_POST['charity_person_name'] ) update_post_meta( $order_id, '_charity_person_name', sanitize_text_field( $_POST['charity_person_name'] ) );
		if ( $_POST['charity_name'] ) update_post_meta( $order_id, '_charity_name', sanitize_text_field( $_POST['charity_name'] ) );
		if ( $_POST['charity_number'] ) update_post_meta( $order_id, '_charity_number', sanitize_text_field( $_POST['charity_number'] ) );
		if ( $_POST['charity_address'] ) update_post_meta( $order_id, '_charity_address', sanitize_text_field( $_POST['charity_address'] ) );
	}

    if ( $_POST['vat_relief_type'] ) update_post_meta( $order_id, '_vat_relief_type', sanitize_text_field( $_POST['vat_relief_type'] ) );

    if ( $_POST['ap_vat_relief_confirm'] ) update_post_meta( $order_id, '_ap_vat_relief_confirm', esc_attr( 'Yes, I\'m VAT Exempt' ) );
}


// DISPLAY - VAT Relief Data on Thankyou Page, Order details Page and Email
function ap_vat_relief_display_on_thankyou_page( $order_id ) {
    ap_display_vat_relief_data($order_id);
}

function ap_vat_relief_display_on_order_details( $order ) {
	$order_id = $order->get_id();
    ap_display_vat_relief_data($order_id);
}

function ap_vat_relief_display_on_emails( $order, $sent_to_admin, $plain_text, $email ) {
    $order_id = $order->get_id();
    ap_display_vat_relief_data($order_id);
}

function ap_display_vat_relief_data($order_id){
    $vat_relief_type = get_post_meta( $order_id, '_vat_relief_type', true );
    if ($vat_relief_type && 'no' !== $vat_relief_type){
	    echo '<div class="vat_relief_data" style="margin: 16px 0; padding: 12px; background: #f8f8f8; border: solid 1px #dbdcdc; border-radius:10px;">';

	    if ('person' === $vat_relief_type){
            echo '<h4 style="margin: 0 0 10px;">VAT Relief Person Details</h4>';
		    if ( $person_name = get_post_meta( $order_id, '_person_name', true ) ) echo '<p style="margin: 0 0 10px;"><strong>Name:</strong> ' . $person_name . '</p>';
		    if ( $person_claim_name = get_post_meta( $order_id, '_person_claim_name', true ) ) echo '<p style="margin: 0 0 10px;"><strong>Claim for Name:</strong> ' . $person_claim_name . '</p>';
		    if ( $person_disability = get_post_meta( $order_id, '_person_disability', true ) ) echo '<p style="margin: 0 "><strong>Disability:</strong> ' . $person_disability . '</p>';
	    }
	    if ('charity' === $vat_relief_type){
            echo '<h4 style="margin: 0 0 10px;">VAT Relief Charity Details</h4>';
		    if ( $charity_person_name = get_post_meta( $order_id, '_charity_person_name', true ) ) echo '<p style="margin: 0 0 10px;"><strong>Name:</strong> ' . $charity_person_name . '</p>';
		    if ( $charity_name = get_post_meta( $order_id, '_charity_name', true ) ) echo '<p style="margin: 0 0 10px;"><strong>Charity Name:</strong> ' . $charity_name . '</p>';
		    if ( $charity_number = get_post_meta( $order_id, '_charity_number', true ) ) echo '<p style="margin: 0 0 10px;"><strong>Charity Number:</strong> ' . $charity_number . '</p>';
		    if ( $charity_address = get_post_meta( $order_id, '_charity_address', true ) ) echo '<p style="margin: 0;"><strong>Charity Address:</strong> ' . $charity_address . '</p>';
	    }
	    echo '</div>';
    }
}

/**
 *  Frontend displays of tags
 */

// on single product page
function ap_vat_relief_tag_on_single_product(){
	global $product;
	if (get_post_meta( $product->get_id(), '_product_vat_relief', true )){
		echo '<h4 class="inline-block bg-orange-400 text-white text-sm px-4 py-1 rounded">Eligible for VAT relief</h4>';
	}
}

// on category product loop view
function ap_vat_relief_tag_inside_products_loop(){
	global $product;
	if (get_post_meta( $product->get_id(), '_product_vat_relief', true )){
		echo '<h4 class="my-2 text-center bg-orange-400 text-white text-xs px-2 py-1 rounded">Eligible for VAT relief</h4>';
	}
}