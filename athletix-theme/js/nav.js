/**
 * Athletix theme — mobile navigation toggle.
 */
( function () {
	'use strict';
	var toggle = document.querySelector( '.ax-nav-toggle' );
	var nav = document.getElementById( 'ax-primary-nav' );
	if ( ! toggle || ! nav ) {
		return;
	}
	toggle.addEventListener( 'click', function () {
		var open = nav.classList.toggle( 'is-open' );
		toggle.setAttribute( 'aria-expanded', open ? 'true' : 'false' );
	} );
} )();
