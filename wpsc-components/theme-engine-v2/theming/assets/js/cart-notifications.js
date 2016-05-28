( function( window, document, $, notifs, undefined ) {
	'use strict';

	var c$ = {};
	var $id = function( id ) {
		return $( document.getElementById( id ) );
	};



	notifs.cache = function() {
		c$.body = $( document.body );
		c$.body.append( $id( 'tmpl-wpsc-modal' ).html() );
		c$.addBtn = $( '.wpsc-add-to-cart' );
		c$.modal = $id( 'wpsc-cart-notification' );
		c$.overlay = $id( 'wpsc-modal-overlay' );
	};

	notifs.init = function() {
		notifs.cache();
		notifs.createCartNotificationElements();
		notifs.bindEvents();

		notifs.modal = wp.template( 'wpsc-modal-inner' );
		// wp.template( 'highlight' )
		window.console.log( 'notifs', notifs );
		notifs.openModal();
	};

	notifs.createCartNotificationElements = function() {
		// c$.body.append(  );
	};

	notifs.bindEvents = function() {
		c$.body
			.on( 'click', '.wpsc-add-to-cart', notifs.triggerModal )
			.on( 'click', '#wpsc-modal-overlay, .wpsc-close-modal', notifs.closeModal );
	};

	notifs.triggerModal = function( evt ) {
		evt.preventDefault();
		notifs.openModal();
	};

	notifs.closeModal = function() {
		c$.overlay.addClass( 'wpsc-hide' );
		c$.modal.addClass( 'wpsc-hide' );
	};

	notifs.openModal = function( data ) {
		data = data || {
			one : 'one'
		};
		c$.overlay.removeClass( 'wpsc-hide' );

		c$.modal.html( notifs.modal( data ) ).removeClass( 'wpsc-hide' );

		// Now that it's open, calculate it's inner height...
		var newHeight = c$.modal.find( '.wpsc-cart-notification-inner' ).outerHeight();

		// And set the height of the modal to match.
		c$.modal.height( Math.round( newHeight ) );
	};


	$( notifs.init );

} )( window, document, jQuery, window.WPSC.cartNotifications );
