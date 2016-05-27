( function( window, document, $, notifs, undefined ) {
	'use strict';

	var c$ = {};
	var $id = function( id ) {
		return $( document.getElementById( id ) );
	};



	notifs.cache = function() {
		c$.body = $( document.body );
		c$.addBtn = $( '.wpsc-add-to-cart' );
	};

	notifs.init = function() {
		notifs.cache();
		notifs.createCartNotificationElements();
		notifs.bindEvents();

		c$.body.append( $id( 'tmpl-wpsc-modal' ).html() );
		notifs.modal = wp.template( notifs._templates['wpsc-modal'] );
		window.console.log( 'notifs', notifs );
	};

	notifs.createCartNotificationElements = function() {
		// c$.body.append(  );
	};

	notifs.bindEvents = function() {

	};

	notifs.openModal = function( data ) {
		notifs.modal( data );
	};


	$( notifs.init );

} )( window, document, jQuery, window.WPSC.cartNotifications );
