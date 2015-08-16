<?php
$namespaceNames = array();

// For wikis without Gadgets installed.
if ( !defined( 'NS_GADGET' ) ) {
	define( 'NS_GADGET', 2300 );
	define( 'NS_GADGET_TALK', 2301 );
	define( 'NS_GADGET_DEFINITION', 2302 );
	define( 'NS_GADGET_DEFINITION_TALK', 2303 );
}

$namespaceNames['en'] = array(
	NS_GADGET => 'Gadget',
	NS_GADGET_TALK => 'Gadget_talk',
	NS_GADGET_DEFINITION => 'Gadget_definition',
	NS_GADGET_DEFINITION_TALK => 'Gadget_definition_talk',
);

$namespaceNames['fa'] = array(
	NS_GADGET => 'ابزار',
	NS_GADGET_TALK => 'بحث_ابزار',
	NS_GADGET_DEFINITION => 'توضیحات_ابزار',
	NS_GADGET_DEFINITION_TALK => 'بحث_توضیحات_ابزار',
);

$namespaceNames['he'] = array(
	NS_GADGET => 'גאדג\'ט',
	NS_GADGET_TALK => 'שיחת_גאדג\'ט',
	NS_GADGET_DEFINITION => 'הגדרת_גאדג\'ט',
	NS_GADGET_DEFINITION_TALK => 'שיחת_הגדרת_גאדג\'ט',
);

$namespaceNames['vi'] = array(
	NS_GADGET => 'Tiện_ích',
	NS_GADGET_TALK => 'Thảo_luận_Tiện_ích',
	NS_GADGET_DEFINITION => 'Định_nghĩa_tiện_ích',
	NS_GADGET_DEFINITION_TALK => 'Thảo_luận_Định_nghĩa_tiện_ích',
);
