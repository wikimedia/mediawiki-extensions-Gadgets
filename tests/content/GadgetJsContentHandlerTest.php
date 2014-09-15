<?php

class GadgetJsContentHandlerTest extends MediaWikiTestCase {
	/**
	 * @covers GadgetJsContentHandler::__construct
	 */
	public function testBasicAttrs() {
		$ch = ContentHandler::getForModelID( 'GadgetJs' );
		$this->assertInstanceOf( 'GadgetJsContenthandler', $ch );

		$this->assertTrue( $ch->isSupportedFormat( CONTENT_FORMAT_JAVASCRIPT ) );
	}

	/**
	 * @covers GadgetJsContentHandler::canBeUsedOn
	 * @dataProvider provideCanBeUsedOn
	 */
	public function testCanBeUsedOn( $text, $expected ) {
		$ch = new GadgetJsContentHandler();
		$title = Title::newFromText( $text );
		$this->assertEquals( $expected, $ch->canBeUsedOn( $title ) );
	}

	public function provideCanBeUsedOn() {
		return array(
			array( 'Gadget:FooBar.css', false ),
			array( 'Gadget:FooBar.js', true ),
			array( 'Main Page', false ),
			array( 'MediaWiki:Common.js', false ),
		);
	}

	/**
	 * @covers GadgetJsContentHandler::getContentClass
	 */
	public function testGetContentClass() {
		$ch = new GadgetJsContentHandler();
		$content = $ch->makeEmptyContent();
		$this->assertInstanceOf( 'GadgetJsContent', $content );
	}
}