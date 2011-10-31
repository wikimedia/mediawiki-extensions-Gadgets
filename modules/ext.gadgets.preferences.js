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
	 * @param gadgetsByCategory {Object} Map of { repo: { categoryID: { gadgetID: gadgetObj } } }
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
			if ( repo == 'local' ) {
				// Skip local repository
				// FIXME: Just don't request the info in the first place then, waste of API reqs
				continue;
			}
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
					$oldItem.find( 'label' ).text( gadgetsByCategory[repo][category][gadget].title );
					// Move the item from $oldContainer to $newContainer
					$newContainer.append( $oldItem );
				}
			}
		}
		$oldContainer.replaceWith( $newContainer );
		// Unhide the container by removing the unloaded class, and remove the spinner too
		$spinner.removeClass( 'mw-gadgetsshared-item-unloaded mw-ajax-loader' );
	}
	
	/**
	 * Displays an error on the shared gadgets preferences tab, between the intro text
	 * and the checkboxes. This also unhides the checkboxes container and removes the spinner,
	 * if applicable.
	 * 
	 * @param msgKey {String} Message key of the error message
	 */
	function showPreferenceFormError( msgKey ) {
		var	$oldContainer = $( '#mw-prefsection-gadgetsshared' ).find( '.mw-input' ),
			$oldContainerTR = $oldContainer.closest( '.mw-gadgetsshared-item-unloaded' ),
			$errorMsg = $( '<p>' ).addClass( 'error' ).text( mw.msg( msgKey ) );
		
		$oldContainerTR
			.before( $( '<tr>' ).append( $( '<td>' ).attr( 'colspan', 2 ).append( $errorMsg ) ) )
			// Unhide the container and remove the spinner
			.removeClass( 'mw-gadgetsshared-item-unloaded mw-ajax-loader' );
	}
	
	/**
	 * Reformat the output of mw.gadgets.api.getForeignGadgetsData() to
	 * suitable input for fixPreferenceForm()
	 * 
	 * @param data {Object} { repo: { gadgetID: gadgetObj } }
	 * @return {Object} { repo: { categoryID: { gadgetID: gadgetObj } } }
	 */
	function reformatGadgetData( data ) {
		var retval = {}, repo, gadget, category;
		for ( repo in data ) {
			retval[repo] = { '': {} }; // Make sure '' is first in the list, fixPreferenceForm() needs it to be
			for ( gadget in data[repo] ) {
				category = data[repo][gadget].metadata.settings.category;
				if ( retval[repo][category] === undefined ) {
					retval[repo][category] = {};
				}
				retval[repo][category][gadget] = data[repo][gadget];
			}
		}
		return retval;
	}
	
	/**
	 * Reformat the output of mw.gadgets.api.getForeignGadgetCategories()
	 * to suitable input for fixPreferenceForm()
	 * 
	 * @param data {Object} { repo: [ { name: categoryID, title: categoryTitle } ] }
	 * @return { repo: { categoryID: categoryTitle } }
	 */
	function reformatCategoryMap( data ) {
		var retval = {}, repo, i;
		for ( repo in data ) {
			retval[repo] = {};
			for ( i = 0; i < data[repo].length; i++ ) {
				retval[repo][data[repo][i].name] = data[repo][i].title;
			}
		}
		return retval;
	} 

	$( function() {
		var gadgetsByCategory = null, categoryNames = null, failed = false;
		
		// Add spinner
		$( '#mw-prefsection-gadgetsshared' ).find( '.mw-gadgetsshared-item-unloaded' ).addClass( 'mw-ajax-loader' );
		
		// Do AJAX requests and call fixPreferenceForm() when done
		mw.gadgets.api.getForeignGadgetsData(
			function( data ) {
				gadgetsByCategory = reformatGadgetData( data );
				if ( categoryNames !== null ) {
					fixPreferenceForm( gadgetsByCategory, categoryNames );
				}
			},
			function( error ) {
				if ( !failed ) {
					failed = true;
					showPreferenceFormError( 'gadgets-sharedprefs-ajaxerror' );
				}
			}
		);
		mw.gadgets.api.getForeignGadgetCategories(
			function( data ) {
				categoryNames = reformatCategoryMap( data );
				if ( gadgetsByCategory !== null ) {
					fixPreferenceForm( gadgetsByCategory, categoryNames );
				}
			},
			function( error ) {
				if ( !failed ) {
					failed = true;
					showPreferenceFormError( 'gadgets-sharedprefs-ajaxerror' );
				}
			}
		);
	} );

} )( jQuery );
