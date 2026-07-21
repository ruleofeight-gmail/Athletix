/**
 * Athletix relationship picker.
 *
 * Progressive enhancement for large relationship fields: replaces a native
 * <select> of potentially hundreds of posts with a debounced, AJAX-backed
 * search box. The chosen id is written to a hidden input so the surrounding
 * form submits exactly as the plain <select> did.
 *
 * @package Athletix
 */
( function () {
	'use strict';

	var cfg = window.AthletixPicker || {};

	/**
	 * Debounce a function by the given delay.
	 *
	 * @param {Function} fn    Callback.
	 * @param {number}   delay Milliseconds.
	 * @return {Function} Debounced callback.
	 */
	function debounce( fn, delay ) {
		var timer;
		return function () {
			var args = arguments;
			var self = this;
			window.clearTimeout( timer );
			timer = window.setTimeout( function () {
				fn.apply( self, args );
			}, delay );
		};
	}

	/**
	 * Fetch search results for a picker.
	 *
	 * @param {string}   postType Post type slug.
	 * @param {string}   term     Search term.
	 * @param {Function} done     Receives an array of {id, text}.
	 * @return {void}
	 */
	function search( postType, term, done ) {
		var url =
			cfg.ajaxUrl +
			'?action=athletix_post_search' +
			'&nonce=' + encodeURIComponent( cfg.nonce ) +
			'&post_type=' + encodeURIComponent( postType ) +
			'&s=' + encodeURIComponent( term );

		window
			.fetch( url, { credentials: 'same-origin' } )
			.then( function ( res ) {
				return res.json();
			} )
			.then( function ( payload ) {
				if ( payload && payload.success && payload.data ) {
					done( payload.data.results || [] );
				} else {
					done( [] );
				}
			} )
			.catch( function () {
				done( [] );
			} );
	}

	/**
	 * Wire a single picker root element.
	 *
	 * @param {Element} root Picker container.
	 * @return {void}
	 */
	function initPicker( root ) {
		if ( root.getAttribute( 'data-athletix-ready' ) === '1' ) {
			return;
		}
		root.setAttribute( 'data-athletix-ready', '1' );

		var postType = root.getAttribute( 'data-post-type' );
		var hidden = root.querySelector( '.athletix-picker__value' );
		var input = root.querySelector( '.athletix-picker__search' );
		var list = root.querySelector( '.athletix-picker__results' );

		if ( ! hidden || ! input || ! list ) {
			return;
		}

		/**
		 * Render a result list.
		 *
		 * @param {Array} results Result rows.
		 * @return {void}
		 */
		function render( results ) {
			list.innerHTML = '';
			if ( ! results.length ) {
				list.hidden = true;
				return;
			}
			results.forEach( function ( row ) {
				var item = document.createElement( 'button' );
				item.type = 'button';
				item.className = 'athletix-picker__option';
				item.textContent = row.text;
				item.setAttribute( 'data-id', row.id );
				item.addEventListener( 'click', function () {
					hidden.value = row.id;
					input.value = row.text;
					list.hidden = true;
					list.innerHTML = '';
				} );
				list.appendChild( item );
			} );
			list.hidden = false;
		}

		var onType = debounce( function () {
			var term = input.value.trim();
			// Typing a new query invalidates the previous selection.
			hidden.value = '0';
			search( postType, term, render );
		}, 250 );

		input.addEventListener( 'input', onType );
		input.addEventListener( 'focus', function () {
			if ( ! list.innerHTML ) {
				search( postType, input.value.trim(), render );
			}
		} );

		// Dismiss the results when focus leaves the widget.
		document.addEventListener( 'click', function ( event ) {
			if ( ! root.contains( event.target ) ) {
				list.hidden = true;
			}
		} );
	}

	/**
	 * Initialise every picker on the page.
	 *
	 * @return {void}
	 */
	function initAll() {
		var roots = document.querySelectorAll( '.athletix-picker' );
		Array.prototype.forEach.call( roots, initPicker );
	}

	if ( document.readyState === 'loading' ) {
		document.addEventListener( 'DOMContentLoaded', initAll );
	} else {
		initAll();
	}
} )();
