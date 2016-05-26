/**
 * WP eCommerce Fancy Notifications JS
 *
 * @since  4.0
 */

window.WPEC_Fancy_Notifications = window.WPEC_Fancy_Notifications || {};

( function( window, document, $, notifs, undefined ) {
	'use strict';

	/**
	 * Cache all selectors.
	 *
	 * @since  4.0
	 */
	notifs.cache = function() {
		notifs.$              = {};
		notifs.$.notif        = $( document.getElementById( 'fancy_notification' ) );
		notifs.$.animation    = $( 'div.wpsc_loading_animation' );
		notifs.$.loader       = $( document.getElementById( 'loading_animation' ) );
		notifs.$.notifContent = $( document.getElementById( 'fancy_notification_content' ) );
	};

	/**
	 * Kick off our notification handlers.
	 *
	 * @since  4.0
	 */
	notifs.init = function() {
		notifs.cache();
		notifs.bindEvents();
	};

	/**
	 * Event Handlers
	 *
	 * @since  4.0
	 */
	notifs.bindEvents = function() {
		$( document )
			.on( 'ready', notifs.appendToBody )
			.on( 'wpscAddToCart', notifs.wpscAddToCart )
			.on( 'wpscAddedToCart', notifs.wpscAddedToCart );
	};

	/**
	 * Move Fancy Notification element to end of HTML body.
	 *
	 * @since  4.0
	 */
	notifs.appendToBody = function() {
		notifs.$.notif.appendTo( 'body' );
	};

	/**
	 * Fancy Notification: Show
	 *
	 * @since  4.0
	 */
	notifs.wpscAddToCart = function() {
		notifs.$.animation.css( 'visibility', 'hidden' );
		notifs.fancy_notification();
	};

	/**
	 * Fancy Notification: Hide
	 *
	 * @since  4.0
	 */
	notifs.wpscAddedToCart = function( evt ) {

		if ( evt.response ) {
			if ( evt.response.fancy_notification && notifs.$.notifContent.length ) {
				notifs.$.loader.hid();
				notifs.$.notifContent.html( evt.response.fancy_notification ).show();
			}
			$( document ).trigger( { type : 'wpsc_fancy_notification', response : evt.response } );
		}

		if ( notifs.$.notif.length > 0 ) {
			notifs.$.loader.hid();
		}

	};

	/**
	 * Fancy Notification
	 *
	 * @since  4.0
	 */
	notifs.fancy_notification = function() {

		if ( 'undefined' === typeof window.WPSC_SHOW_FANCY_NOTIFICATION ) {
			window.WPSC_SHOW_FANCY_NOTIFICATION = true;
		}

		if ( true === window.WPSC_SHOW_FANCY_NOTIFICATION && null !== notifs.$.notif ) {
			notifs.$.notif.css( {
				display  : 'block',
				position : 'fixed',
				left     : ( $( window ).width() - notifs.$.notif.outerWidth() ) / 2,
				top      : ( $( window ).height() - notifs.$.notif.outerHeight() ) / 2
			} );
			notifs.$.loader.show();
			notifs.$.notifContent.hid();
		}

	};

	$( notifs.init );

} )( window, document, jQuery, window.WPEC_Fancy_Notifications );
