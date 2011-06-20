/*
 * JavaScript tweaks for Special:Preferences
 */
( function( $, mw ) {
	
	//"Save" button click handler
	function saveConfig( $dialog, gadget, config ) {
		var prefsJson = $.toJSON( config );
		
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
					alert( mw.msg( 'gadgets-save-success' ) );
					$dialog.dialog( 'close' );
				} else {
					alert( mw.msg( 'gadgets-save-failed' ) );
				}
			},
			error: function( response ) {
				alert( mw.msg( 'gadgets-save-failed' ) );
			}
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
							
							if ( typeof response.getgadgetprefs != 'object' ) {
								alert( mw.msg( 'gadgets-unexpected-error' ) )
								return;
							}
							
							//Create and show dialog
							
							var prefs = response.getgadgetprefs;
							
							var dialogBody = $( prefs ).formBuilder();
							
							$( dialogBody ).submit( function() {
								return false; //prevent form submission
							} );
							
							$( dialogBody ).attr( 'id', 'mw-gadgets-prefsDialog' );
							
							$( dialogBody ).dialog( {
								modal: true,
								width: 'auto',
								resizable: false,
								title: mw.msg( 'gadgets-configuration-of', gadget ),
								close: function() {
									$( this ).dialog( 'destroy' ).empty(); //completely destroy on close
								},
								buttons: [
									//TODO: add a "Restore defaults" button
									{
										text: mw.msg( 'gadgets-prefs-save' ),
										click: function() {
											var isValid = $( dialogBody ).formBuilder( 'validate' );
											
											if ( isValid ) {
												var values = $( dialogBody ).formBuilder( 'getValues' );
												saveConfig( $( this ), gadget, values );
											}
										}
									},
									{
										text: mw.msg( 'gadgets-prefs-cancel' ),
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
				$span.fadeToggle( 'fast' );
			} );
		}
	} );
} )( jQuery, mediaWiki );

