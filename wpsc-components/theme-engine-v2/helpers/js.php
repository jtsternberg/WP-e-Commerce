<?php

add_action( 'wp_enqueue_scripts', '_wpsc_te2_register_scripts', 1 );

function _wpsc_te2_register_scripts() {

	$engine = WPSC_Template_Engine::get_instance();

	$scripts = apply_filters( 'wpsc_registered_scripts', $engine->get_core_scripts_data() );

	foreach ( $scripts as $handle => $script_data ) {

		wp_register_script(
			$handle,
			wpsc_locate_asset_uri( $script_data['path'] ),
			$script_data['dependencies'],
			$script_data['version'],
			! isset( $script_data['in_footer'] ) || $script_data['in_footer']
		);

		if ( isset( $script_data['data'] ) ) {

			wpsc_localize_script(
				$handle,
				$script_data['data']['property_name'],
				$script_data['data']['data']
			);

		}
	}

	$enqueued = false;

	foreach ( $engine->get_queued_scripts() as $handle => $script_data ) {
		$enqueued = true;

		_wpsc_enqueue_and_localize_script( $handle, $script_data );
	}

	// Output our namespace.
	?><script type='text/javascript'>/* <![CDATA[ */window.WPSC = window.WPSC || {};/* ]]> */</script><?php

	do_action( 'wpsc_register_scripts' );
	do_action( 'wpsc_enqueue_scripts' );
}

function _wpsc_prepare_cart_item_for_js( $item, $key ) {

	/*
	 * @todo Most of the following is copied from WPSC_Cart_Item_Table::column_items(),
	 * but this should really be moved to a universally available functions/methods.
	 */

	$product      = get_post( $item->product_id );
	$product_name = $item->product_name;

	if ( $product->post_parent ) {
		$permalink    = wpsc_get_product_permalink( $product->post_parent );
		$product_name = get_post_field( 'post_title', $product->post_parent );
	} else {
		$permalink = wpsc_get_product_permalink( $item->product_id );
	}

	$variations = array();

	if ( is_array( $item->variation_values ) ) {
		foreach ( $item->variation_values as $variation_set => $variation ) {
			$set_name       = get_term_field( 'name', $variation_set, 'wpsc-variation' );
			$variation_name = get_term_field( 'name', $variation    , 'wpsc-variation' );

			if ( ! is_wp_error( $set_name ) && ! is_wp_error( $variation_name ) ) {
				$variations[]   = array(
					'label' => esc_html( $set_name ),
					'value' => esc_html( $variation_name ),
				);
			}
		}
	}

	$image = wpsc_has_product_thumbnail( $item->product_id )
		? wpsc_get_product_thumbnail( $item->product_id, 'archive' )
		: wpsc_product_no_thumbnail_image( 'archive', '', false );

	$remove_url = add_query_arg( '_wp_nonce', wp_create_nonce( "wpsc-remove-cart-item-{$key}" ), wpsc_get_cart_url( 'remove/' . absint( $key ) ) );

	$prepared = array(
		'id'         => $item->product_id,
		'url'        => $permalink,
		'price'      => wpsc_format_currency( $item->unit_price ), // @todo correct property?
		'title'      => $product_name,
		'thumb'      => $image,
		'quantity'   => $item->quantity,
		'remove_url' => $remove_url,
		'variations' => $variations
	);

	return apply_filters( 'wpsc_prepared_cart_item_for_js', $prepared );
}

function _wpsc_prepare_cart_items_for_js( $cart_items = array() ) {
	$prepared = array();
	foreach ( $cart_items as $key => $item ) {
		$prepared[] = _wpsc_prepare_cart_item_for_js( $item, $key );
	}

	return $prepared;
}

function _wpsc_cart_notifications_modal_underscores_template() {
	wp_enqueue_style( 'wpsc-cart-notifications' );

	if ( ! isset( $GLOBALS['wpsc_cart'] ) ) {
		$GLOBALS['wpsc_cart'] = new wpsc_cart();
	}

	$cart_items = _wpsc_prepare_cart_items_for_js( $GLOBALS['wpsc_cart']->cart_items );

	global $wpsc_cart;

	$subtotal = $wpsc_cart->calculate_subtotal();
	$shipping_total = '0';
	$total = $subtotal + $shipping_total;

	$cart_status = array(
		'subTotal'      => $shipping_total ? wpsc_format_currency( $subtotal ) : '',
		'shippingTotal' => $shipping_total ? wpsc_format_currency( $shipping_total ) : '',
		'total'         => wpsc_format_currency( $total ),
	);

	wpsc_localize_script( 'wpsc-cart-notifications', 'cartNotifications', array(
		'_cartItems'  => $cart_items,
		'_cartStatus' => $cart_status,
		'ajaxurl'     => admin_url( 'admin-ajax.php' ),
		'debug'       => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
		// 'strings' => apply_filters( 'wpsc_cart_notification_strings', array(
		// 	'1_added'  => __( '%d item added to Your Cart.', 'wp-e-commerce' ),
		// ) ),
	) );
	?>
	<script type="text/html" id="tmpl-wpsc-modal">
		<div class="wpsc-hide" id="wpsc-modal-overlay"></div>
		<div class="wpsc-hide" id="wpsc-cart-notification"></div>
	</script>
	<script type="text/html" id="tmpl-wpsc-modal-inner">
		<?php wpsc_get_template_part( 'js-template', 'cart-modal' ); ?>
	</script>
	<script type="text/html" id="tmpl-wpsc-modal-product">
		<?php wpsc_get_template_part( 'js-template', 'cart-modal-product' ); ?>
	</script>
	<?php
}

/**
 * Enqueue a registered wpsc script (and optionally localize its JS data).
 * If script cannot be enqueued yet, register the queued script for later enqueue.
 *
 * @see WPSC_Template_Engine::register_queued_script()
 * @see wp_enqueue_script()
 * @see wpsc_localize_script()
 *
 * @since 4.0
 *
 * @param string $handle      Name of the registered wpsc script.
 * @param array  $script_data (Optional) data to send to wp_localize_script under the WPSC namespace.
 */
function wpsc_enqueue_script( $handle, $script_data = array() ) {
	if ( ! did_action( 'wpsc_enqueue_scripts' ) ) {
		WPSC_Template_Engine::get_instance()->register_queued_script( $handle, $script_data );
	} else {
		_wpsc_enqueue_and_localize_script( $handle, $script_data );
	}
}

/**
 * Enqueue a registered wpsc script (and optionally localize its JS data).
 *
 * @see wp_enqueue_script()
 * @see wpsc_localize_script()
 *
 * @access private
 *
 * @since 4.0
 *
 * @param string $handle      Name of the registered wpsc script.
 * @param array  $script_data (Optional) data to send to wp_localize_script under the WPSC namespace.
 */
function _wpsc_enqueue_and_localize_script( $handle, $script_data = array() ) {
	wp_enqueue_script( $handle );

	if ( ! empty( $script_data ) && isset( $script_data['property_name'], $script_data['data'] ) ) {

		$add_to_namespace = ! isset( $script_data['add_to_namespace'] ) || $script_data['add_to_namespace'];

		wpsc_localize_script(
			$handle,
			$script_data['property_name'],
			$script_data['data'],
			$add_to_namespace
		);
	}
}

/**
 * Localize a script under the WPSC namespace.
 *
 * Works only if the script has already been registered or enqueued.
 *
 * Accepts an associative array $data and creates a JavaScript object:
 *
 *     window.WPSC.{$property_name} = {
 *         key: value,
 *         key: value,
 *         ...
 *     }
 *
 *
 * @see wp_localize_script()
 * @see WP_Dependencies::get_data()
 * @see WP_Dependencies::add_data()
 * @global WP_Scripts $wp_scripts The WP_Scripts object for printing scripts.
 *
 * @since 4.0
 *
 * @param string $handle          Script handle the data will be attached to.
 * @param string $property_name   Name for the property applied to the global WPSC object.
 *                                Passed directly, so it should be qualified JS variable.
 *                                Example: '/[a-zA-Z0-9_]+/'.
 * @param array $data             The data itself. The data can be either a single or multi-dimensional array.
 * @param bool  $add_to_namespace Whether to add to the WPSC object, or default wp_localize_script output.
 *
 * @return bool True if the script was successfully localized, false otherwise.
 */
function wpsc_localize_script( $handle, $property_name, $data, $add_to_namespace = true ) {
	global $wp_scripts;

	if ( $add_to_namespace ) {

		// Make sure this variable does not break the WPSC namespace.
		$property_name = 'WPSC.' . sanitize_html_class( maybe_serialize( $property_name ) );
	}

	if ( isset( $data['_templates'] ) && is_array( $data['_templates'] ) ) {

		foreach ( $data['_templates'] as $tmpl_id => $callback ) {
			if ( is_callable( $callback ) ) {

				// Reset callback value as we won't that need that in JS.
				$data['_templates'][ $tmpl_id ] = $tmpl_id;

				// Hook in template callback.
				add_action( 'wp_footer', $callback );
			}
		}

	}

	$result = wp_localize_script( $handle, $property_name, $data );

	if ( $add_to_namespace ) {

		$script = $wp_scripts->get_data( $handle, 'data' );

		$script = str_replace(
			"var {$property_name} = {",
			"window.{$property_name} = {",
			$script
		);

		$result = $wp_scripts->add_data( $handle, 'data', $script );
	}

	return $result;
}
