/**
 * JavaScript to populate the shared gadgets tab on the preferences page.
 *
 * @author Roan Kattouw
 * @copyright © 2011 Roan Kattouw
 * @license GNU General Public Licence 2.0 or later
 */
( function( $ ) {
	function buildPref( id, text ) {
		var	$div = $( '<div class="mw-htmlform-multiselect-item"></div>' ),
			// TODO: checked state should represent preference
			$input = $( '<input>', {
				'type': 'checkbox',
				'name': 'wpgadgets-shared[]',
				'id': 'mw-input-wpgadgets-shared-' + id,
				'value': id
			} );
			if ( mw.user.options.get( 'gadget-' + id ) == "1" ) {
				$input.prop( 'checked', true );
			}
			$label = $( '<label>', { for: 'mw-input-wpgadgets-shared-' + id } )
				.text( text );
		return $div.append( $input ).append( '&nbsp;' ).append( $label );
	}

	function buildForm( gadgetsByCategory, categoryNames ) {
		var ryanscrewedchadover = [], ryanscrewedchadover2 = [];
		var 	$container = $( '#mw-prefsection-gadgets-shared .mw-input' ),
			// Detach the container from the DOM, so we can fill it without visible build-up.
			// This is faster, too. In order to put it back where it was, we need to store its parent.
			$containerParent = $container.parent();
		$container.detach();

		for ( var category in gadgetsByCategory ) {
			if ( category !== '' ) {
				$container.append( $( '<h1>' ).text( categoryNames[category] ) );
			}
			for ( var gadget in gadgetsByCategory[category] ) {
				$container.append( buildPref( gadget, gadgetsByCategory[category][gadget] ) );
				ryanscrewedchadover.push( 'wpgadgets-shared-id-' + gadget );
				ryanscrewedchadover2.push( gadgetsByCategory[category][gadget] );
			}
		}
		// Re-attach the container
		$containerParent.append( $container );
		$containerParent.closest( 'form' ).append( $( '<input>' ).attr( { 'type': 'hidden', 'name': 'ryanscrewedchadover' } ).val( ryanscrewedchadover.join( '|' ) ) );
		$containerParent.closest( 'form' ).append( $( '<input>' ).attr( { 'type': 'hidden', 'name': 'ryanscrewedchadover2' } ).val( ryanscrewedchadover2.join( '|' ) ) );
	}

	// Temporary testing data
	var categoryNames = {
		'foo': 'The Foreign Category of Foo'
	};
	// TODO: This structure allows cross-pollination of categories when multiple foreign repos are involved, do we want that?
	// I guess probably not, because we wouldn't even know where to pull the category message from to begin with.
	// Probably needs an extra level for the repo.
	var gadgetsByCategory = {
		'': {
			'b': 'Gadget B',
			'UTCLiveClock': 'UTCLiveClock'
		},
		'foo': {
			'a': 'Gadget A'
		}
	};

	$( function() { buildForm( gadgetsByCategory, categoryNames ) } );

} )( jQuery );
