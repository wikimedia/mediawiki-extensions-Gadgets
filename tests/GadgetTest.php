<?php
/**
 * @group Gadgets
 */

class GadgetsTest extends MediaWikiTestCase {
	private function create( $line ) {
		$g = Gadget::newFromDefinition( $line );
		$this->assertInstanceOf( 'Gadget', $g );

		return $g;
	}

	function testInvalidLines() {
		$this->assertFalse( Gadget::newFromDefinition( '' ) );
		$this->assertFalse( Gadget::newFromDefinition( '<foo|bar>' ) );
	}

	function testSimpleCases() {
		$g = $this->create( '* foo bar| foo.css|foo.js|foo.bar' );
		$this->assertEquals( 'foo_bar', $g->getName() );
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
		$g = $this->create( '*foo [ResourceLoader]|foo.js|foo.css' );
		$this->assertEquals( 'foo', $g->getName() );
		$this->assertTrue( $g->supportsResourceLoader() );
		$this->assertEquals( 0, count( $g->getLegacyScripts() ) );
	}

	function testDependencies() {
		$g = $this->create( '* foo[ResourceLoader|dependencies=jquery.ui]|bar.js' );
		$this->assertEquals( array( 'Gadget-bar.js' ), $g->getScripts() );
		$this->assertTrue( $g->supportsResourceLoader() );
		$this->assertEquals( array( 'jquery.ui' ), $g->getDependencies() );
	}

	function testPreferences() {
		$prefs = array();

		$gadgets = Gadget::fetchStructuredList( '* foo | foo.js
==keep-section1==
* bar| bar.js
==remove-section==
* baz [rights=embezzle] |baz.js
==keep-section2==
* quux [rights=read] | quux.js' );
		$this->assertGreaterThanOrEqual( 2, count( $gadgets ), "Gadget list parsed" );

		Gadget::injectDefinitionCache( $gadgets );
		$this->assertTrue( GadgetHooks::getPreferences( new User, $prefs ), 'GetPrefences hook should return true' );

		$options = $prefs['gadgets']['options'];
		$this->assertFalse( isset( $options['&lt;gadget-section-remove-section&gt;'] ), 'Must not show empty sections' );
		$this->assertTrue( isset( $options['&lt;gadget-section-keep-section1&gt;'] ) );
		$this->assertTrue( isset( $options['&lt;gadget-section-keep-section2&gt;'] ) );
	}
}
