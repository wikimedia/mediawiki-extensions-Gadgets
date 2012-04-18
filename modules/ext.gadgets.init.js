/**
 * Initialize the mw.gadgets object
 */
( function ( mw ) {

	mw.gadgets = {
		conf: mw.config.get( 'gadgetsConf' )
	};

}( mediaWiki ) );
