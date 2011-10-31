/**
 * JavaScript to populate the shared gadgets tab on the preferences page.
 *
 * @author Roan Kattouw
 * @copyright © 2011 Roan Kattouw
 * @license GNU General Public Licence 2.0 or later
 */
( function( $ ) {
	/**
	 * Fixes the description and the categorization for shared gadgets in the preferences form.
	 * This hides the container td for shared gadgets preferences, reorders them by moving them
	 * into a new td in the right order, updates their label texts, then replaces the old td with
	 * the new one.
	 * @param gadgetsByCategory {Object} Map of { repo: { categoryID: { gadgetID: gadgetDescription } } }
	 * @param categoryNames {Object} Map of { repo: { categoryID: categoryDescription } }
	 */
	function fixPreferenceForm( gadgetsByCategory, categoryNames ) {
		// TODO i18n the repo names somehow
		// TODO h1 and h2 need better styling
		var 	$oldContainer = $( '#mw-prefsection-gadgetsshared' ).find( '.mw-input' ),
			$newContainer = $( '<td>' ).addClass( 'mw-input' ),
			$spinner = $oldContainer.closest( '.mw-gadgetsshared-item-unloaded' ),
			repo, category, gadget, $oldItem;
		for ( repo in gadgetsByCategory ) {
			$newContainer.append( $( '<h1>' ).text( repo ) );
			for ( category in gadgetsByCategory[repo] ) {
				if ( category !== '' ) {
					$newContainer.append( $( '<h2>' ).text( categoryNames[repo][category] ) );
				}
				for ( gadget in gadgetsByCategory[repo][category] ) {
					// Find the item belonging to this gadget in $oldContainer
					$oldItem = $oldContainer
						.find( '#mw-input-wpgadgetsshared-' + gadget )
						.closest( '.mw-htmlform-multiselect-item' );
					// Update the label text
					$oldItem.find( 'label' ).text( gadgetsByCategory[repo][category][gadget] );
					// Move the item from $oldContainer to $newContainer
					$newContainer.append( $oldItem );
				}
			}
		}
		$oldContainer.replaceWith( $newContainer );
		// Unhide the container by removing the unloaded class, and remove the spinner too
		$spinner.removeClass( 'mw-gadgetsshared-item-unloaded mw-ajax-loader' );
	}

	// Temporary testing data
	var categoryNames = {
		'wiki2': {
			'foo': 'The Foreign Category of Foo'
		},
		'wiki3': {
			'foo': 'The Remote Category of Foo',
			'bar': 'The Remote Category of Bar'
		}
	};
	// TODO: Actually fetch this info using AJAX
	var gadgetsByCategory = {
		'wiki2': {
			'': {
				'b': 'Gadget B',
				'UTCLiveClock': 'A clock that displays UTC time blah blah blah'
			},
			'foo': {
				'a': 'Gadget A'
			}
		},
		'wiki3': {
			'': {
				'c': 'Gadget C',
				'd': 'Gadget D'
			},
			'foo': {
				'e': 'Gadget E'
			},
			'bar': {
				'f': 'Gadget F',
				'g': 'Gadget G'
			}
		}
	};

	$( function() {
		// TODO make all of this nicer once we have AJAX
		// Add spinner
		$( '#mw-prefsection-gadgetsshared' ).find( '.mw-gadgetsshared-item-unloaded' ).addClass( 'mw-ajax-loader' );
		// Simulate AJAX delay
		setTimeout( function() { fixPreferenceForm( gadgetsByCategory, categoryNames ) }, 2000 );
	} );

} )( jQuery );
