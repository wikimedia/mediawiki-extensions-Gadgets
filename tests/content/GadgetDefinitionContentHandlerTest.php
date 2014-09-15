<?php

class GadgetDefinitionContentHandlerTest extends MediaWikiTestCase {
	/**
	 * @covers GadgetDefinitionContentHandler::__construct
	 */
	public function testBasicAttrs() {
		$ch = ContentHandler::getForModelID( 'GadgetDefinition' );
		$this->assertInstanceOf( 'GadgetDefinitionContenthandler', $ch );

		$this->assertTrue( $ch->isSupportedFormat( CONTENT_FORMAT_JSON ) );
	}

	/**
	 * @covers GadgetDefinitionContentHandler::canBeUsedOn
	 * @dataProvider provideCanBeUsedOn
	 */
	public function testCanBeUsedOn( $text, $expected ) {
		$ch = new GadgetDefinitionContentHandler();
		$title = Title::newFromText( $text );
		$this->assertEquals( $expected, $ch->canBeUsedOn( $title ) );
	}

	public function provideCanBeUsedOn() {
		return array(
			array( 'Gadget definition:Foo', true ),
			array( 'Gadget:FooBar.css', false ),
			array( 'Gadget:FooBar.js', false ),
			array( 'Main Page', false ),
			array( 'MediaWiki:Common.js', false ),
		);
	}

	/**
	 * @covers GadgetDefinitionContentHandler::getContentClass
	 */
	public function testGetContentClass() {
		$ch = new GadgetDefinitionContentHandler();
		$content = $ch->makeEmptyContent();
		$this->assertInstanceOf( 'GadgetDefinitionContent', $content );
	}
}