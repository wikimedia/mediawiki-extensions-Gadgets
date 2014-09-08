<?php

class GadgetDefinitionContentTest extends MediaWikiLangTestCase {

	/**
	 * @covers GadgetDefinitionContent::__construct
	 * @dataProvider provideConstructor
	 */
	public function testConstructor( $text, $desc ) {
		$content = new GadgetDefinitionContent( $text );
		$this->assertInstanceOf( 'GadgetDefinitionContent', $content, $desc );
	}

	public static function provideConstructor() {
		return array(
			array( '{}', 'Valid JSON' ),
			array( 'totally not valid JSON', 'Invalid JSON' ),
			array( '', 'Empty string' )
		);
	}

	/**
	 * @covers GadgetDefinitionContent::isValid
	 * @covers Gadget::validatePropertiesArray
	 * @dataProvider provideIsValid
	 */
	public function testIsValid( $text, $expected, $desc ) {
		$content = new GadgetDefinitionContent( $text );
		$this->assertEquals( $expected, $content->isValid(), $desc );
	}

	/**
	 * @todo add tests for module.* properties
	 */
	public static function provideIsValid() {
		return array(
			array( '', false, 'Empty string' ),
			array( '{}', true, 'Empty JSON object' ),
			array( 'some random text', false, 'Plain string' ),
			array( '{"settings": {}}', true, 'settings set to an object' ),
			array( '{"settings": []}', true, 'settings set to an array' ),
			array( '{"settings": {"rights": ["block", "delete"]}}', true, 'setting.rights set to an array' ),
			array( '{"settings": {"rights": "block"}}', false, 'settings.rights set to a string' ),
			array( '{"settings": {"rights": [["block", "delete"]]}}', false, 'settings.rights set to a nested array' ),
			array( '{"settings": {"default": true}}', true, 'settings.default set to true' ),
			array( '{"settings": {"default": false}}', true, 'settings.default set to false' ),
			array( '{"settings": {"default": {}}}', false, 'settings.default set to an object' ),
			array( '{"settings": {"hidden": true}}', true, 'settings.hidden set to true' ),
			array( '{"settings": {"hidden": false}}', true, 'settings.hidden set to false' ),
			array( '{"settings": {"hidden": {}}}', false, 'settings.hidden set to an object' ),
			array( '{"settings": {"shared": true}}', true, 'settings.shared set to true' ),
			array( '{"settings": {"shared": false}}', true, 'settings.shared set to false' ),
			array( '{"settings": {"shared": {}}}', false, 'settings.shared set to an object' ),
			array( '{"settings": {"skins": true}}', true, 'settings.skins set to true' ),
			array( '{"settings": {"skins": false}}', false, 'settings.skins set to false' ),
			array( '{"settings": {"skins": ["vector", "monobook"]}}', true, 'settings.skins set to an array' ),
			array( '{"settings": {"skins": "string"}}', false, 'settings.skins set to a string' ),
			array( '{"settings": {"category": "Some string"}}', true, 'settings.category set to a string' ),
			array( '{"settings": {"category": []}}', false, 'settings.category set to an array' ),
		);
	}

	/**
	 * @covers GadgetDefinitionContent::preSaveTransform
	 * @dataProvider providePreSaveTransform
	 */
	public function testPreSaveTransform( $input, $expected, $desc ) {
		$title = Title::newMainPage();
		$user = $this->getMock( 'User' );
		$parserOptions = $this->getMock( 'ParserOptions' );
		$content = new GadgetDefinitionContent( $input );
		$expectedContent = new GadgetDefinitionContent( $expected );
		// Use getNativeData to compare the raw text
		$this->assertEquals(
			$expectedContent->getNativeData(),
			$content->preSaveTransform( $title, $user, $parserOptions )->getNativeData(),
			$desc
		);
	}

	public static function providePreSaveTransform() {
		return array(
			array( '{}', '{
    "settings": {
        "rights": [],
        "default": false,
        "hidden": false,
        "shared": false,
        "skins": true,
        "category": ""
    },
    "module": {
        "scripts": [],
        "styles": [],
        "dependencies": [],
        "messages": [],
        "position": "bottom"
    }
}', 'Empty JSON object' ),
			array( '{"settings": {"hidden": true}}', '{
    "settings": {
        "hidden": true,
        "rights": [],
        "default": false,
        "shared": false,
        "skins": true,
        "category": ""
    },
    "module": {
        "scripts": [],
        "styles": [],
        "dependencies": [],
        "messages": [],
        "position": "bottom"
    }
}', 'settings.hidden set to true' ),
			array( '{"settings": {"skins": ["vector", "monobook"]}}', '{
    "settings": {
        "skins": [
            "monobook",
            "vector"
        ],
        "rights": [],
        "default": false,
        "hidden": false,
        "shared": false,
        "category": ""
    },
    "module": {
        "scripts": [],
        "styles": [],
        "dependencies": [],
        "messages": [],
        "position": "bottom"
    }
}', 'settings.skins set to [vector, monobook]' ),
		);
	}

	private function getMockWikiPage() {
		$page = $this->getMockBuilder( 'WikiPage' )->disableOriginalConstructor()
			->getMock();
		$page->expects( $this->any() )->method( 'exists' )->will( $this->returnValue( true ) );
		$page->expects( $this->any() )->method( 'getTitle' )->will( $this->returnValue( Title::newMainPage() ) );
		return $page;
	}

	public function testGetDeletionUpdates() {
		$content = new GadgetDefinitionContent( '{}' );
		$updates = $content->getDeletionUpdates( $this->getMockWikiPage(), $this->getMock( 'ParserOutput' ) );
		$found = false;
		foreach ( $updates as $update ) {
			if ( $update instanceof GadgetDefinitionDeletionUpdate ) {
				$found = true;
			}
		}

		$this->assertTrue( $found, 'GadgetDefinitionContent::getDeletionUpdates returned a GadgetDefinitionDeletionUpdate' );
	}

	public function testGetSecondaryDataUpdates() {
		$oldContent = new GadgetDefinitionContent( '{}' );
		$newContent = new GadgetDefinitionContent( '{"settings": {"skins": ["vector", "monobook"]}}' );
		$parserOutput = $this->getMock( 'ParserOutput' );
		$parserOutput->expects( $this->any() )->method( 'getSecondaryDataUpdates' )->will( $this->returnValue( array() ) );
		$found = false;
		$updates = $newContent->getSecondaryDataUpdates( Title::newMainPage(), $oldContent, true, $parserOutput );
		foreach ( $updates as $update ) {
			if ( $update instanceof GadgetDefinitionSecondaryDataUpdate ) {
				$found = true;
			}
		}

		$this->assertTrue( $found, 'GadgetDefinitionContent::getSecondaryDataUpdates returned a GadgetDefinitionSecondaryDataUpdate' );
	}
}