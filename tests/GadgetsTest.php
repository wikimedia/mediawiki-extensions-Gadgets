<?php

/**
 * @group Gadgets
 */
class GadgetsTest extends PHPUnit_Framework_TestCase {

	private function create( $line ) {
		// TODO fails now
		$g = Gadget::newFromDefinition( $line );
		// assertInstanceOf() is available since PHPUnit 3.5
		$this->assertEquals( 'Gadget', get_class( $g ) );
		return $g;
	}

	function testInvalidLines() {
		$this->assertFalse( Gadget::newFromDefinition( '' ) );
		$this->assertFalse( Gadget::newFromDefinition( '<foo|bar>' ) );
	}

	function testSimpleCases() {
		$g = $this->create( '* foo bar| foo.css|foo.js|foo.bar' ); //FIXME
		$this->assertEquals( 'foo_bar', $g->getId() );
		$this->assertEquals( 'ext.gadget.foo_bar', $g->getModuleName() );
		$this->assertEquals( array( 'Gadget-foo.js' ), $g->getScripts() );
		$this->assertEquals( array( 'Gadget-foo.css' ), $g->getStyles() );
		$this->assertEquals( array( 'Gadget-foo.js', 'Gadget-foo.css' ),
			$g->getScriptsAndStyles() );
		$this->assertEquals( array( 'Gadget-foo.js' ), $g->getLegacyScripts() );
		$this->assertFalse( $g->supportsResourceLoader() );
		$this->assertTrue( $g->hasModule() );
	}

	function testRLtag() {
		$g = $this->create( '*foo [ResourceLoader]|foo.js|foo.css' ); //FIXME
		$this->assertEquals( 'foo', $g->getId() );
		$this->assertTrue( $g->supportsResourceLoader() );
		$this->assertEquals(0, count( $g->getLegacyScripts() ) );
	}

	function testDependencies() {
		$g = $this->create( '* foo[ResourceLoader|dependencies=jquery.ui]|bar.js' ); //FIXME
		$this->assertEquals( array( 'Gadget-bar.js' ), $g->getScripts() );
		$this->assertTrue( $g->supportsResourceLoader() );
		$this->assertEquals( array( 'jquery.ui' ), $g->getDependencies() );
	}
}
