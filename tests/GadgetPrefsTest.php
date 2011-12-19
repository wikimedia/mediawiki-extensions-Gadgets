<?php
/**
 * @group Gadgets
 */
class GadgetPrefsTest extends MediaWikiTestCase {
	// Test preferences descriptions validator (generic)
	function testPrefsDescriptions() {
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( null ) );
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( array() ) );
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( array( 'fields' => array() ) ) );

		// Test with stdClass instead of array
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

		// Test with wrong type
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

		// Test with missing name
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( array(
			'fields' => array(
				array(
					'type' => 'boolean',
					'label' => 'foo',
					'default' => true
				)
			)
		) ) );

		// Test with wrong preference name
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

		// Test with two fields with the same name
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

		// Test with fields encoded as associative array instead of regular array
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

		// Test with too long preference name (41 characters)
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


		// Test with an unexisting field parameter
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

	// Tests for 'label' type preferences
	function testPrefsDescriptionsLabel() {
		$correct = array(
			'fields' => array(
				array(
					'type' => 'label',
					'label' => 'foo'
				)
			)
		);

		// Tests with correct values for 'label'
		foreach ( array( '', '@', '@message', 'foo', '@@not message' ) as $def ) {
			$correct['fields'][0]['label'] = $def;
			$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct ) );
		}
		
		// Tests with wrong values for 'label'
		$wrong = $correct;
		foreach ( array( 0, 1, true, false, null, array() ) as $label ) {
			$wrong['fields'][0]['label'] = $label;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		}
		
	}

	// Tests for 'boolean' type preferences
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

		// Tests with wrong default values
		$wrong = $correct;
		foreach ( array( 0, 1, '', 'false', 'true', null, array() ) as $def ) {
			$wrong['fields'][0]['default'] = $def;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		}
	}

	// Tests for 'string' type preferences
	function testPrefsDescriptionsString() {
		$correct = array(
			'fields' => array(
				array(
					'name' => 'testString',
					'type' => 'string',
					'label' => 'some label',
					'minlength' => 6,
					'maxlength' => 10,
					'default' => 'default'
				)
			)
		);

		$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct ) );

		// Tests with wrong default values (when 'required' is not given)
		$wrong = $correct;
		foreach ( array( null, '', true, false, 0, 1, array(), 'short', 'veryverylongstring' ) as $def ) {
			$wrong['fields'][0]['default'] = $def;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		}

		// Tests with correct default values (when required is not given)
		$correct2 = $correct;
		foreach ( array( '6chars', '1234567890' ) as $def ) {
			$correct2['fields'][0]['default'] = $def;
			$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct2 ) );
		}

		// Tests with wrong default values (when 'required' is false)
		$wrong = $correct;
		$wrong['fields'][0]['required'] = false;
		foreach ( array( null, true, false, 0, 1, array(), 'short', 'veryverylongstring' ) as $def ) {
			$wrong['fields'][0]['default'] = $def;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		}

		// Tests with correct default values (when required is false)
		$correct2 = $correct;
		$correct2['fields'][0]['required'] = false;
		foreach ( array( '', '6chars', '1234567890' ) as $def ) {
			$correct2['fields'][0]['default'] = $def;
			$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct2 ) );
		}

		$correct = array(
			'fields' => array(
				array(
					'name' => 'testString',
					'type' => 'string',
					'label' => 'some label',
					'default' => ''
				)
			)
		);

		// Test with empty default when "required" is true
		$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct ) );

		// Test with empty default when "required" is true
		$wrong = $correct;
		$wrong['fields'][0]['required'] = true;
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );

		// Test with empty default when "required" is false and minlength is given
		$correct2 = $correct;
		$correct2['fields'][0]['required'] = false;
		$correct2['fields'][0]['minlength'] = 3;
		$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct2 ) );
	}

	// Tests for 'number' type preferences
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

		// Tests with wrong default values (with 'required' = true)
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

	// Tests for 'select' type preferences
	function testPrefsDescriptionsSelect() {
		$correct = array(
			'fields' => array(
				array(
					'name' => 'testSelect',
					'type' => 'select',
					'label' => 'some label',
					'default' => 3,
					'options' => array(
						array( 'name' => 'opt1', 'value' => null ),
						array( 'name' => 'opt2', 'value' => true ),
						array( 'name' => 'opt3', 'value' => 3 ),
						array( 'name' => 'opt4', 'value' => 'test' )
					)
				)
			)
		);


		// Tests with correct default values
		$correct2 = $correct;
		foreach ( array( null, true, 3, 'test' ) as $def ) {
			$correct2['fields'][0]['default'] = $def;
			$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct2 ) );
		}

		// Tests with wrong default values
		$wrong = $correct;
		foreach ( array( '', 'true', 'null', false, array(), 0, 1, 3.0001 ) as $def ) {
			$wrong['fields'][0]['default'] = $def;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		}
	}

	// Tests for 'range' type preferences
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

		// Tests with correct default values
		$correct2 = $correct;
		foreach ( array( 15, 33, 45 ) as $def ) {
			$correct2['fields'][0]['default'] = $def;
			$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct2 ) );
		}

		// Tests with wrong default values
		$wrong = $correct;
		foreach ( array( '', true, false, null, array(), '35', 14, 46, 30.2 ) as $def ) {
			$wrong['fields'][0]['default'] = $def;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		}
		
		// Test with max not in the set min + k*step (step not given, so it's 1)
		$wrong = $correct;
		$wrong['fields'][0]['max'] = 45.5;
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		
		
		// Tests with floating point min, max and step
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
		
		// Tests with correct default values
		$correct2 = $correct;
		foreach ( array( -2.8, -2.55, 0.20, 4.2 ) as $def ) {
			$correct2['fields'][0]['default'] = $def;
			$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct2 ) );
		}

		// Tests with wrong default values
		$wrong = $correct;
		foreach ( array( '', true, false, null, array(), '0.20', -2.7, 0, 4.199999 ) as $def ) {
			$wrong['fields'][0]['default'] = $def;
			$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		}
	}
	
	// Tests for 'date' type preferences
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
		
		// Tests with correct default values
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

		// Tests with wrong default values
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

	// Tests for 'color' type preferences
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
		
		// Tests with correct default values
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

		// Tests with wrong default values
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

	// Tests for 'composite' type fields
	function testPrefsDescriptionsComposite() {
		$correct = array(
			'fields' => array(
				array(
					'name' => 'foo',
					'type' => 'composite',
					'fields' => array(
						array(
							'name' => 'bar',
							'type' => 'boolean',
							'label' => '@msg1',
							'default' => true
						),
						array(
							'name' => 'car',
							'type' => 'color',
							'label' => '@msg2',
							'default' => '#123456'
						)
					)
				)
			)
		);
		
		$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct ) );
		$this->assertEquals(
			GadgetPrefs::getDefaults( $correct ),
			array( 'foo' => array( 'bar' => true, 'car' => '#123456' ) )
		);
		$this->assertEquals( GadgetPrefs::getMessages( $correct ), array( 'msg1', 'msg2' ) );
		
		$this->assertTrue( GadgetPrefs::checkPrefsAgainstDescription(
			$correct,
			array( 'foo' => array( 'bar' => false, 'car' => '#00aaff' ) )
		) );

		$this->assertFalse( GadgetPrefs::checkPrefsAgainstDescription(
			$correct,
			array( 'foo' => array( 'bar' => null, 'car' => '#00aaff' ) )
		) );

		$this->assertFalse( GadgetPrefs::checkPrefsAgainstDescription(
			$correct,
			array( 'foo' => array( 'bar' => false, 'car' => '#00aafz' ) )
		) );

		$this->assertFalse( GadgetPrefs::checkPrefsAgainstDescription(
			$correct,
			array( 'bar' => false, 'car' => '#00aaff' )
		) );

		$prefs = array(
			'foo' => array(
				'bar' => false,
				'car' => null //wrong
			)
		);
		
		GadgetPrefs::matchPrefsWithDescription( $correct, $prefs );
		//Check if only the wrong subfield has been reset to default value
		$this->assertEquals( $prefs, array( 'foo' => array( 'bar' => false, 'car' => '#123456' ) ) );
	}

	// Tests for 'list' type fields
	function testPrefsDescriptionsList() {
		$correct = array(
			'fields' => array(
				array(
					'name' => 'foo',
					'type' => 'list',
					'default' => array(),
					'field' => array(
						'type' => 'composite',
						'fields' => array(
							array(
								'name' => 'bar',
								'type' => 'boolean',
								'label' => '@msg1',
								'default' => true
							),
							array(
								'name' => 'car',
								'type' => 'color',
								'label' => '@msg2',
								'default' => '#123456'
							)
						)
					)
				)
			)
		);
		
		$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct ) );
		
		//Specifying the 'name' member for field must fail
		$wrong = $correct;
		$wrong['fields'][0]['field']['name'] = 'composite';
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		
		
		$this->assertEquals(
			GadgetPrefs::getDefaults( $correct ),
			array( 'foo' => array() )
		);
		
		$this->assertEquals( GadgetPrefs::getMessages( $correct ), array( 'msg1', 'msg2' ) );

		// Tests with correct pref values
		$this->assertTrue( GadgetPrefs::checkPrefsAgainstDescription(
			$correct,
			array( 'foo' => array() )
		) );

		$this->assertTrue( GadgetPrefs::checkPrefsAgainstDescription(
			$correct,
			array( 'foo' => array(
					array(
						'bar' => true,
						'car' => '#115599'
					),
					array(
						'bar' => false,
						'car' => '#123456'
					),
					array(
						'bar' => true,
						'car' => '#ffffff'
					)
				)
			)
		) );

		// Tests with wrong pref values
		$this->assertFalse( GadgetPrefs::checkPrefsAgainstDescription(
			$correct,
			array( 'foo' => array(
					array(
						'bar' => null, //wrong
						'car' => '#115599'
					)
				)
			)
		) );

		$this->assertFalse( GadgetPrefs::checkPrefsAgainstDescription(
			$correct,
			array( 'foo' => array( //wrong, not enclosed in array
					'bar' => null,
					'car' => '#115599'
				)
			)
		) );


		// Tests with 'minlength' and 'maxlength' options
		$wrong = $correct;
		$wrong['fields'][0]['minlength'] = 4;
		$wrong['fields'][0]['maxlength'] = 3; //maxlength < minlength, wrong
		$this->assertFalse( GadgetPrefs::isPrefsDescriptionValid( $wrong ) );
		
		$correct2 = $correct;
		$correct2['fields'][0]['minlength'] = 2;
		$correct2['fields'][0]['maxlength'] = 3;
		$correct2['fields'][0]['default'] = array(
			array( 'bar' => true, 'car' => '#115599' ),
			array( 'bar' => false, 'car' => '#123456' )
		);
		$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $correct2 ) );

		$this->assertFalse( GadgetPrefs::checkPrefsAgainstDescription(
			$correct2,
			array( 'foo' => array( //less than minlength items
					array( 'bar' => true, 'car' => '#115599' )
				)
			)
		) );

		$this->assertFalse( GadgetPrefs::checkPrefsAgainstDescription(
			$correct2,
			array( 'foo' => array() ) //empty array, must fail because "required" is not false
		) );

		$this->assertFalse( GadgetPrefs::checkPrefsAgainstDescription(
			$correct2,
			array( 'foo' => array( //more than minlength items
					array( 'bar' => true, 'car' => '#115599' ),
					array( 'bar' => false, 'car' => '#123456' ),
					array( 'bar' => true, 'car' => '#ffffff' ),
					array( 'bar' => false, 'car' => '#2357bd' )
				)
			)
		) );

		// Test with 'required'
		$correct2['fields'][0]['required'] = false;
		$this->assertTrue( GadgetPrefs::checkPrefsAgainstDescription(
			$correct2,
			array( 'foo' => array() ) //empty array, must be accepted because "required" is false
		) );
		
		// Tests matchPrefsWithDescription
		$prefs = array( 'foo' => array(
				array(
					'bar' => null,
					'car' => '#115599'
				),
				array(
					'bar' => false,
					'car' => ''
				),
				array(
					'bar' => true,
					'car' => '#ffffff'
				)
			)
		);
		
		
		GadgetPrefs::matchPrefsWithDescription( $correct, $prefs );
		$this->assertTrue( GadgetPrefs::checkPrefsAgainstDescription( $correct, $prefs ) );
	}

	//Data provider to be able to reuse a complex preference description for several tests.
	function prefsDescProvider() {
		return array( array(
			array(
				'fields' => array(
					array(
						'type' => 'bundle',
						'sections' => array(
							array(
								'title' => '@section1',
								'fields' => array (
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
									)
								)
							),
							array(
								'title' => 'Section2',
								'fields' => array(
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
											array( 'name' => '@opt1', 'value' => null ),
											array( 'name' => '@opt2', 'value' => true ),
											array( 'name' => 'opt3', 'value' => 3 ),
											array( 'name' => '@opt4', 'value' => 'opt4value' )
										)
									),
									array(
										'name' => 'testSelect2',
										'type' => 'select',
										'label' => 'foo',
										'default' => 3,
										'options' => array(
											array( 'name' => '@opt1', 'value' => null ),
											array( 'name' => 'opt2', 'value' => true ),
											array( 'name' => 'opt3', 'value' => 3 ),
											array( 'name' => 'opt4', 'value' => 'opt4value' )
										)
									)
								)
							)
						)
					)
				)
			)
		) );
	}

	/**
	 * Tests Gadget::getDefaults
	 *
	 * @dataProvider prefsDescProvider
	 */
	function testGetDefaults( $prefsDescription ) {
		$this->assertEquals( GadgetPrefs::getDefaults( $prefsDescription ), array(
			'testBoolean' => true,
			'testBoolean2' => true,
			'testNumber' => 7,
			'testNumber2' => 7,
			'testSelect' => 3,
			'testSelect2' => 3
		) );
	}
	
	private static function createGadgetObject() {
		$gSettings = Gadget::getPropertiesBase();
		$gSettings['module']['styles'] = array( 'foo.css' );
		$gSettings['module']['scripts'] = array( 'foo.js' );
		return new Gadget( 'GadgetsTest', LocalGadgetRepo::singleton(), $gSettings, wfTimestampNow() );
	}

	/**
	 * Tests Gadget::setPrefsDescription, GadgetPrefs::checkPrefsAgainstDescription,
	 * GadgetPrefs::matchPrefsWithDescription and Gadget::setPrefs.
	 *
	 * @dataProvider prefsDescProvider
	 */
	function testSetPrefs( $prefsDescription ) {
		// FIXME this test is broken
		$this->markTestIncomplete( 'Gadget::setPrefs not yet implemented' );
		return;

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

		$defaults = GadgetPrefs::getDefaults( $prefsDescription );
		$this->assertEquals( $prefs2['testBoolean2'], $defaults['testBoolean2'] );
		$this->assertEquals( $prefs2['testNumber2'], $defaults['testNumber2'] );
		$this->assertEquals( $prefs2['testSelect2'], $defaults['testSelect2'] );
		
		$g = self::createGadgetObject();
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
		// FIXME this test is broken
		$this->markTestIncomplete( 'Gadget::setPrefs not yet implemented' );
		return;
		
		$g = self::createGadgetObject();
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
	 * Tests GadgetPrefs::simplifyPrefs.
	 */
	function testSimplifyPrefs() {
		$prefsDescription = array(
			'fields' => array(
				array(
					'type' => 'boolean',
					'name' => 'foo',
					'label' => 'some label',
					'default' => true
				),
				array(
					'type' => 'bundle',
					'sections' => array(
						array(
							'name' => 'Section 1',
							'fields' => array(
								array(
									'type' => 'boolean',
									'name' => 'bar',
									'label' => 'dummy label',
									'default' => false
								),
							)
						),
						array(
							'name' => 'Section 2',
							'fields' => array(
								array(
									'type' => 'string',
									'name' => 'baz',
									'label' => 'A string',
									'default' => 'qwerty'
								)
							)
						)
					)
				),
				array(
					'type' => 'composite',
					'name' => 'cmp',
					'fields' => array(
						array(
							'type' => 'number',
							'name' => 'aNumber',
							'label' => 'A number',
							'default' => 3.14
						),
						array(
							'type' => 'color',
							'name' => 'aColor',
							'label' => 'A color',
							'default' => '#a023e2'
						)
					)
				),
				array(
					'type' => 'list',
					'name' => 'aList',
					'default' => array( 2, 3, 5, 7 ),
					'field' => array(
						'type' => 'range',
						'label' => 'A range',
						'min' => 0,
						'max' => 256,
						'default' => 128
					)
				)
			)
		);
		
		$this->assertTrue( GadgetPrefs::isPrefsDescriptionValid( $prefsDescription ) );
		
		$prefs = array(
			'foo' => true, //=default
			'bar' => true,
			'baz' => 'asdfgh',
			'cmp' => array(
				'aNumber' => 2.81,
				'aColor' => '#a023e2' //=default
			),
			'aList' => array( 2, 3, 5, 9 )
		);
		
		GadgetPrefs::simplifyPrefs( $prefsDescription, $prefs );
		$this->assertEquals(
			$prefs,
			array(
				'bar' => true,
				'baz' => 'asdfgh',
				'cmp' => array(
					'aNumber' => 2.81,
				),
				'aList' => array( 2, 3, 5, 9 )
			)
		);
		

		$prefs = array(
			'foo' => false,
			'bar' => false, //=default
			'baz' => 'asdfgh',
			'cmp' => array(
				'aNumber' => 3.14, //=default
				'aColor' => '#a023e2' //=default
			),
			'aList' => array( 2, 3, 5, 7 ) //=default
		);
		GadgetPrefs::simplifyPrefs( $prefsDescription, $prefs );
		$this->assertEquals(
			$prefs,
			array(
				'foo' => false,
				'baz' => 'asdfgh'
			)
		);
	}

	/**
	 * Tests GadgetPrefs::getMessages.
	 *
	 * @dataProvider prefsDescProvider
	 */
	function testGetMessages( $prefsDescription ) {
		$msgs = GadgetPrefs::getMessages( $prefsDescription );
		sort( $msgs );
		$this->assertEquals( $msgs, array(
			'foo', 'foo3', 'opt1', 'opt2', 'opt4', 'section1'
		) );
	}
}
