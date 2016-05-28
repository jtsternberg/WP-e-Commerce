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

function _wpsc_cart_notifications_modal_underscores_template() {
	wp_enqueue_style( 'wpsc-cart-notifications' );

	?>
	<script type="text/html" id="tmpl-wpsc-modal">
		<div class="wpsc-hide" id="wpsc-modal-overlay"></div>
		<div class="wpsc-hide" id="wpsc-cart-notification"></div>
	</script>
	<script type="text/html" id="tmpl-wpsc-modal-inner">
		<div class="wpsc-cart-notification-inner">
			<div class="wpsc-close-modal wpsc-icon-remove">Close</div>
			<div class="wpsc-cart-what-was-added">
				<div class="wpsc-confirmation-message">
					<i class="wpsc-icon-check"></i><span class="wpsc-confirmation-count">1</span> item added to <a class="wpsc-cart-link" href="{cart_url}">Your Cart</a>
				</div>
				<div class="wpsc-products-review">
					<div class="wpsc-product-review" id="{product_id}">
						<div class="wpsc-product-review-thumb">
							<img src="http://dev.wpecommerce/wp-content/uploads/2016/05/Eddy-Need-Remix-mp3-image.jpg" alt="Eddy Remix">
						</div>
						<div class="wpsc-product-review-details">
							<span class="wpsc-product-review-name">Eddy Remix</span>
							<span class="wpsc-product-review-sku">SKU 312107</span>
							<span class="wpsc-product-review-price">
								<span class="wpsc-product-review-reg-price">$39.95</span>
							</span>
							<span class="wpsc-product-review-quantity">Quantity: 1</span>
						</div>
					</div>
					<!-- end repeater -->
				</div>
			</div>

			<div class="wpsc-confirmation-totals">
				<div class="wpsc-cart-status">
					Your Cart: <span class="wpsc-cart-count">2</span> items
				</div>

				<div class="wpsc-totals-table wpsc-row">

					<div class="wpsc-totals-table-row wpsc-totals-subtotal">
						<div class="wpsc-totals-row-label">
							Order Subtotal:
						</div>
						<div class="wpsc-totals-row-total">
							$79.90
						</div>
					</div>

					<div class="wpsc-totals-table-row wpsc-totals-shipping">

						<div class="wpsc-totals-row-label">
							Est. Shipping + Handling:
						</div>
						<div class="wpsc-totals-row-total">
							$13.95
						</div>
					</div>

					<div class="wpsc-totals-table-row wpsc-totals-subtotal">
						<div class="wpsc-totals-row-label">
							Subtotal:
						</div>
						<div class="wpsc-totals-row-total">
							$93.85
						</div>
					</div>

				</div>

				<div class="wpsc-cart-notification-actions wpsc-form-actions">
					<button class="wpsc-button wpsc-close-modal">Continue Shopping</button>
					<button class="wpsc-button wpsc-button-primary wpsc-begin-checkout"><i class="wpsc-icon-white wpsc-icon-ok-sign"></i> Checkout Now</button>
				</div>

			</div>

		</div>
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
			"window.{$property_name} = window.{$property_name} || {",
			$script
		);

		$result = $wp_scripts->add_data( $handle, 'data', $script );
	}

	return $result;
}
