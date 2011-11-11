/**
 * JavaScript to populate the shared gadgets tab on the preferences page.
 *
 * @author Roan Kattouw
 * @copyright © 2011 Roan Kattouw
 * @license GNU General Public Licence 2.0 or later
 */
( function( $ ) {
	function fixPreferenceForm( gadgetsByCategory, categoryNames ) {
		for ( repo in gadgetsByCategory ) {
			for ( category in gadgetsByCategory[repo] ) {
				$( document.getElementById( 'mw-htmlform-gadgetcategory-' + repo + '-' + category ) )
					.siblings( 'legend' )
					.text( categoryNames[repo][category] );
					
				for ( gadget in gadgetsByCategory[repo][category] ) {
					// Use getElementById() because we'd have to escape gadget for selector stuff otherwise
					$( document.getElementById( 'mw-input-wpgadget-' + gadget ) )
						.siblings( 'label' )
						.text( gadgetsByCategory[repo][category][gadget].title );
				}
			}
		}
	}
	
	/**
	 * Displays an error on the shared gadgets preferences tab, between the intro text
	 * and the checkboxes. This also unhides the checkboxes container and removes the spinner,
	 * if applicable.
	 * 
	 * @param msgKey {String} Message key of the error message
	 */
	function showPreferenceFormError( msgKey ) {
		var	$table = $( '#mw-htmlform-gadgetsshared' ),
			$errorMsg = $( '<p>' ).addClass( 'error' ).text( mw.msg( msgKey ) );
		
		$table
			.append( $( '<tr>' ).append( $( '<td>' ).attr( 'colspan', 2 ).append( $errorMsg ) ) )
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
		
		// TODO spinner
		
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
