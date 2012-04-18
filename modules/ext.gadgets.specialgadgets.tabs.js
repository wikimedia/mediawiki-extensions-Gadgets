/**
 * JavaScript for Special:Gadgets
 *
 * @author Timo Tijhof
 */

jQuery( document ).ready( function ( $ ) {

	var ga = mw.gadgets;

	if ( ga.conf.userIsAllowed['gadgets-definition-create'] ) {
		var createTab = mw.util.addPortletLink(
			// Not all skins use the new separated tabs yet,
			// Fall back to the general 'p-cactions'.
			$( '#p-views' ).length ? 'p-views' : 'p-cactions',
			'#',
			mw.msg( 'gadgets-gadget-create' ),
			'ca-create', // Use whatever core has for pages ? Or use gadget-create ?
			mw.msg( 'gadgets-gadget-create-tooltip' ),
			'e' // Same as core for ca-edit/ca-create
		);
		$( createTab ).click( function ( e ) {
			e.preventDefault();
			ga.ui.startGadgetManager( 'create' );
		} );
	}

});
