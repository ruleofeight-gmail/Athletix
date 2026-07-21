/**
 * Athletix editor blocks.
 *
 * Registers server-rendered dynamic blocks. Each block's markup comes from the
 * PHP render_callback (which reuses the shortcode handlers), so here we only
 * provide the editor UI: an attribute panel plus a live ServerSideRender
 * preview. No build step — this uses the wp.* UMD globals directly.
 *
 * @package Athletix
 */
( function ( wp ) {
	'use strict';

	if ( ! wp || ! wp.blocks || ! wp.element ) {
		return;
	}

	var el = wp.element.createElement;
	var Fragment = wp.element.Fragment;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var ToggleControl = wp.components.ToggleControl;
	var ServerSideRender = wp.serverSideRender;
	var __ = wp.i18n.__;

	/**
	 * Build a labelled number control for an attribute.
	 *
	 * @param {Object} props Block props.
	 * @param {string} key   Attribute name.
	 * @param {string} label Field label.
	 * @return {Object} Control element.
	 */
	function numberField( props, key, label ) {
		return el( TextControl, {
			key: key,
			type: 'number',
			label: label,
			value: props.attributes[ key ],
			onChange: function ( value ) {
				var next = {};
				next[ key ] = parseInt( value, 10 ) || 0;
				props.setAttributes( next );
			},
		} );
	}

	/**
	 * Build a toggle control for a boolean attribute.
	 *
	 * @param {Object} props Block props.
	 * @param {string} key   Attribute name.
	 * @param {string} label Field label.
	 * @return {Object} Control element.
	 */
	function toggleField( props, key, label ) {
		return el( ToggleControl, {
			key: key,
			label: label,
			checked: !! props.attributes[ key ],
			onChange: function ( value ) {
				var next = {};
				next[ key ] = value;
				props.setAttributes( next );
			},
		} );
	}

	/**
	 * Register one Athletix block.
	 *
	 * @param {string}   slug   Block slug (without namespace).
	 * @param {string}   title  Human title.
	 * @param {Function} fields Builds the inspector control list from props.
	 * @return {void}
	 */
	function register( slug, title, fields ) {
		var name = 'athletix/' + slug;

		registerBlockType( name, {
			edit: function ( props ) {
				var blockProps = useBlockProps ? useBlockProps() : {};

				return el(
					Fragment,
					null,
					el(
						InspectorControls,
						null,
						el(
							PanelBody,
							{ title: __( 'Settings', 'athletix' ), initialOpen: true },
							fields( props )
						)
					),
					el(
						'div',
						blockProps,
						el( ServerSideRender, {
							block: name,
							attributes: props.attributes,
						} )
					)
				);
			},
			save: function () {
				// Dynamic block — rendered server-side.
				return null;
			},
		} );
	}

	register( 'standings', __( 'Athletix Standings', 'athletix' ), function ( props ) {
		return [
			numberField( props, 'league', __( 'League ID', 'athletix' ) ),
			numberField( props, 'season', __( 'Season ID (0 = all)', 'athletix' ) ),
		];
	} );

	register( 'roster', __( 'Athletix Roster', 'athletix' ), function ( props ) {
		return [
			numberField( props, 'team', __( 'Team ID', 'athletix' ) ),
			numberField( props, 'league', __( 'League ID', 'athletix' ) ),
			numberField( props, 'columns', __( 'Columns', 'athletix' ) ),
		];
	} );

	register( 'schedule', __( 'Athletix Schedule', 'athletix' ), function ( props ) {
		return [
			numberField( props, 'league', __( 'League ID', 'athletix' ) ),
			numberField( props, 'season', __( 'Season ID (0 = all)', 'athletix' ) ),
			numberField( props, 'limit', __( 'Max matches', 'athletix' ) ),
		];
	} );

	register( 'match', __( 'Athletix Match', 'athletix' ), function ( props ) {
		return [ numberField( props, 'id', __( 'Match ID', 'athletix' ) ) ];
	} );

	register( 'player', __( 'Athletix Player', 'athletix' ), function ( props ) {
		return [
			numberField( props, 'id', __( 'Player ID', 'athletix' ) ),
			toggleField( props, 'stats', __( 'Show statistics', 'athletix' ) ),
		];
	} );
} )( window.wp );
