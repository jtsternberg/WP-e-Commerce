( function( window, document, $, notifs, undefined ) {
	'use strict';

	notifs.cache = function() {
		notifs.$ = {};
	};

	notifs.init = function() {
		notifs.cache();

		window.console.log( 'notifs', notifs );
	};

	$( notifs.init );

} )( window, document, jQuery, window.WPSC.cartNotifications );
