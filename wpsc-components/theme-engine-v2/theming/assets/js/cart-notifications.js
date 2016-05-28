( function( window, document, $, notifs, undefined ) {
	'use strict';

	var c$ = {};
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

	notifs.cache = function() {
		c$.body = $( document.body );
		c$.body.append( $id( 'tmpl-wpsc-modal' ).html() );
		c$.modal = $id( 'wpsc-cart-notification' );
		c$.overlay = $id( 'wpsc-modal-overlay' );
	};

	notifs.models = {};
	notifs.collections = {};
	notifs.views = {};

	notifs.models.Product = Backbone.Model.extend({
		defaults: {
			id         : 0,
			url        : '',
			price      : '',
			title      : '',
			thumb      : '',
			quantity   : 0,
			remove_url : '',
			variations : []
		},

		url: function() {
			var url = notifs.ajaxurl +'?action=wpsc_cart_item&id='+ encodeURIComponent( this.id ) +'&action=';

			if ( this.get( 'edit' ) ) {
				url += 'edit&quantity=' + this.get( 'quantity' );
			} else {
				url += 'delete';
			}

			return url;
		}
	});

	notifs.models.Status = Backbone.Model.extend({
		defaults: {
			subTotal      : 0,
			shippingTotal : 0,
			numberAdded   : 1,
			numberItems   : 0
		}
	});

	notifs.collections.Products = Backbone.Collection.extend({ model : notifs.models.Product });

	/**
	 * Cart view
	 */
	notifs.views.Cart = Backbone.View.extend({
		el: '#wpsc-cart-notification',
		template  : wp.template( 'wpsc-modal-inner' ),
		status: {},
		events   : {
			'click .wpsc-close-modal' : 'evtClose'
		},

		initialize: function() {
			this.status = new notifs.models.Status( notifs._cartStatus );

			this.listenTo( this.collection, 'remove', this.checkEmpty );
			this.listenTo( this.collection, 'render', this.render );
			this.listenTo( this.collection, 'change', this.render );
			this.listenTo( this.status, 'change', this.maybeUpdateView );
			this.listenTo( this, 'open', this.render );
			this.listenTo( this, 'close', this.close );
			this.listenTo( this, 'update', this.update );
		},

		render: function() {
			this.$el.empty();

			var addedElements = document.createDocumentFragment();

			// create a sub view for every model in the collection
			this.collection.each( function( model ) {
				var row = new notifs.views.Row({ model: model });
				addedElements.appendChild( row.render().el );
			});

			c$.overlay.removeClass( 'wpsc-hide' );

			this.$el.html( this.template( this.status.toJSON() ) ).removeClass( 'wpsc-hide' );

			this.$el.find( '.wpsc-cart-what-was-added' ).append( addedElements );

			// Now that it's open, calculate it's inner height...
			var newHeight = this.$el.find( '.wpsc-cart-notification-inner' ).outerHeight();

			var winHeight = $( window ).height();
			var maxHeight = winHeight - ( winHeight * 0.3 );
			newHeight = newHeight > maxHeight ? maxHeight : newHeight;

			// And set the height of the modal to match.
			this.$el.height( Math.round( newHeight ) );

		},

		checkEmpty: function() {
			if ( ! this.collection.length ) {
				// Close modal?
			}
			this.$el.find( '.wpsc-cart-count' ).text( this.collection.length );
		},

		maybeUpdateView: function( modelChanged ) {
			if ( modelChanged.changed.numberAdded || modelChanged.changed.numberItems ) {
				log( 'modelChanged', modelChanged );
				// this.render();
			}
		},

		evtClose: function( evt ) {
			evt.preventDefault();
			this.close();
		},

		close: function() {
			c$.overlay.addClass( 'wpsc-hide' );
			this.$el.addClass( 'wpsc-hide' );
		},

		update: function( data ) {
			var model = this.collection.create( data );

			var prevNumber = this.status.get( 'numberItems' );
			this.status.set( 'numberItems', this.collection.length );
			this.status.set( 'numberAdded', this.status.get( 'numberItems' ) - prevNumber );

			this.render();

			return model;
		}


	});

	/**
	 * Product row
	 */
	notifs.views.Row = Backbone.View.extend({
		tagName   : 'div',
		className : 'wpsc-cart-body',
		template  : wp.template( 'wpsc-modal-product' ),
		// Attach events
		events   : {
			// 'click .wpsc-cart-item-edit' : 'edit'
			// 'click .wpsc-cart-item-remove' : 'removeIt'
		},

		// Render the row
		render: function() {
			this.$el.html( this.template( this.model.toJSON() ) );
			return this;
		},

		edit: function(e) {
			e.preventDefault();

			// Show quantity input.
		},

		// Perform the Removal
		removeIt: function(e) {
			e.preventDefault();
			var _this     = this;

			// Ajax error handler
			var destroyError = function( model, response ) {
				log( 'destroyError', response );
				// whoops.. re-show row and add error message
				_this.$el.fadeIn( 300 );
			};

			// Ajax success handler
			var destroySuccess = function( model, response ) {
				// If our response reports success
				if ( response.success ) {
					log( 'destroySuccess', response );
					// remove our row completely
					_this.$el.remove();
				} else {
					// whoops, error
					destroyError( model, response );
				}
			};

			// Hide error message (if it's showing)
			// Optimistically hide row
			_this.$el.fadeOut( 300 );

			// Remove model and fire ajax event
			this.model.destroy({ success: destroySuccess, error: destroyError, wait: true });

		}
	});

	notifs.init = function() {
		notifs.cache();

		// Send the model data to our table view
		notifs.CartView = new notifs.views.Cart({
			collection : new notifs.collections.Products( notifs._cartItems )
		});

		notifs.bindEvents();
		log( 'notifs', notifs );
	};

	notifs.bindEvents = function() {
		c$.body
			.on( 'click', '.wpsc-add-to-cart', notifs.evtOpen )
			.on( 'click', '#wpsc-modal-overlay', notifs.closeModal );
	};

	notifs.evtOpen = function( evt ) {
		evt.preventDefault();
		var $this    = $( this );
		var $product = $this.parents( '.wpsc-product' );
		var $thumb = $product.find( '.wpsc-product-thumbnail' );
		var product = {
			id         : 0,
			url        : '',
			price      : '',
			title      : '',
			thumb      : '',
			quantity   : 0,
			remove_url : '',
			variations : []
		};

		product.id    = $product.attr( 'id' ).split( '-' ).pop();
		product.url   = $thumb.length ? $thumb.attr( 'href' ) : $product.find( '.wpsc-product-title > a' ).attr( 'href' );
		product.price = $product.find( '.wpsc-product-price .wpsc-sale-price .wpsc-amount' ).length ? $product.find( '.wpsc-product-price .wpsc-sale-price .wpsc-amount' ).text() : $product.find( '.wpsc-product-price .wpsc-amount' ).last().text();
		product.title = $product.find( '.wpsc-product-title > a' ).text();
		product.thumb = $thumb.length ? $product.find( '.wpsc-product-thumbnail' ).html() : '';
		product.quantity = $product.find( '[name="quantity"]' ).val();


		notifs.CartView.trigger( 'update', product );
		// notifs.openModal( product );
	};

	notifs.closeModal = function() {
		notifs.CartView.trigger( 'close' );
	};

	notifs.openModal = function() {
		notifs.CartView.trigger( 'open' );
	};


	$( notifs.init );

} )( window, document, jQuery, window.WPSC.cartNotifications );
