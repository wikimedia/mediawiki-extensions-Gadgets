/*
 * JavaScript tweaks for Special:Preferences
 */
( function( $, mw ) {
	
	//"Save" button click handler
	function saveConfig( $dialog, gadget, config ) {
		var json = $.toJSON( config );
		
		var postData = 'action=ajax&rs=GadgetsAjax::setPreferences' +
				'&rsargs[]=gadget|' + encodeURIComponent( gadget ) +
				'&rsargs[]=json|' + encodeURIComponent( json );

		$.ajax( {
			url: mw.config.get( 'wgScriptPath' ) + '/index.php',
			type: "POST",
			data: postData,
			dataType: "json",
			success: function( response ) {
				if ( response === true ) {
					alert( 'Configuration saved successfully' );
					$dialog.dialog( 'close' );
				} else {
					//TODO
					alert( 'Something wrong happened' );
				}
			},
			error: function( response ) {
				//TODO
				alert( 'Something wrong happened' );
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
				.text( "Configure" ) //TODO: use a message instead
				.click( function() {
					var postData = 'action=ajax&rs=GadgetsAjax::getPreferences' +
							'&rsargs[]=gadget|' + encodeURIComponent( gadget );
							
					$.ajax( {
						url: mw.config.get( 'wgScriptPath' ) + '/index.php',
						type: "POST",
						data: postData,
						dataType: "json", // response type
						success: function( response ) {
							//TODO: malformed response?
							
							//Create and show dialog
							
							var dialogBody = $( response ).formBuilder();
							
							//window.validator = $( dialogBody ).validate();
							
							$( dialogBody ).dialog( {
								modal: true,
								width: 'auto',
								resizable: false,
								title: 'Configuration of ' + gadget, //TODO: use messages
								close: function() {
									$( this ).dialog( 'destroy' ).empty(); //completely destroy on close
								},
								buttons: {
									//TODO: add a "Restore defaults" button
									
									"Save": function() {
										var isValid = $( dialogBody ).formBuilder( 'validate' );
										
										if ( isValid ) {
											var values = $( dialogBody ).formBuilder( 'getValues' );
											saveConfig( $( this ), gadget, values );
										}
									},
									"Cancel": function() {
										$( this ).dialog( "close" );
									}
								}
							} );
						},
						error: function( response ) {
							//TODO
							alert( 'Something wrong happened' );
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

