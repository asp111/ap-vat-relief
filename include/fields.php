<?php


/**
 * Woocommerce Product Backend Page
 * VAT Relief Checkbox toggle custom field
 */

//Add VAT Relief Fields on Woocommmerce General Product Data section
add_action('woocommerce_product_options_general_product_data', 'ap_woocommerce_product_vat_relief_fields');

// Save VAT Relief Fields on Product Publish/Update action
add_action('woocommerce_process_product_meta', 'ap_woocommerce_product_vat_relief_fields_save');

function ap_woocommerce_product_vat_relief_fields()
{
    global $woocommerce, $post;

    echo '<div class="product_custom_field">';

    // VAT Relief Toggle
    woocommerce_wp_checkbox(
        array(
            'id' => '_product_vat_relief',
            'label' => 'Eligible for VAT Relief?',
            'description' => __('Enable this option if this product is VAT Relief for people with a disability', 'woocommerce'),
            'desc_tip' => 'true'
        )
    );

    echo '</div>';

}



function ap_woocommerce_product_vat_relief_fields_save($post_id)
{
	// Save VAT Relief Toggle Checkbox
	$woocommerce_product_vat_relief = $_POST['_product_vat_relief'];
	if (!empty($woocommerce_product_vat_relief))
		update_post_meta($post_id, '_product_vat_relief', esc_attr($woocommerce_product_vat_relief));

}


/**
 * Front end - Adding VAT Relief Box at Checkout
 * @param $checkout
 *
 * @return void
 */
function ap_vat_relief_add_vat_relief_box( $checkout ) {
	echo '<div id="vat_relief_box" class="p-4 mt-8 bg-gray-100 rounded-lg">';

	woocommerce_form_field(
        'vat_relief_type',
        array(
            'type' => 'radio',
            'class' => array( 'form-row-wide', 'vat_relief_type' ),
            'options' => array('no' => 'No', 'person' => 'Yes, I or the person I am buying for is eligible.','charity' => 'Yes, I represent a charity that is eligible.',),
            'label'  => __("Are you eligible for VAT Relief?"),
            'required'=>true,
            'default'=> 'no',
        ),
        $checkout->get_value('vat_relief_type')
    );

    // vat details box
    echo '<div id="vat_relief_inner_box" class="'.(($checkout->get_value('vat_relief_type') != 'no') ? 'block':'hidden').'">';

    echo '<div id="person_vat_relief" class="my-4 '.(($checkout->get_value('vat_relief_type') == 'person') ? 'block':'hidden').'">';
    echo '<h4 class="my-4 mx-2 font-semibold border-b border-b-neutral-300">Person Details</h4>';

    woocommerce_form_field(
            'person_name',
            array(
                'label'       => 'Your Name',
                'description' => '',
                'required'    => true,
                'type'        => 'text',
            ),
            $checkout->get_value( 'person_name' )
    );


    woocommerce_form_field(
            'person_claim_name',
            array(
                'label'       => 'Name of person you are claiming for',
                'description' => '',
                'required'    => true,
                'type'        => 'text',
            ),
            $checkout->get_value( 'person_claim_name' )
    );


    woocommerce_form_field(
            'person_disability',
            array(
                'label'       => 'Their disability',
                'description' => '',
                'required'    => true,
                'type'        => 'text',
            ),
            $checkout->get_value( 'person_disability' )
    );

    echo '</div>';


    // Charity fields
    echo '<div id="charity_vat_relief" class="my-4 '.(($checkout->get_value('vat_relief_type') == 'charity') ? 'block':'hidden').'">';
    echo '<h4 class="my-4 mx-2 font-semibold border-b border-b-neutral-300">Charity Details</h4>';
    woocommerce_form_field(
            'charity_person_name',
            array(
                'label'       => 'Your Name',
                'description' => '',
                'required'    => true,
                'type'        => 'text',
            ),
            $checkout->get_value( 'charity_person_name' )
    );

    woocommerce_form_field(
            'charity_name',
            array(
                'label'       => 'Charity Name',
                'description' => '',
                'required'    => true,
                'type'        => 'text',
            ),
            $checkout->get_value( 'charity_name' )
    );

    woocommerce_form_field(
            'charity_number',
            array(
                'label'       => 'Charity Number',
                'description' => '',
                'required'    => true,
                'type'        => 'text',
            ),
            $checkout->get_value( 'charity_number' )
    );

    woocommerce_form_field(
            'charity_address',
            array(
                'label'       => 'Charity Address',
                'description' => '',
                'required'    => true,
                'type'        => 'text',
            ),
            $checkout->get_value( 'charity_address' )
    );

    echo '</div>';

    woocommerce_form_field(
		'ap_vat_relief_confirm',
		array(
			'label'  => 'I confirm and declared that the information is correct for applying to VAT Exempt',
			'description'  => '<small class="text-gray-500">Select this to get VAT exempt on eligible products. By law, you are required to be VAT exempt to claim this. PLEASE NOTE THAT IT IS AN OFFENCE TO MAKE A FALSE DECLARATION.</small>',
			'class'  => array( 'input-checkbox vat-cancel-button' ),
            'required'    => true,
			'type'   => 'checkbox'
		),
		$checkout->get_value( 'ap_vat_relief_confirm' )
	);


	echo '</div>'; // vat box end

	echo '</div>';
}
