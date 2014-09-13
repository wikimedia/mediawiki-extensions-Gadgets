<?php

/**
 * @group Gadgets
 * @group Extensions
 */
class GadgetHooksTest extends MediaWikiTestCase {

	/**
	 * @covers GadgetsHooks::onContentHandlerDefaultModelFor
	 * @dataProvider provideOnContentHandlerDefaultModelFor
	 */
	public function testOnContentHandlerDefaultModelFor( $text, $expected, $desc ) {
		$title = Title::newFromText( $text );
		$model = null;
		GadgetsHooks::onContentHandlerDefaultModelFor( $title, $model );
		$this->assertEquals( $expected, $model, $desc );
	}

	public static function provideOnContentHandlerDefaultModelFor() {
		return array(
			array( 'Gadget:Foo.js', 'GadgetJs', 'Gadget page with a .js extension' ),
			array( 'Gadget:Foo.css', 'GadgetCss', 'Gadget page with a .css extension' ),
			array( 'Gadget:Foo', null, 'Gadget page with no extension' ),
			array( 'Gadget:Foo.ext', null, 'Gadget page with a non-css/js extension'),
			array( 'Main Page', null, 'Mainspace page' ),
		);
	}
}