/**
 * JavaScript to populate the shared gadgets tab on the preferences page.
 *
 * @author Roan Kattouw
 * @copyright © 2011 Roan Kattouw
 * @license GNU General Public Licence 2.0 or later
 */
( function ( $, mw, undefined ) {
	function hexEncode( s ) {
		var i, c,
			retval = '';
		for ( i = 0; i < s.length; i++ ) {
			c = s.charCodeAt( i ).toString( 16 );
			if ( c.length % 2 == 1 ) {
				c = '0' + c;
			}
			retval += c;
		}
		return retval;
	}

	function fixPreferenceForm( gadgetsByCategory, categoryNames ) {
		var repo, category, gadget, g, escTitle, labelHtml;
		for ( repo in gadgetsByCategory ) {
			if ( repo === 'local' ) {
				// We don't need to fix local Gadgets, leave those alone
				continue;
			}

			for ( category in gadgetsByCategory[repo] ) {
				$( '#mw-htmlform-gadgetcategory-' + hexEncode( repo ) + '-' + hexEncode( category ) )
					.siblings( 'legend' )
					.text( categoryNames[repo][category] );

				for ( gadget in gadgetsByCategory[repo][category] ) {
					g = gadgetsByCategory[repo][category][gadget];
					if ( g.desc === '' ) {
						// Empty description, just use the title
						labelHtml = mw.html.escape( g.title );
					} else {
						labelHtml = mw.msg( 'gadgets-preference-description', g.title );
						// Description needs to be put in manually because it's HTML
						labelHtml = labelHtml.replace( '$2', g.desc );
					}
					$( '#mw-input-gadgetpref-' + hexEncode( gadget ) )
						.siblings( 'label' )
						.html( labelHtml );
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
		var $table = $( '#mw-htmlform-gadgetsshared' ),
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
		var repo, i,
			retval = {};
		for ( repo in data ) {
			retval[repo] = {};
			for ( i = 0; i < data[repo].length; i++ ) {
				retval[repo][data[repo][i].name] = data[repo][i].title;
			}
		}
		return retval;
	}

	$( function () {
		var gadgetsByCategory = null, categoryNames = null, failed = false;

		// TODO spinner

		// Do AJAX requests and call fixPreferenceForm() when done
		mw.gadgets.api.getForeignGadgetsData(
			function ( data ) {
				gadgetsByCategory = reformatGadgetData( data );
				if ( categoryNames !== null ) {
					fixPreferenceForm( gadgetsByCategory, categoryNames );
				}
			},
			function ( error ) {
				if ( !failed ) {
					failed = true;
					showPreferenceFormError( 'gadgets-sharedprefs-ajaxerror' );
				}
			}
		);
		mw.gadgets.api.getForeignGadgetCategories(
			function ( data ) {
				categoryNames = reformatCategoryMap( data );
				if ( gadgetsByCategory !== null ) {
					fixPreferenceForm( gadgetsByCategory, categoryNames );
				}
			},
			function ( error ) {
				if ( !failed ) {
					failed = true;
					showPreferenceFormError( 'gadgets-sharedprefs-ajaxerror' );
				}
			}
		);
	} );

} )( jQuery, mediaWiki );
