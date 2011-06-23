<?php

/**
 * @group Gadgets
 */
class GadgetsTest extends PHPUnit_Framework_TestCase {

	private function create( $line ) {
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
		$this->assertEquals(0, count( $g->getLegacyScripts() ) );
	}

	function testDependencies() {
		$g = $this->create( '* foo[ResourceLoader|dependencies=jquery.ui]|bar.js' );
		$this->assertEquals( array( 'Gadget-bar.js' ), $g->getScripts() );
		$this->assertTrue( $g->supportsResourceLoader() );
		$this->assertEquals( array( 'jquery.ui' ), $g->getDependencies() );
	}

	function testOptions() {
		global $wgUser;

		// This test makes call to the parser which requires valids Outputpage
		// and Title objects. Set them up there, they will be released at the
		// end of the test.
		global $wgOut, $wgTitle;
		$old_wgOut = $wgOut;
		$old_wgTitle = $wgTitle;
		$wgTitle = Title::newFromText( 'Parser test for Gadgets extension' );

		// Proceed with test setup:
		$prefs = array();
		$context = new RequestContext();
		$wgOut = $context->getOutput();
		$wgOut->setTitle( Title::newFromText( 'test' ) );

		Gadget::loadStructuredList( '* foo | foo.js
==keep-section1==
* bar| bar.js
==remove-section==
* baz [rights=embezzle] |baz.js
==keep-section2==
* quux [rights=read] | quux.js' );
		$this->assertTrue( GadgetHooks::getPreferences( $wgUser, $prefs ), 'GetPrefences hook should return true' );

		$options = $prefs['gadgets']['options'];
		$this->assertFalse( isset( $options['&lt;gadget-section-remove-section&gt;'] ), 'Must not show empty sections' );
		$this->assertTrue( isset( $options['&lt;gadget-section-keep-section1&gt;'] ) );
		$this->assertTrue( isset( $options['&lt;gadget-section-keep-section2&gt;'] ) );

		// Restore globals
		$wgOut = $old_wgOut;
		$wgTitle = $old_wgTitle;
	}

	//Test preferences descriptions validator (generic)
	function testPrefsDescriptions() {
		$this->assertFalse( Gadget::isPrefsDescriptionValid( null ) );
		$this->assertFalse( Gadget::isPrefsDescriptionValid( array() ) );
		$this->assertFalse( Gadget::isPrefsDescriptionValid( array( 'fields' => array() ) ) );

		//Test with wrong type
		$this->assertFalse( Gadget::isPrefsDescriptionValid( array(
			'fields' => array(
				'testUnexisting' => array(
					'type' => 'unexistingtype',
					'label' => 'foo',
					'default' => 'bar'
				)
			)
		) ) );

		//Test with wrong preference name
		$this->assertFalse( Gadget::isPrefsDescriptionValid( array(
			'fields' => array(
				'testWrongN@me' => array(
					'type' => 'boolean',
					'label' => 'foo',
					'default' => true
				)
			)
		) ) );

		//Test with an unexisting field parameter
		$this->assertFalse( Gadget::isPrefsDescriptionValid( array(
			'fields' => array(
				'testBoolean' => array(
					'type' => 'boolean',
					'label' => 'foo',
					'default' => true,
					'unexistingParamThatMustFail' => 'foo'
				)
			)
		) ) );
	}

	//Tests for 'boolean' type preferences
	function testPrefsDescriptionsBoolean() {
		$correct = array(
			'fields' => array(
				'testBoolean' => array(
					'type' => 'boolean',
					'label' => 'some label',
					'default' => true
				)
			)
		);

		$this->assertTrue( Gadget::isPrefsDescriptionValid( $correct ) );

		$correct2 = array(
			'fields' => array(
				'testBoolean' => array(
					'type' => 'boolean',
					'label' => 'some label',
					'default' => false
				)
			)
		);

		$this->assertTrue( Gadget::isPrefsDescriptionValid( $correct2 ) );

		//Tests with wrong default values
		$wrong = $correct;
		foreach ( array( 0, 1, '', 'false', 'true', null, array() ) as $def ) {
			$wrong['fields']['testBoolean']['default'] = $def;
			$this->assertFalse( Gadget::isPrefsDescriptionValid( $wrong ) );
		}
	}

	//Tests for 'string' type preferences
	function testPrefsDescriptionsString() {
		$correct = array(
			'fields' => array(
				'testString' => array(
					'type' => 'string',
					'label' => 'some label',
					'minlength' => 6,
					'maxlength' => 10,
					'required' => false,
					'default' => 'default'
				)
			)
		);

		$this->assertTrue( Gadget::isPrefsDescriptionValid( $correct ) );

		//Tests with wrong default values
		$wrong = $correct;
		foreach ( array( null, true, false, 0, 1, array(), 'short', 'veryverylongstring' ) as $def ) {
			$wrong['fields']['testString']['default'] = $def;
			$this->assertFalse( Gadget::isPrefsDescriptionValid( $wrong ) );
		}

		//Tests with correct default values (when required is false)
		$correct2 = $correct;
		foreach ( array( '', '6chars', '1234567890' ) as $def ) {
			$correct2['fields']['testString']['default'] = $def;
			$this->assertTrue( Gadget::isPrefsDescriptionValid( $correct2 ) );
		}

		//Test with empty default when "required" is true
		$wrong = $correct;
		$wrong['fields']['testString']['required'] = true;
		$wrong['fields']['testString']['default'] = '';
		$this->assertFalse( Gadget::isPrefsDescriptionValid( $wrong ) );
	}

	//Tests for 'number' type preferences
	function testPrefsDescriptionsNumber() {
		$correctFloat = array(
			'fields' => array(
				'testNumber' => array(
					'type' => 'number',
					'label' => 'some label',
					'min' => -15,
					'max' => 36,
					'required' => true,
					'default' => 3.14
				)
			)
		);

		$correctInt = array(
			'fields' => array(
				'testNumber' => array(
					'type' => 'number',
					'label' => 'some label',
					'min' => -15,
					'max' => 36,
					'integer' => true,
					'required' => true,
					'default' => 12
				)
			)
		);

		$this->assertTrue( Gadget::isPrefsDescriptionValid( $correctFloat ) );
		$this->assertTrue( Gadget::isPrefsDescriptionValid( $correctInt ) );

		//Tests with wrong default values (with 'required' = true)
		$wrongFloat = $correctFloat;
		foreach ( array( '', false, true, null, array(), -100, +100 ) as $def ) {
			$wrongFloat['fields']['testNumber']['default'] = $def;
			$this->assertFalse( Gadget::isPrefsDescriptionValid( $wrongFloat ) );
		}

		$wrongInt = $correctInt;
		foreach ( array( '', false, true, null, array(), -100, +100, 2.7182818 ) as $def ) {
			$wrongInt['fields']['testNumber']['default'] = $def;
			$this->assertFalse( Gadget::isPrefsDescriptionValid( $wrongInt ) );
		}

		//If required=false, default=null must be accepted, too
		foreach ( array( $correctFloat, $correctInt ) as $correct ) {
			$correct['fields']['testNumber']['required'] = false;
			$correct['fields']['testNumber']['default'] = null;
			$this->assertTrue( Gadget::isPrefsDescriptionValid( $correct ) );
		}
	}

	//Tests for 'select' type preferences
	function testPrefsDescriptionsSelect() {
		$correct = array(
			'fields' => array(
				'testSelect' => array(
					'type' => 'select',
					'label' => 'some label',
					'default' => 3,
					'options' => array(
						'opt1' => null,
						'opt2' => true,
						'opt3' => 3,
						'opt4' => 'test'
					)
				)
			)
		);


		//Tests with correct default values
		$correct2 = $correct;
		foreach ( array( null, true, 3, 'test' ) as $def ) {
			$correct2['fields']['testSelect']['default'] = $def;
			$this->assertTrue( Gadget::isPrefsDescriptionValid( $correct2 ) );
		}

		//Tests with wrong default values
		$wrong = $correct;
		foreach ( array( '', 'true', 'null', false, array(), 0, 1, 3.0001 ) as $def ) {
			$wrong['fields']['testSelect']['default'] = $def;
			$this->assertFalse( Gadget::isPrefsDescriptionValid( $wrong ) );
		}
	}
}
