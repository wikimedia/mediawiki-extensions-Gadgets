<?php

class GadgetCssContentHandlerTest extends MediaWikiTestCase {
	/**
	 * @covers GadgetCssContentHandler::__construct
	 */
	public function testBasicAttrs() {
		$ch = ContentHandler::getForModelID( 'GadgetCss' );
		$this->assertInstanceOf( 'GadgetCssContenthandler', $ch );

		$this->assertTrue( $ch->isSupportedFormat( CONTENT_FORMAT_CSS ) );
	}

	/**
	 * @covers GadgetCssContentHandler::canBeUsedOn
	 * @dataProvider provideCanBeUsedOn
	 */
	public function testCanBeUsedOn( $text, $expected ) {
		$ch = new GadgetCssContentHandler();
		$title = Title::newFromText( $text );
		$this->assertEquals( $expected, $ch->canBeUsedOn( $title ) );
	}

	public function provideCanBeUsedOn() {
		return array(
			array( 'Gadget:FooBar.css', true ),
			array( 'Gadget:FooBar.js', false ),
			array( 'Main Page', false ),
			array( 'MediaWiki:Common.js', false ),
		);
	}

	/**
	 * @covers GadgetCssContentHandler::getContentClass
	 */
	public function testGetContentClass() {
		$ch = new GadgetCssContentHandler();
		$content = $ch->makeEmptyContent();
		$this->assertInstanceOf( 'GadgetCssContent', $content );
	}
}