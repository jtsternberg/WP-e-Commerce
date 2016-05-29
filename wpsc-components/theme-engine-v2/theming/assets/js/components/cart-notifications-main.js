( function( window, document, $, notifs, undefined ) {
	'use strict';

	var $id = function( id ) {
		return $( document.getElementById( id ) );
	};

	var log = function() {
		log.history = log.history || [];
		log.history.push( arguments );
		if ( notifs.debug && window.console && window.console.log ) {
			window.console.log( Array.prototype.slice.call(arguments) );
		}
	};

	notifs.currency = require( './utils/currency.js' )( notifs.currency );

	notifs.models = {
		Product : require( './models/product.js' )( notifs.currency, notifs.ajaxurl ),
		Status  : require( './models/status.js' )( notifs.currency, notifs.strings )
	};

	notifs.collections = {
		Products : require( './collections/products.js' )( notifs.currency, notifs.models.Product )
	};

	notifs.views = {
		ProductRow : require( './views/product-row.js' )( log )
	};

	notifs.views.Cart = require( './views/cart.js' )( {
		currency      : notifs.currency,
		statusModel   : notifs.models.Status,
		initialStatus : notifs.CartView.status,
		rowView       : notifs.views.ProductRow
	}, $id, log );


	notifs.init = function() {
		$( document.body )
			.on( 'click', '.wpsc-add-to-cart', notifs.clickAddProductToCart )
			// .on( 'submit', '.wpsc-add-to-cart-form', notifs.clickAddProductToCart )
			.on( 'click', '#wpsc-modal-overlay', notifs.closeModal )
			.append( $id( 'tmpl-wpsc-modal' ).html() );

		// Kick it off.
		notifs.CartView = new notifs.views.Cart({
			collection : new notifs.collections.Products( notifs.CartView.items )
		});
	};

	notifs.clickAddProductToCart = function( evt ) {
		evt.preventDefault();
		var $product = $( this ).parents( '.wpsc-product' );
		notifs.addProductToCart( $product );
	};

	notifs.addProductToCart = function( $product ) {
		notifs.domToModel = notifs.domToModel || require( './utils/product-dom-to-model.js' )( notifs.currency );
		notifs.CartView.trigger( 'add-to-cart', notifs.domToModel.prepare( $product ) );
	};

	notifs.closeModal = function() {
		notifs.CartView.trigger( 'close' );
	};

	notifs.openModal = function() {
		notifs.CartView.trigger( 'open' );
	};

	$( notifs.init );

} )( window, document, jQuery, window.WPSC.cartNotifications );
