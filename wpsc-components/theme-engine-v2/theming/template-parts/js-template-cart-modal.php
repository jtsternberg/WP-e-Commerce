<?php
/**
 * The template part for displaying cart notification modal.
 *
 * Override this template by copying it to theme-folder/wp-e-commerce/feedback-no-products.php
 *
 * @author   WP eCommerce
 * @package  WP-e-Commerce/Templates
 * @version  4.0
 */
?>

<div class="wpsc-cart-notification-inner">

	<div class="wpsc-close-modal wpsc-icon-remove"><?php _e( 'Close', 'wp-e-commerce' ); ?></div>

	<!-- WP eCommerce Cart Notification Form Begins -->
	<form class="wpsc-form wpsc-cart-form" action="<?php echo esc_url( wpsc_get_cart_url() ); ?>"  method="post">

		<!-- WP eCommerce Checkout Table Begins -->
		<div class="wpsc-cart-table wpsc-table wpsc-cart-item-table">

			<div class="wpsc-cart-what-was-added">
				<# if ( data.numberAdded ) { #>
					<div class="wpsc-confirmation-message">
						<i class="wpsc-icon-check"></i><span class="wpsc-confirmation-count">{{ data.numberAdded }}</span> <?php _ex( 'item(s) added', 'Number of items added to the shopping cart', 'wp-e-commerce' ); ?>
					</div>
				<# } #>

			</div>

			<div class="wpsc-cart-footer wpsc-confirmation-totals">

				<div class="wpsc-cart-status">
					<a class="wpsc-cart-link" href="<?php echo esc_url( wpsc_get_cart_url() ); ?>">Your Cart:</a> <span class="wpsc-cart-count">{{ data.numberItems }}</span> <?php _ex( 'item(s)', 'Number of items in the shopping cart', 'wp-e-commerce' ); // @todo _noop these strings. ?>
				</div>

				<div class="wpsc-totals-table">

					<# if ( data.subTotal ) { #>
						<div class="wpsc-cart-aggregate wpsc-cart-subtotal-row">
							<div class="wpsc-totals-row-label">
							   <?php _e( 'Order Subtotal:', 'wp-e-commerce' ); ?>
							</div>
							<div class="wpsc-totals-row-total">
								{{ data.subTotal }}
							</div>
						</div>
					<# } #>

					<# if ( data.shippingTotal ) { #>
						<div class="wpsc-cart-aggregate wpsc-cart-shipping-row">
							<div class="wpsc-totals-row-label">
								<?php _e( 'Est. Shipping + Handling:', 'wp-e-commerce' ); ?>
							</div>
							<div class="wpsc-totals-row-total">
								{{ data.shippingTotal }}
							</div>
						</div>
					<# } #>

					<# if ( data.total ) { #>
						<div class="wpsc-cart-aggregate wpsc-cart-total-row">
							<div class="wpsc-totals-row-label">
								<?php _e( 'Subtotal:', 'wp-e-commerce' ); ?>
							</div>
							<div class="wpsc-totals-row-total">
								{{ data.total }}
							</div>
						</div>
					<# } #>

				</div>

				<div class="wpsc-form-actions bottom">
					<?php wpsc_form_button( '', __( 'Continue Shopping', 'wp-e-commerce' ), array( 'class' => 'wpsc-button wpsc-close-modal' ) ); ?>
					<# if ( data.numberItems ) { #>
						<?php wpsc_begin_checkout_button(); ?>
					<# } #>
				</div>

			</div>

		</div>
		<!-- WP eCommerce Checkout Table Ends -->
	</form>
	<!-- WP eCommerce Cart Form Ends -->
</div>
