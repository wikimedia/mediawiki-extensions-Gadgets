<?php

/**
 * @group Gadgets
 */
class GadgetTest extends PHPUnit_Framework_TestCase {
	/**
	 * @dataProvider provideValidatePropertiesArray
	 */
	public function testValidatePropertiesArray( $input, $expectSuccess, $expectedErrors, $desc ) {
		$status = Gadget::validatePropertiesArray( $input );
		$this->assertEquals( $expectSuccess, $status->isGood(), $desc );
		$this->assertEquals( $expectedErrors, $status->getErrorsArray(), $desc );
	}
	
	// Helper function because array_merge_recursive() doesn't work well for overriding arrays with other types
	private static function buildPropertiesArray( $props ) {
		$retval = Gadget::getPropertiesBase();
		foreach ( $props as $outerKey => $arr ) {
			if ( is_array( $arr ) ) {
				foreach ( $arr as $innerKey => $value ) {
					$retval[$outerKey][$innerKey] = $value;
				}
			} else {
				$retval[$outerKey] = $arr;
			}
		}
		return $retval;
	}
	
	public function provideValidatePropertiesArray() {
		return array(
			array( null, false, array( array( 'gadgets-validate-invalidjson' ) ), 'only arrays are accepted (testing null)' ),
			array( 'foo', false, array( array( 'gadgets-validate-invalidjson' ) ), 'only arrays are accepted (testing string)' ),
			array( '{"settings": {"rights":[], "default":false,"hidden":false,"shared":true,"category":"foo"},"module":{"scripts":["Foo.js", "Foo2.js"],"styles":["Foo.css"],"dependencies":[],"messages":["sessionfailure"]}}', false, array( array( 'gadgets-validate-invalidjson' ) ), 'only arrays are accepted (testing valid JSON string)' ),
			array( self::buildPropertiesArray( array() ), true, array(), 'base array is accepted' ),
			array( self::buildPropertiesArray( array( 'settings' => 'blah' ) ), false, array( array( 'gadgets-validate-wrongtype', 'settings', 'array', 'string' ) ), 'settings set to a string' ),
			array( self::buildPropertiesArray( array( 'settings' => array( 'rights' => 'protect' ) ) ), false, array( array( 'gadgets-validate-wrongtype', 'settings.rights', 'array', 'string' ) ), 'rights set to a string' ),
			array( self::buildPropertiesArray( array( 'settings' => array( 'default' => 42 ) ) ), false, array( array( 'gadgets-validate-wrongtype', 'settings.default', 'boolean', 'integer' ) ), 'default set to an integer' ),
			array( self::buildPropertiesArray( array( 'settings' => array( 'hidden' => 3.14 ) ) ), false, array( array( 'gadgets-validate-wrongtype', 'settings.hidden', 'boolean', 'double' ) ), 'hidden set to a double' ),
			array( self::buildPropertiesArray( array( 'settings' => array( 'shared' => array( 'foo' => 'bar' ) ) ) ), false, array( array( 'gadgets-validate-wrongtype', 'settings.shared', 'boolean', 'array' ) ), 'shared set to an array' ),
			array( self::buildPropertiesArray( array( 'settings' => array( 'category' => null ) ) ), false, array( array( 'gadgets-validate-wrongtype', 'settings.category', 'string', 'NULL' ) ), 'category set to null' ),
			array( self::buildPropertiesArray( array( 'module' => 'blah' ) ), false, array( array( 'gadgets-validate-wrongtype', 'module', 'array', 'string' ) ), 'module set to a string' ),
			array( self::buildPropertiesArray( array( 'module' => array( 'scripts' => null ) ) ), false, array( array( 'gadgets-validate-wrongtype', 'module.scripts', 'array', 'NULL' ) ), 'scripts set to NULL' ),
			array( self::buildPropertiesArray( array( 'module' => array( 'styles' => (object)array( 'Foo.css' ) ) ) ), false, array( array( 'gadgets-validate-wrongtype', 'module.styles', 'array', 'object' ) ), 'styles set to an object' ),
			array( self::buildPropertiesArray( array( 'module' => array( 'messages' => array( 'foo', 'bar', 1, 'baz' ) ) ) ), false, array( array( 'gadgets-validate-wrongtype', 'module.messages[2]', 'string', 'integer' ) ), 'messages[2] set to an integer' ),
			array( self::buildPropertiesArray( array( 'module' => array( 'dependencies' => array( null ) ) ) ), false, array( array( 'gadgets-validate-wrongtype', 'module.dependencies[0]', 'string', 'NULL' ) ), 'dependencies[0] set to null' ),
		);
	}
	
	public function testGetters() {
		$now = wfTimestampNow();
		$data = array(
			'settings' => array(
				'rights' => array( 'protect' ),
				'default' => true,
				'hidden' => false,
				'shared' => true,
				'category' => 'foobar',
			),
			'module' => array(
				'scripts' => array( 'Foo.js', 'Bar.js' ),
				'styles' => array( 'Foo.css' ),
				'messages' => array( 'january', 'february' ),
				'dependencies' => array( 'jquery.ui.button' ) 
			)
		);
		$g = new Gadget( 'GadgetTest', LocalGadgetRepo::singleton(), $data, wfTimestampNow() );
		$this->assertEquals( $data, $g->getMetadata(), 'getMetadata' );
		$this->assertEquals( FormatJson::encode( $data ), $g->getJSON(), 'getJSON' );
		$this->assertEquals( 'GadgetTest', $g->getId(), 'getId' );
		$this->assertEquals( LocalGadgetRepo::singleton(), $g->getRepo(), 'getRepo' );
		$this->assertEquals( $now, $g->getTimestamp(), 'getTimestamp' );
		$this->assertEquals( $data['settings']['category'], $g->getCategory(), 'getCategory' );
		$this->assertEquals( $data['settings']['default'], $g->isEnabledByDefault(), 'isEnabledByDefault' );
		$this->assertEquals( $data['settings']['rights'], $g->getRequiredRights(), 'getRequiredRights' );
		$this->assertEquals( $data['settings']['hidden'], $g->isHidden(), 'isHidden' );
		$this->assertEquals( $data['settings']['shared'], $g->isShared(), 'isShared' );
		$this->assertEquals( $data['module']['scripts'], $g->getScripts(), 'getScripts' );
		$this->assertEquals( $data['module']['styles'], $g->getStyles(), 'getStyles' );
		$this->assertEquals( $data['module']['dependencies'], $g->getDependencies(), 'getDependencies' );
	}
}
