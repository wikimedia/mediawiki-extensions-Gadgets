/*
 * JavaScript tweaks for Special:Preferences
 */
( function( $, mw ) {
  $( '#mw-htmlform-gadgets input[name="wpgadgets[]"]' ).each( function( idx, input ) {
    var id = input.id;
    var gadget = id.substr( "mw-input-wpgadgets-".length );

    if ( $.inArray( gadget, mw.gadgets.configurableGadgets ) != -1 ) {
      $span = $( '<span></span>' );

      if ( !$( input ).is( ':checked' ) ) {
        $span.hide();
      }

      $link = $( '<a></a>' )
              .text( "Configure" ) //TODO: use a message instead
              .click( function() {
                var post_data = 'action=ajax&rs=GadgetsAjax::getUI' +
                                '&rsargs[]=gadget|' + encodeURIComponent( gadget );
                // Send POST request via AJAX!
                $.ajax( {
                  url     : mw.config.get( 'wgScriptPath' ) + '/index.php',
                  type    : "POST",
                  data    : post_data,
                  dataType: "html", // response type
                  success : function( response ) {
                    //Show dialog
                    $( response ).dialog( {
                      modal: true,
                      width: 'auto',
                      resizable: false,
                      title: 'Configuration of ' + gadget, //TODO: use messages
                      close: function() {
                        $(this).dialog('destroy').empty(); //completely destroy on close
                      },
                      buttons: {
                        //TODO: add "Restore defaults" button
                        "Save": function() {
                          //TODO
                          alert( "I should save changes now, but I don't know how :'(..." );
                        },
                        "Cancel": function() {
                          $( this ).dialog( "close" );
                        }
                      }
                    } );
                  },
                  error   : function( response ) {
                    //TODO
                    alert( 'Something wrong happened' );
                  },
                } );

                return false; //prevent event propagation
              } );

      $span.append( "&nbsp;·&nbsp;", $link );
      $( input ).next().append( $span );

      //Toggle visibility on click to the input
      $( input ).click( function() {
        $span.toggle( 'fast' );
      } );
    }
  } );
} )( jQuery, mediaWiki );
