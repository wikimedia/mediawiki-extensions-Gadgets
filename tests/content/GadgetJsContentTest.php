<?php

class GadgetJsContentTest extends GadgetContentTestCase {

	/**
	 * @covers GadgetJsContent::__construct
	 * @dataProvider provideConstructor
	 */
	public function testConstructor( $text, $desc ) {
		$content = new GadgetJsContent( $text );
		$this->assertInstanceOf( 'GadgetJsContent', $content, $desc );
	}

	public static function provideConstructor() {
		return array(
			array( 'mw.gadget.test( "foo" );', 'valid JS' ),
			array( 'Totally not valid JS', 'invalid JS' ),
			array( '', 'Empty content' ),
		);
	}

	/**
	 * @covers GadgetJsContent::getDeletionUpdates
	 */
	public function testGetDeletionUpdates() {
		$content = new GadgetJsContent( '// blah' );
		$updates = $content->getDeletionUpdates( $this->getMockWikiPage(), $this->getMock( 'ParserOutput' ) );
		$found = false;
		foreach ( $updates as $update ) {
			if ( $update instanceof GadgetScriptDeletionUpdate ) {
				$found = true;
			}
		}

		$this->assertTrue( $found, 'GadgetJsContent::getDeletionUpdates returned a GadgetDefinitionDeletionUpdate' );
	}

	/**
	 * @covers GadgetJsContent::getSecondaryDataUpdates
	 */
	public function testGetSecondaryDataUpdates() {
		$oldContent = new GadgetJsContent( '// no JS :(' );
		$newContent = new GadgetJsContent( 'alert("rar!");' );
		$parserOutput = $this->getMock( 'ParserOutput' );
		$parserOutput->expects( $this->any() )->method( 'getSecondaryDataUpdates' )->will( $this->returnValue( array() ) );
		$found = false;
		$updates = $newContent->getSecondaryDataUpdates( Title::newMainPage(), $oldContent, true, $parserOutput );
		foreach ( $updates as $update ) {
			if ( $update instanceof GadgetScriptSecondaryDataUpdate ) {
				$found = true;
			}
		}

		$this->assertTrue( $found, 'GadgetJsContent::getSecondaryDataUpdates returned a GadgetDefinitionSecondaryDataUpdate' );
	}

}
