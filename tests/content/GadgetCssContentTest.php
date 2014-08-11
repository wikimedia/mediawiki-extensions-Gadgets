<?php

class GadgetCssContentTest extends GadgetContentTestCase {

	/**
	 * @covers GadgetCssContent::__construct
	 * @dataProvider provideConstructor
	 */
	public function testConstructor( $text, $desc ) {
		$content = new GadgetCssContent( $text );
		$this->assertInstanceOf( 'GadgetCssContent', $content, $desc );
	}

	public static function provideConstructor() {
		return array(
			array( '.mw-gadget-test { content: "style"; }', 'valid CSS' ),
			array( 'Totally not valid CSS', 'invalid CSS' ),
			array( '', 'Empty content' ),
		);
	}

	/**
	 * @covers GadgetCssContent::getDeletionUpdates
	 */
	public function testGetDeletionUpdates() {
		$content = new GadgetCssContent( '/* blah */' );
		$updates = $content->getDeletionUpdates( $this->getMockWikiPage(), $this->getMock( 'ParserOutput' ) );
		$found = false;
		foreach ( $updates as $update ) {
			if ( $update instanceof GadgetScriptDeletionUpdate ) {
				$found = true;
			}
		}

		$this->assertTrue( $found, 'GadgetCssContent::getDeletionUpdates returned a GadgetDefinitionDeletionUpdate' );
	}

	/**
	 * @covers GadgetCssContent::getSecondaryDataUpdates
	 */
	public function testGetSecondaryDataUpdates() {
		$oldContent = new GadgetCssContent( '/* no CSS :( */' );
		$newContent = new GadgetCssContent( 'body { background-color: red; }' );
		$parserOutput = $this->getMock( 'ParserOutput' );
		$parserOutput->expects( $this->any() )->method( 'getSecondaryDataUpdates' )->will( $this->returnValue( array() ) );
		$found = false;
		$updates = $newContent->getSecondaryDataUpdates( Title::newMainPage(), $oldContent, true, $parserOutput );
		foreach ( $updates as $update ) {
			if ( $update instanceof GadgetScriptSecondaryDataUpdate ) {
				$found = true;
			}
		}

		$this->assertTrue( $found, 'GadgetCssContent::getSecondaryDataUpdates returned a GadgetDefinitionSecondaryDataUpdate' );
	}

}
