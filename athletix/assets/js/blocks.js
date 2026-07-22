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
	var useState = wp.element.useState;
	var useEffect = wp.element.useEffect;
	var registerBlockType = wp.blocks.registerBlockType;
	var InspectorControls = wp.blockEditor.InspectorControls;
	var useBlockProps = wp.blockEditor.useBlockProps;
	var PanelBody = wp.components.PanelBody;
	var TextControl = wp.components.TextControl;
	var SelectControl = wp.components.SelectControl;
	var ToggleControl = wp.components.ToggleControl;
	var ServerSideRender = wp.serverSideRender;
	var apiFetch = wp.apiFetch;
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
	 * A term dropdown that loads its options from the taxonomy REST endpoint,
	 * so editors pick a League/Season by name instead of typing a term id.
	 *
	 * When `filterLeague` is a truthy league id, the request is scoped to that
	 * league (via the athletix_league query arg) and re-runs whenever the league
	 * changes — so, e.g., the Season list only shows that league's seasons.
	 *
	 * @param {Object} props { taxonomy, label, value, onChange, allLabel, filterLeague }.
	 * @return {Object} Control element.
	 */
	function TermSelect( props ) {
		var state = useState( [] );
		var terms = state[ 0 ];
		var setTerms = state[ 1 ];
		var league = parseInt( props.filterLeague, 10 ) || 0;

		useEffect( function () {
			if ( ! apiFetch ) {
				return;
			}
			var path = '/wp/v2/' + props.taxonomy + '?per_page=100&hide_empty=false&_fields=id,name';
			if ( league ) {
				path += '&athletix_league=' + league;
			}
			apiFetch( { path: path } ).then( function ( items ) {
				setTerms( ( items || [] ).map( function ( t ) {
					return { label: t.name, value: String( t.id ) };
				} ) );
			} ).catch( function () {
				setTerms( [] );
			} );
		}, [ props.taxonomy, league ] );

		var options = [ { label: props.allLabel, value: '0' } ].concat( terms );

		return el( SelectControl, {
			label: props.label,
			value: String( props.value || 0 ),
			options: options,
			onChange: function ( value ) {
				props.onChange( parseInt( value, 10 ) || 0 );
			},
		} );
	}

	/**
	 * Build a term-picker control for an attribute.
	 *
	 * @param {Object} props        Block props.
	 * @param {string} key          Attribute name.
	 * @param {string} label        Field label.
	 * @param {string} taxonomy     Taxonomy REST base.
	 * @param {string} allLabel     Label for the "any" option.
	 * @param {number} filterLeague Optional league id to scope options to.
	 * @return {Object} Control element.
	 */
	function termField( props, key, label, taxonomy, allLabel, filterLeague ) {
		return el( TermSelect, {
			key: key + ':' + ( parseInt( filterLeague, 10 ) || 0 ),
			taxonomy: taxonomy,
			label: label,
			allLabel: allLabel,
			filterLeague: filterLeague,
			value: props.attributes[ key ],
			onChange: function ( value ) {
				var next = {};
				next[ key ] = value;
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
			termField( props, 'league', __( 'League', 'athletix' ), 'ax_league', __( '— Select a league —', 'athletix' ) ),
			termField( props, 'season', __( 'Season', 'athletix' ), 'ax_season', __( 'All seasons', 'athletix' ), props.attributes.league ),
		];
	} );

	register( 'roster', __( 'Athletix Roster', 'athletix' ), function ( props ) {
		return [
			numberField( props, 'team', __( 'Team ID', 'athletix' ) ),
			termField( props, 'league', __( 'League', 'athletix' ), 'ax_league', __( 'Any league', 'athletix' ) ),
			numberField( props, 'columns', __( 'Columns', 'athletix' ) ),
		];
	} );

	register( 'schedule', __( 'Athletix Schedule', 'athletix' ), function ( props ) {
		return [
			termField( props, 'league', __( 'League', 'athletix' ), 'ax_league', __( 'Any league', 'athletix' ) ),
			termField( props, 'season', __( 'Season', 'athletix' ), 'ax_season', __( 'All seasons', 'athletix' ), props.attributes.league ),
			numberField( props, 'limit', __( 'Max matches', 'athletix' ) ),
		];
	} );

	register( 'bracket', __( 'Athletix Bracket', 'athletix' ), function ( props ) {
		return [
			termField( props, 'league', __( 'League', 'athletix' ), 'ax_league', __( '— Select a league —', 'athletix' ) ),
			termField( props, 'season', __( 'Season', 'athletix' ), 'ax_season', __( 'All seasons', 'athletix' ), props.attributes.league ),
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
