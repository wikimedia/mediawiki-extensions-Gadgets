<?php
$namespaceNames = array();

// For wikis without Gadgets installed.
if ( !defined( 'NS_GADGET' ) ) {
	define( 'NS_GADGET', 2300 );
	define( 'NS_GADGET_TALK', 2301 );
}

$namespaceNames['en'] = array(
	NS_GADGET => 'Gadget',
	NS_GADGET_TALK => 'Gadget_talk',
);
