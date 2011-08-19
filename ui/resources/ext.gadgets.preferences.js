/*
 * JavaScript tweaks for Special:Preferences
 */
( function( $, mw ) {

	//Deep comparison of two objects. It assumes that the two objects
	//have the same keys (recursively).
	function deepEquals( a, b ) {
		if ( a === b ) {
			return true;
		}
		
		if ( $.isArray( a ) ) {
			if ( !$.isArray( b ) || a.length != b.length ) {
				return false;
			}
			
			for ( var i = 0; i < a.length; i++ ) {
				if ( !deepEquals( a[i], b[i] ) ) {
					return false;
				}
			}
		} else {
			if ( typeof a != 'object' || typeof b != 'object' ) {
				return false;
			}
			
			for ( var key in a ) {
				if ( a.hasOwnProperty( key ) ) {
					if ( !deepEquals( a[key], b[key] ) ) {
						return false;
					}
				}
			}
		}
		return true;
	}
	
	//Deletes a stylesheet object
	function removeStylesheet( styleSheet ) {
		var owner =
			styleSheet.ownerNode ?
			styleSheet.ownerNode :    //not-IE or IE >= 9
			styleSheet.owningElement; //IE < 9
		owner.parentNode.removeChild( owner );
	} 

	//Shows a message in the bottom of the dialog, with fading
	function showMsg( msg ) {
		if ( msg === null ) {
			$( '#mw-gadgets-prefsDialog-message' ).fadeTo( 200, 0 );
		} else {
			$( '#mw-gadgets-prefsDialog-message' )
				.text( msg )
				.fadeTo( 200, 1 );
		}
	}
	
	//"Save" button click handler
	function saveConfig( $dialog, gadget, config ) {
		var prefsJson = $.toJSON( config );
		
		//disable all dialog buttons
		$( '#mw-gadgets-prefsDialog-close, #mw-gadgets-prefsDialog-save' ).button( 'disable' );
		
		//Set cursor to "wait" for all elements; save the stylesheet so it can be removed later
		var waitCSS = mw.util.addCSS( '* { cursor: wait !important; }' );
		
		//just to avoid code duplication
		function error() {
			//Remove "wait" cursor
			removeStylesheet( waitCSS );
			
			//Warn the user
			showMsg( mw.msg( 'gadgets-save-failed' ) );

			//Enable all dialog buttons
			$( '#mw-gadgets-prefsDialog-close, #mw-gadgets-prefsDialog-save' ).button( 'enable' );
		}
		
		$.ajax( {
			url: mw.config.get( 'wgScriptPath' ) + '/api.php',
			type: "POST",
			data: {
				'action': 'setgadgetprefs',
				'gadget': gadget,
				'prefs': prefsJson,
				'token': mw.user.tokens.get('editToken'),
				'format': 'json'
			},
			dataType: "json",
			success: function( response ) {
				if ( typeof response.error == 'undefined' ) {
					//Remove "wait" cursor
					removeStylesheet( waitCSS );
			
					//Notify success to user
					showMsg( mw.msg( 'gadgets-save-success' ) );

					//enable cancel button (leaving 'save' disabled)
					$( '#mw-gadgets-prefsDialog-close' ).button( 'enable' );
					//update 'savedConfig'
					$dialog.data( 'savedValues', config );
				} else {
					error();
				}
			},
			error: error
		} );
	}
	
	$( '#mw-htmlform-gadgets input[name="wpgadgets[]"]' ).each( function( idx, input ) {
		var	id = input.id,
			gadget = id.substr( "mw-input-wpgadgets-".length );

		if ( $.inArray( gadget, mw.gadgets.configurableGadgets ) != -1 ) {
			var $span = $( '<span></span>' );

			if ( !$( input ).is( ':checked' ) ) {
				$span.hide();
			}

			var $link = $( '<a></a>' )
				.text( mw.msg( 'gadgets-configure' ) )
				.click( function() {
					$.ajax( {
						url: mw.config.get( 'wgScriptPath' ) + '/api.php',
						type: "POST",
						data: {
							'action': 'getgadgetprefs',
							'gadget': gadget,
							'format': 'json'
						},
						dataType: "json", // response type
						success: function( response ) {
							
							if ( typeof response.description != 'object' ||
								typeof response.values != 'object')
							{
								alert( mw.msg( 'gadgets-unexpected-error' ) );
								return;
							}
							
							//Create and show dialog
							
							var	prefsDescription = response.description,
								values = response.values,
								$dialogBody;
							
							var $form = $( prefsDescription ).formBuilder( {
								msgPrefix: "Gadget-" + gadget + "-",
								idPrefix: "gadget-" + gadget + "-preferences-",
								values: values,
								change: function() {
									//Hide current message, if any
									showMsg( null );
									
									var	savedValues = $dialogBody.data( 'savedValues' ),
										currentValues = $form.formBuilder( 'getValues' );

									if ( !deepEquals( currentValues, savedValues ) ) {
										$( '#mw-gadgets-prefsDialog-save' ).button( 'enable' );
									} else {
										$( '#mw-gadgets-prefsDialog-save' ).button( 'disable' );
									}
								}
							} );
							
							$form.submit( function() {
								return false; //prevent form submission
							} );
							
							$dialogBody = $( '<div/>' )
								.attr( 'id', 'mw-gadgets-prefsDialog' )
								.append( $form )
								.data( 'savedValues', values );
							
							//Add a div to show messages
							$( '<div>&nbsp;</div>' )
								.attr( 'id', 'mw-gadgets-prefsDialog-message' )
								.css( 'opacity', 0 )  //starts invisible
								.appendTo( $dialogBody );
							
							$dialogBody.dialog( {
								modal: true,
								width: 550,
								resizable: false,
								title: mw.msg( 'gadgets-configuration-of', gadget ),
								create: function() {
									//Remove styles to dialog buttons
									$( this ).dialog( 'widget' ).find( '.ui-button' )
										.removeClass().unbind( 'mouseover' ).unbind( 'mousedown' );
								},
								close: function() {
									$( this ).remove();
								},
								buttons: [
									//TODO: add a "Restore defaults" button
									{
										id: 'mw-gadgets-prefsDialog-save',
										text: mw.msg( 'gadgets-prefs-save' ),
										disabled: true,
										click: function() {
											var isValid = $form.formBuilder( 'validate' );
											if ( isValid ) {
												var currentValues = $form.formBuilder( 'getValues' );
												saveConfig( $( this ), gadget, currentValues );
											} else {
												showMsg( mw.msg( 'gadgets-save-invalid' ) );
											}
										}
									},
									{
										id: 'mw-gadgets-prefsDialog-close',
										text: mw.msg( 'gadgets-prefs-close' ),
										click: function() {
											$( this ).dialog( "close" );
										}
									}
								]
							} );
						},
						error: function( response ) {
							alert( mw.msg( 'gadgets-unexpected-error' ) );
						}
					} );

					return false; //prevent event propagation
				} );

			$span.append( "&nbsp;·&nbsp;", $link );
			$( input ).next().append( $span );

			//Toggle visibility on click to the input
			$( input ).click( function() {
				var visibility = $span.css( 'visibility' );
				$span.css( 'visibility', visibility == 'visible' ? 'hidden' : 'visible' );
			} );
		}
	} );
} )( jQuery, mediaWiki );

