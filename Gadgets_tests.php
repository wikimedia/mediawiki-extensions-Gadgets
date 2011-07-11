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
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( null ) );
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( array() ) );
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( array( 'fields' => array() ) ) );

		//Test with stdClass instead if array
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( (object)array(
			'fields' => array(
				array(
					'name' => 'testBoolean',
					'type' => 'boolean',
					'label' => 'foo',
					'default' => 'bar'
				)
			)
		) ) );

		//Test with wrong type
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( array(
			'fields' => array(
				array(
					'name' => 'testUnexisting',
					'type' => 'unexistingtype',
					'label' => 'foo',
					'default' => 'bar'
				)
			)
		) ) );

		//Test with missing name
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( array(
			'fields' => array(
				array(
					'type' => 'boolean',
					'label' => 'foo',
					'default' => true
				)
			)
		) ) );

		//Test with wrong preference name
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( array(
			'fields' => array(
				array(
					'name' => 'testWrongN@me',
					'type' => 'boolean',
					'label' => 'foo',
					'default' => true
				)
			)
		) ) );

		//Test with two fields with the same name
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( array(
			'fields' => array(
				array(
					'name' => 'testBoolean',
					'type' => 'boolean',
					'label' => 'foo',
					'default' => true
				),
				array(
					'name' => 'testBoolean',
					'type' => 'string',
					'label' => 'foo',
					'default' => 'bar'
				)
			)
		) ) );

		//Test with fields encoded as associative array instead of regular array
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( array(
			'fields' => array(
				'testBoolean' => array(
					'name' => 'testBoolean',
					'type' => 'string',
					'label' => 'foo',
					'default' => 'bar'
				)
			)
		) ) );

		//Test with too long preference name (41 characters)
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( array(
			'fields' => array(
				array(
					'name' => 'aPreferenceNameExceedingTheLimitOf40Chars',
					'type' => 'boolean',
					'label' => 'foo',
					'default' => true
				)
			)
		) ) );

		//This must pass, instead (40 characters is fine)
		$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( array(
			'fields' => array(
				array(
					'name' => 'otherPreferenceNameThatS40CharactersLong',
					'type' => 'boolean',
					'label' => 'foo',
					'default' => true
				)
			)
		) ) );


		//Test with an unexisting field parameter
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( array(
			'fields' => array(
				array(
					'name' => 'testBoolean',
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
				array(
					'name' => 'testBoolean',
					'type' => 'boolean',
					'label' => 'some label',
					'default' => true
				)
			)
		);

		$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct ) );

		$correct2 = array(
			'fields' => array(
				array(
					'name' => 'testBoolean',
					'type' => 'boolean',
					'label' => 'some label',
					'default' => false
				)
			)
		);

		$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct2 ) );

		//Tests with wrong default values
		$wrong = $correct;
		foreach ( array( 0, 1, '', 'false', 'true', null, array() ) as $def ) {
			$wrong['fields'][0]['default'] = $def;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		}
	}

	//Tests for 'string' type preferences
	function testPrefsDescriptionsString() {
		$correct = array(
			'fields' => array(
				array(
					'name' => 'testString',
					'type' => 'string',
					'label' => 'some label',
					'minlength' => 6,
					'maxlength' => 10,
					'required' => false,
					'default' => 'default'
				)
			)
		);

		$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct ) );

		//Tests with wrong default values
		$wrong = $correct;
		foreach ( array( null, true, false, 0, 1, array(), 'short', 'veryverylongstring' ) as $def ) {
			$wrong['fields'][0]['default'] = $def;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		}

		//Tests with correct default values (when required is false)
		$correct2 = $correct;
		foreach ( array( '', '6chars', '1234567890' ) as $def ) {
			$correct2['fields'][0]['default'] = $def;
			$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct2 ) );
		}

		//Test with empty default when "required" is true
		$wrong = $correct;
		$wrong['fields']['testString']['required'] = true;
		$wrong['fields']['testString']['default'] = '';
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
	}

	//Tests for 'number' type preferences
	function testPrefsDescriptionsNumber() {
		$correctFloat = array(
			'fields' => array(
				array(
					'name' => 'testNumber',
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
				array(
					'name' => 'testNumber',
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

		$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correctFloat ) );
		$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correctInt ) );

		//Tests with wrong default values (with 'required' = true)
		$wrongFloat = $correctFloat;
		foreach ( array( '', false, true, null, array(), -100, +100 ) as $def ) {
			$wrongFloat['fields'][0]['default'] = $def;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrongFloat ) );
		}

		$wrongInt = $correctInt;
		foreach ( array( '', false, true, null, array(), -100, +100, 2.7182818 ) as $def ) {
			$wrongInt['fields'][0]['default'] = $def;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrongInt ) );
		}

		//If required=false, default=null must be accepted, too
		foreach ( array( $correctFloat, $correctInt ) as $correct ) {
			$correct['fields'][0]['required'] = false;
			$correct['fields'][0]['default'] = null;
			$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct ) );
		}
	}

	//Tests for 'select' type preferences
	function testPrefsDescriptionsSelect() {
		$correct = array(
			'fields' => array(
				array(
					'name' => 'testSelect',
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
			$correct2['fields'][0]['default'] = $def;
			$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct2 ) );
		}

		//Tests with wrong default values
		$wrong = $correct;
		foreach ( array( '', 'true', 'null', false, array(), 0, 1, 3.0001 ) as $def ) {
			$wrong['fields'][0]['default'] = $def;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		}
	}

	//Tests for 'range' type preferences
	function testPrefsDescriptionsRange() {
		$correct = array(
			'fields' => array(
				array(
					'name' => 'testRange',
					'type' => 'range',
					'label' => 'some label',
					'default' => 35,
					'min' => 15,
					'max' => 45
				)
			)
		);

		//Tests with correct default values
		$correct2 = $correct;
		foreach ( array( 15, 33, 45 ) as $def ) {
			$correct2['fields'][0]['default'] = $def;
			$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct2 ) );
		}

		//Tests with wrong default values
		$wrong = $correct;
		foreach ( array( '', true, false, null, array(), '35', 14, 46, 30.2 ) as $def ) {
			$wrong['fields'][0]['default'] = $def;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		}
		
		//Test with max not in the set min + k*step (step not given, so it's 1)
		$wrong = $correct;
		$wrong['fields'][0]['max'] = 45.5;
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		
		
		//Tests with floating point min, max and step
		$correct = array(
			'fields' => array(
				array(
					'name' => 'testRange',
					'type' => 'range',
					'label' => 'some label',
					'default' => 0.20,
					'min' => -2.8,
					'max' => 4.2,
					'step' => 0.25
				)
			)
		);
		
		$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct ) );
		
		//Tests with correct default values
		$correct2 = $correct;
		foreach ( array( -2.8, -2.55, 0.20, 4.2 ) as $def ) {
			$correct2['fields'][0]['default'] = $def;
			$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct2 ) );
		}

		//Tests with wrong default values
		$wrong = $correct;
		foreach ( array( '', true, false, null, array(), '0.20', -2.7, 0, 4.199999 ) as $def ) {
			$wrong['fields'][0]['default'] = $def;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		}
	}
	
	//Tests for 'date' type preferences
	function testPrefsDescriptionsDate() {
		$correct = array(
			'fields' => array(
				array(
					'name' => 'testDate',
					'type' => 'date',
					'label' => 'some label',
					'default' => null
				)
			)
		);
		
		//Tests with correct default values
		$correct2 = $correct;
		foreach ( array(
				null,
				'2011-07-05T15:00:00Z',
				'2011-01-01T00:00:00Z',
				'2011-12-31T23:59:59Z',
			) as $def )
		{
			$correct2['fields'][0]['default'] = $def;
			$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct2 ) );
		}

		//Tests with wrong default values
		$wrong = $correct;
		foreach ( array(
				'', true, false, array(), 0,
				'2011-07-05T15:00:00',
				'2011-07-05T15:00:61Z',
				'2011-07-05T15:61:00Z',
				'2011-07-05T25:00:00Z',
				'2011-07-32T15:00:00Z',
				'2011-07-5T15:00:00Z',
				'2011-7-05T15:00:00Z',
				'2011-13-05T15:00:00Z',
				'2011-07-05T15:00-00Z',
				'2011-07-05T15-00:00Z',
				'2011-07-05S15:00:00Z',
				'2011-07:05T15:00:00Z',
				'2011:07-05T15:00:00Z'
			) as $def )
		{
			$wrong['fields'][0]['default'] = $def;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		}
	}

	//Tests for 'color' type preferences
	function testPrefsDescriptionsColor() {
		$correct = array(
			'fields' => array(
				array(
					'name' => 'testColor',
					'type' => 'color',
					'label' => 'some label',
					'default' => '#123456'
				)
			)
		);
		
		//Tests with correct default values
		$correct2 = $correct;
		foreach ( array(
				'#000000',
				'#ffffff',
				'#8ed36e',
			) as $def )
		{
			$correct2['fields'][0]['default'] = $def;
			$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct2 ) );
		}

		//Tests with wrong default values
		$wrong = $correct;
		foreach ( array(
				'', true, false, null, 0, array(),
				'123456',
				'#629af',
				'##123456',
				'#1aefdq',
				'#145aeF', //uppercase letters not allowed
				'#179', //syntax not allowed
				'red', //syntax not allowed
			) as $def )
		{
			$wrong['fields'][0]['default'] = $def;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		}
	}

	
	//Data provider to be able to reuse this preference description for several tests.
	function prefsDescProvider() {
		return array( array(
			array(
				'fields' => array(
					array(
						'name' => 'testBoolean',
						'type' => 'boolean',
						'label' => '@foo',
						'default' => true
					),
					array(
						'name' => 'testBoolean2',
						'type' => 'boolean',
						'label' => '@@foo2',
						'default' => true
					),
					array(
						'name' => 'testNumber',
						'type' => 'number',
						'label' => '@foo3',
						'min' => 2.3,
						'max' => 13.94,
						'default' => 7
					),
					array(
						'name' => 'testNumber2',
						'type' => 'number',
						'label' => 'foo4',
						'min' => 2.3,
						'max' => 13.94,
						'default' => 7
					),
					array(
						'name' => 'testSelect',
						'type' => 'select',
						'label' => 'foo',
						'default' => 3,
						'options' => array(
							'@opt1' => null,
							'@opt2' => true,
							'opt3' => 3,
							'@opt4' => 'opt4value'
						)
					),
					array(
						'name' => 'testSelect2',
						'type' => 'select',
						'label' => 'foo',
						'default' => 3,
						'options' => array(
							'@opt1' => null,
							'opt2' => true,
							'opt3' => 3,
							'opt4' => 'opt4value'
						)
					)
				)
			)
		) );
	}
	
	/**
	 * Tests Gadget::setPrefsDescription, GadgetPrefs::checkPrefsAgainstDescription,
	 * GadgetPrefs::matchPrefsWithDescription and Gadget::setPrefs.
	 *
	 * @dataProvider prefsDescProvider
	 */
	function testSetPrefs( $prefsDescription ) {
		$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $prefsDescription ) );
		
		$prefs = array(
			'testBoolean' => false,
			'testBoolean2' => null, //wrong
			'testNumber' => 11,
			'testNumber2' => 45, //wrong
			'testSelect' => true,
			'testSelect2' => false //wrong
		);
		
		$this->assertFalse( GadgetPrefs::checkPrefsAgainstDescription( $prefsDescription, $prefs ) );
		
		$prefs2 = $prefs;
		GadgetPrefs::matchPrefsWithDescription( $prefsDescription, $prefs2 );
		//Now $prefs2 should pass validation
		$this->assertTrue( GadgetPrefs::checkPrefsAgainstDescription( $prefsDescription, $prefs2 ) );

		//$prefs2 should have testBoolean, testNumber and testSelect unchanged, the other reset to defaults
		$this->assertEquals( $prefs2['testBoolean'], $prefs['testBoolean'] );
		$this->assertEquals( $prefs2['testNumber'], $prefs['testNumber'] );
		$this->assertEquals( $prefs2['testSelect'], $prefs['testSelect'] );

		$this->assertEquals( $prefs2['testBoolean2'], $prefsDescription['fields'][1]['default'] );
		$this->assertEquals( $prefs2['testNumber2'], $prefsDescription['fields'][3]['default'] );
		$this->assertEquals( $prefs2['testSelect2'], $prefsDescription['fields'][5]['default'] );
		
		$g = $this->create( '*foo[ResourceLoader]| foo.css|foo.js|foo.bar' );
		$g->setPrefsDescription( $prefsDescription );
		$this->assertTrue( $g->getPrefsDescription() !== null );
		
		//Setting wrong preferences must fail
		$this->assertFalse( $g->setPrefs( $prefs ) );
		
		//Setting right preferences must succeed
		$this->assertTrue( $g->setPrefs( $prefs2 ) );
		
		//Adding a field not in the description must fail
		$prefs2['someUnexistingPreference'] = 'bar';
		$this->assertFalse( GadgetPrefs::checkPrefsAgainstDescription( $prefsDescription, $prefs2 ) );
	}

	/**
	 * @expectedException MWException
	 */
	function testSetPrefsWithWrongParam() {
		$g = $this->create( '*foo[ResourceLoader]| foo.css|foo.js|foo.bar' );
		$g->setPrefsDescription( array(
			'fields' => array(
				'testBoolean' => array(
					'type' => 'boolean',
					'label' => 'foo',
					'default' => true
				)
			)
		) );
		
		//Call with invalid param
		$g->setPrefs( 'wrongparam' );
	}
	

	/**
	 * Tests GadgetPrefs::getMessages.
	 *
	 * @dataProvider prefsDescProvider
	 */
	function testGetMessages( $prefsDescription ) {
		$msgs = GadgetPrefs::getMessages( $prefsDescription );
		$this->assertEquals( $msgs, array(
			'foo', 'foo3', 'opt1', 'opt2', 'opt4'
		) );
	}
}
