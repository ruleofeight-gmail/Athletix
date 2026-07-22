/**
 * Batch "Add Another" behaviour for the Athletix quick-add screens.
 *
 * Submitting the form (either button) saves over AJAX instead of reloading; the
 * new record is appended to an "added this session" list. The primary button
 * then clears the whole form for a fresh entry, while "Add Another" keeps the
 * shared context fields (Sport/League/Division, or Team) and clears only the
 * per-item fields, dropping the cursor back on the name. On the player screen it
 * also keeps the Position dropdown in sync with the chosen team's sport.
 *
 * No build step — plain browser globals. Progressive enhancement: with this
 * script absent the form still posts to admin-post.php and works.
 *
 * @package Athletix
 */
( function () {
	'use strict';

	var cfg = window.AthletixQuickAdd;
	if ( ! cfg ) {
		return;
	}

	var form = document.querySelector( '.athletix-quick-add__form' );
	if ( ! form ) {
		return;
	}

	var count = 0;

	/**
	 * HTML-escape a value for safe insertion.
	 *
	 * @param {string} value Raw value.
	 * @return {string} Escaped value.
	 */
	function esc( value ) {
		var d = document.createElement( 'div' );
		d.textContent = null === value || undefined === value ? '' : value;
		return d.innerHTML;
	}

	/**
	 * Show a dismissible inline notice at the top of the form.
	 *
	 * @param {string} type    'success' or 'error'.
	 * @param {string} message Text.
	 * @param {string} link    Optional edit URL.
	 * @return {void}
	 */
	function notify( type, message, link ) {
		var box = document.getElementById( 'athletix-quick-notice' );
		if ( ! box ) {
			return;
		}
		var edit = link ? ' <a href="' + esc( link ) + '">' + esc( cfg.i18n.edit ) + '</a>' : '';
		box.className = 'notice notice-' + ( 'success' === type ? 'success' : 'error' ) + ' is-dismissible';
		box.innerHTML = '<p>' + esc( message ) + edit + '</p>';
		box.hidden = false;
	}

	/**
	 * Add the created record to the running "added this session" list.
	 *
	 * @param {Object} data { id, title, editLink }.
	 * @return {void}
	 */
	function recordAdded( data ) {
		var wrap = document.getElementById( 'athletix-added' );
		var list = document.getElementById( 'athletix-added-list' );
		var head = document.getElementById( 'athletix-added-count' );
		if ( ! wrap || ! list ) {
			return;
		}

		count++;
		if ( head ) {
			head.textContent = count;
		}

		var li = document.createElement( 'li' );
		if ( data.editLink ) {
			li.innerHTML = '<a href="' + esc( data.editLink ) + '">' + esc( data.title ) + '</a>';
		} else {
			li.textContent = data.title;
		}
		list.insertBefore( li, list.firstChild );
		wrap.hidden = false;
	}

	/**
	 * Clear form fields, preserving the context fields on "Add Another".
	 *
	 * @param {boolean} keepContext Keep the shared context fields.
	 * @return {void}
	 */
	function reset( keepContext ) {
		var context = keepContext ? cfg.contextFields : [];

		Array.prototype.forEach.call( form.elements, function ( field ) {
			if ( ! field.name || 'hidden' === field.type || 'submit' === field.type ) {
				return;
			}
			if ( context.indexOf( field.name ) !== -1 ) {
				return;
			}
			if ( 'SELECT' === field.tagName ) {
				field.selectedIndex = 0;
			} else if ( 'checkbox' === field.type || 'radio' === field.type ) {
				field.checked = false;
			} else {
				field.value = '';
			}
		} );

		var focus = document.getElementById( cfg.focusField );
		if ( focus ) {
			focus.focus();
		}
	}

	/**
	 * Submit the form over AJAX.
	 *
	 * @param {boolean} another Whether "Add Another" was used.
	 * @return {void}
	 */
	function submit( another ) {
		var body = new FormData( form );
		body.set( 'action', cfg.createAction );
		body.set( 'nonce', cfg.nonce );

		fetch( cfg.ajaxUrl, {
			method: 'POST',
			credentials: 'same-origin',
			body: body
		} )
			.then( function ( r ) { return r.json(); } )
			.then( function ( res ) {
				if ( ! res || ! res.success ) {
					var code = res && res.data ? res.data.code : '';
					notify( 'error', 'name' === code ? cfg.i18n.nameReq : cfg.i18n.failed, '' );
					var name = document.getElementById( cfg.focusField );
					if ( name ) {
						name.focus();
					}
					return;
				}

				recordAdded( res.data );
				notify( 'success', cfg.i18n.added, res.data.editLink );
				reset( another );
			} )
			.catch( function () {
				notify( 'error', cfg.i18n.failed, '' );
			} );
	}

	form.addEventListener( 'submit', function ( e ) {
		e.preventDefault();
		var another = !! ( e.submitter && 'athletix_add_another' === e.submitter.name );
		submit( another );
	} );

	// Player screen only: keep Position in step with the team's sport.
	if ( 'player' === cfg.kind ) {
		bindPositions();
	}

	/**
	 * Wire the team → sport → position-list behaviour.
	 *
	 * @return {void}
	 */
	function bindPositions() {
		var team  = document.getElementById( 'athletix_team' );
		var field = document.getElementById( 'athletix_position_field' );
		var label = document.getElementById( 'athletix_sport_label' );
		if ( ! team || ! field ) {
			return;
		}

		function renderText( hint ) {
			field.innerHTML = '<input name="athletix_position" id="athletix_position" type="text" class="regular-text" />' +
				( hint ? '<p class="description">' + esc( hint ) + '</p>' : '' );
		}

		function renderSelect( positions ) {
			var html = '<select name="athletix_position" id="athletix_position">';
			html += '<option value="">' + esc( cfg.i18n.none ) + '</option>';
			positions.forEach( function ( pos ) {
				html += '<option value="' + esc( pos ) + '">' + esc( pos ) + '</option>';
			} );
			html += '</select>';
			field.innerHTML = html;
		}

		function load() {
			var id = parseInt( team.value, 10 ) || 0;
			if ( label ) {
				label.textContent = '';
			}
			if ( ! id ) {
				renderText( '' );
				return;
			}

			var url = cfg.ajaxUrl + '?action=' + encodeURIComponent( cfg.positionsAction ) +
				'&nonce=' + encodeURIComponent( cfg.positionsNonce ) + '&team=' + id;

			fetch( url, { credentials: 'same-origin' } )
				.then( function ( r ) { return r.json(); } )
				.then( function ( res ) {
					if ( ! res || ! res.success ) {
						renderText( '' );
						return;
					}
					if ( label && res.data.label ) {
						label.textContent = cfg.i18n.sport.replace( '%s', res.data.label );
					}
					if ( res.data.positions && res.data.positions.length ) {
						renderSelect( res.data.positions );
					} else {
						renderText( cfg.i18n.free );
					}
				} )
				.catch( function () { renderText( '' ); } );
		}

		team.addEventListener( 'change', load );

		// A preselected team (carried through "Add Another") loads its positions.
		if ( parseInt( team.value, 10 ) ) {
			load();
		}
	}
}() );
