<?php

/**
 * @group Gadgets
 * @group Database
 */
class GadgetTest extends MediaWikiTestCase {

	/**
	 * @covers Gadget::__construct
	 */
	public function testConstructor() {
		$gadget = new Gadget( 'unittest', new LocalGadgetRepo );
		$this->assertInstanceOf( 'Gadget', $gadget );
	}

	/**
	 * @covers Gadget::isValidGadgetID
	 * @dataProvider provideIsValidGadgetID
	 */
	public function testIsValidGadgetID( $input, $expectedToBeValid, $desc ) {
		$this->assertEquals( $expectedToBeValid, Gadget::isValidGadgetID( $input ), $desc );
	}

	public function provideIsValidGadgetID() {
		return array(
			array( 'Foo', true, 'normal gadget ID' ),
			array( 'foo', true, 'gadget ID starting with lowercase letter' ),
			array( 'foo|bar', false, 'gadget ID containing a pipe' ),
			array( 'foo,bar', false, 'gadget ID containing a comma' ),
			array( 'foo!bar', false, 'gadget ID containing an exclamation mark' ),
			array( 'foo[bar', false, 'gadget ID containing a bracket' ),
			array( 'foo/../', false, 'gadget ID containing ../' ),
			array( str_repeat( 'f', 245 ), false, '245-byte gadget ID' ),
			array( str_repeat( 'f', 244 ), true, '244-byte gadget ID' ),
		);
	}

	/**
	 * @covers Gadget::validatePropertiesArray
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
			array( '{"settings": {"rights":[], "default":false,"hidden":false,"shared":true,"category":"foo"},"module":{"scripts":["Foo.js",
"Foo2.js"],"styles":["Foo.css"],"dependencies":[],"messages":["sessionfailure"]}}', false, array( array( 'gadgets-validate-invalidjson' ) ), 'only arrays are accepted (testing valid JSON string)'
),
			array( self::buildPropertiesArray( array() ), true, array(), 'base array is accepted' ),
			array( self::buildPropertiesArray( array( 'settings' => 'blah' ) ), false, array( array( 'gadgets-validate-wrongtype', 'settings', 'array', 'string' ) ), 'settings set to a
string' ),
			array( self::buildPropertiesArray( array( 'settings' => array( 'rights' => 'protect' ) ) ), false, array( array( 'gadgets-validate-wrongtype', 'settings.rights', 'array',
'string' ) ), 'rights set to a string' ),
			array( self::buildPropertiesArray( array( 'settings' => array( 'default' => 42 ) ) ), false, array( array( 'gadgets-validate-wrongtype', 'settings.default', 'boolean',
'integer' ) ), 'default set to an integer' ),
			array( self::buildPropertiesArray( array( 'settings' => array( 'hidden' => 3.14 ) ) ), false, array( array( 'gadgets-validate-wrongtype', 'settings.hidden', 'boolean',
'double' ) ), 'hidden set to a double' ),
			array( self::buildPropertiesArray( array( 'settings' => array( 'shared' => array( 'foo' => 'bar' ) ) ) ), false, array( array( 'gadgets-validate-wrongtype',
'settings.shared', 'boolean', 'array' ) ), 'shared set to an array' ),
			array( self::buildPropertiesArray( array( 'settings' => array( 'category' => null ) ) ), false, array( array( 'gadgets-validate-wrongtype', 'settings.category', 'string',
'NULL' ) ), 'category set to null' ),
			array( self::buildPropertiesArray( array( 'module' => 'blah' ) ), false, array( array( 'gadgets-validate-wrongtype', 'module', 'array', 'string' ) ), 'module set to a
string' ),
			array( self::buildPropertiesArray( array( 'module' => array( 'scripts' => null ) ) ), false, array( array( 'gadgets-validate-wrongtype', 'module.scripts', 'array', 'NULL' )
), 'scripts set to NULL' ),
			array( self::buildPropertiesArray( array( 'module' => array( 'styles' => (object)array( 'Foo.css' ) ) ) ), false, array( array( 'gadgets-validate-wrongtype',
'module.styles', 'array', 'object' ) ), 'styles set to an object' ),
			array( self::buildPropertiesArray( array( 'module' => array( 'messages' => array( 'foo', 'bar', 1, 'baz' ) ) ) ), false, array( array( 'gadgets-validate-wrongtype',
'module.messages[2]', 'string', 'integer' ) ), 'messages[2] set to an integer' ),
			array( self::buildPropertiesArray( array( 'module' => array( 'dependencies' => array( null ) ) ) ), false, array( array( 'gadgets-validate-wrongtype',
'module.dependencies[0]', 'string', 'NULL' ) ), 'dependencies[0] set to null' ),
		);
	}

	public static function getBoilerplateData() {
		// Should be static or const or something, but PHP won't let us do that cause PHP sucks
		return array(
			'settings' => array(
				'rights' => array( 'protect' ),
				'default' => true,
				'hidden' => false,
				'shared' => true,
				'skins' => array( 'monobook', 'vector' ),
				'category' => 'foobar',
			),
			'module' => array(
				'scripts' => array( 'Foo.js', 'Bar.js' ),
				'styles' => array( 'Foo.css' ),
				'messages' => array( 'february', 'january' ),
				'dependencies' => array( 'jquery.ui.button' ),
				'position' => 'top',
			)
		);
	}

	public function testGetters() {
		$data = self::getBoilerplateData();
		$now = wfTimestampNow();
		$g = new Gadget( 'GadgetTest', LocalGadgetRepo::singleton() );
		$g->setSettings( $data['settings'] );
		$g->setModuleData( $data['module'] );
		$g->setTimestamp( $now );

		$this->assertEquals( $data, $g->getMetadata(), 'getMetadata' );
		$this->assertEquals( 'GadgetTest', $g->getId(), 'getId' );
		$this->assertEquals( LocalGadgetRepo::singleton(), $g->getRepo(), 'getRepo' );
		$this->assertEquals( $now, $g->getTimestamp(), 'getTimestamp' );
		$this->assertEquals( $data['settings']['category'], $g->getCategory(), 'getCategory' );
		$this->assertEquals( $data['settings']['default'], $g->isEnabledByDefault(), 'isEnabledByDefault' );
		$this->assertEquals( $data['settings']['rights'], $g->getRequiredRights(), 'getRequiredRights' );
		$this->assertEquals( $data['settings']['hidden'], $g->isHidden(), 'isHidden' );
		$this->assertEquals( $data['settings']['shared'], $g->isShared(), 'isShared' );
		$this->assertEquals( $data['settings']['skins'], $g->getSkins(), 'getSkins' );
		$this->assertEquals( $data['module']['scripts'], $g->getScripts(), 'getScripts' );
		$this->assertEquals( $data['module']['styles'], $g->getStyles(), 'getStyles' );
		$this->assertEquals( $data['module']['dependencies'], $g->getDependencies(), 'getDependencies' );
	}

	/**
	 * @covers Gadget::getJSON
	 */
	public function testGetJSON() {
		$data = self::getBoilerplateData();
		$g = new Gadget( 'GadgetTest', LocalGadgetRepo::singleton() );
		$g->setSettings( $data['settings'] );
		$g->setModuleData( $data['module'] );
		$this->assertEquals( FormatJson::encode( $data ), $g->getJSON(), 'JSON round-trips cleanly' );

		$oldData = $data;
		$data['settings']['skins'] = array_reverse( $data['settings']['skins'] );
		$data['module']['messages'] = array_reverse( $data['module']['messages'] );
		$g = new Gadget( 'GadgetTest', LocalGadgetRepo::singleton() );
		$g->setSettings( $data['settings'] );
		$g->setModuleData( $data['module'] );
		$this->assertEquals( FormatJson::encode( $oldData ), $g->getJSON(), 'getJSON returns sorted arrays' );

		$data['module']['messages'][] = 'january';
		$data['settings']['skins'][] = 'vector';
		$data['settings']['skins'][] = 'vector';
		$g = new Gadget( 'GadgetTest', LocalGadgetRepo::singleton() );
		$g->setSettings( $data['settings'] );
		$g->setModuleData( $data['module'] );
		$this->assertEquals( FormatJson::encode( $oldData ), $g->getJSON(), 'getJSON removes duplicates from arrays' );
	}

	/**
	 * @covers Gadget::getTitleMessageKey
	 * @covers Gadget::getDescriptionMessageKey
	 */
	public function testMessageFunctions() {
		global $wgLang;

		$g = new Gadget( 'gadgettest1', LocalGadgetRepo::singleton() );

		$this->assertEquals( 'gadget-gadgettest1-title', $g->getTitleMessageKey(), 'getTitleMessageKey' );
		$this->assertEquals( 'gadget-gadgettest1-desc', $g->getDescriptionMessageKey(), 'getDescriptionMessageKey' );

		// Make sure the gadget-gadgettest1-{title,desc} messages exist
		// In the test environment, the MessageCache is disabled because
		// $wgUseDatabaseMessages is set to false. Temporarily enable it
		// so we can write messages to the DB and use them.
		MessageCache::singleton()->enable();

		$titleMsgTitle = Title::newFromText( 'MediaWiki:gadget-gadgettest1-title' );
		$descMsgTitle = Title::newFromText( 'MediaWiki:gadget-gadgettest1-desc' );
		if ( !$titleMsgTitle->exists() ) {
			ParserTest::addArticle( $titleMsgTitle->getPrefixedText(), 'Gadget test 1', __LINE__ );
		}
		if ( !$descMsgTitle->exists() ) {
			ParserTest::addArticle( $descMsgTitle->getPrefixedText(), 'Description of gadget test 1', __LINE__ );
		}
		$this->assertEquals( wfMessage( 'gadget-gadgettest1-title' )->plain(), $g->getTitleMessage(), 'getTitleMessage for existing message' );
		$this->assertEquals( wfMessage( 'gadget-gadgettest1-desc' )->plain(), $g->getDescriptionMessage(), 'getDescriptionMessage for existing message' );

		$g = new Gadget( 'gadgettest2', LocalGadgetRepo::singleton() );
		$titleMsgTitle = Title::newFromText( 'MediaWiki:gadget-gadgettest2-title' );
		$descMsgTitle = Title::newFromText( 'MediaWiki:gadget-gadgettest2-desc' );
		if ( !$titleMsgTitle->exists() ) {
			$page = WikiPage::factory( $titleMsgTitle );
			$page->doDeleteArticle( 'Deleting to make way for test' );
		}
		if ( !$descMsgTitle->exists() ) {
			$page = WikiPage::factory( $descMsgTitle );
			$page->doDeleteArticle( 'Deleting to make way for test' );
		}
		$this->assertEquals( $wgLang->ucfirst( 'gadgettest2' ), $g->getTitleMessage(), 'getTitleMessage for nonexistent message' );
		$this->assertEquals( '', $g->getDescriptionMessage(), 'getDescriptionMessage for nonexistent message' );

		MessageCache::singleton()->disable();

	}

	/**
	 * @covers Gadget::getModule
	 */
	public function testGetModule() {
		$data = self::getBoilerplateData();
		$g = new Gadget( 'GadgetTest', LocalGadgetRepo::singleton() );
		$g->setSettings( $data['settings'] );
		$g->setModuleData( $data['module'] );

		$m = $g->getModule();
		$pages = array(
			'Gadget:Foo.js' => array( 'type' => 'script' ),
			'Gadget:Bar.js' => array( 'type' => 'script' ),
			'Gadget:Foo.css' => array( 'type' => 'style' ),
		);

		$this->assertEquals( 'ext.gadget.GadgetTest', $g->getModuleName(), 'getModuleName' );
		$this->assertEquals( $g->getDependencies(), $m->getDependencies(), 'getDependencies' );
		$this->assertEquals( $data['module']['messages'], $m->getMessages(), 'getMessages' );
		$this->assertEquals( $data['module']['position'], $m->getPosition(), 'getPosition' );
		$this->assertEquals( LocalGadgetRepo::singleton()->getSource(), $m->getSource(), 'getSource' );
		$this->assertEquals( $pages, $m->getPages( ResourceLoaderContext::newDummyContext() ), 'getPages' );
	}

	/**
	 * @covers Gadget::isEnabledByDefault
	 * @covers Gadget::isEnabledForUser
	 */
	public function testIsEnabledForUser() {
		$defaultOff = self::buildPropertiesArray( array( 'settings' => array( 'default' => false ) ) );
		$defaultOn = self::buildPropertiesArray( array( 'settings' => array( 'default' => true ) ) );
		$gOff = new Gadget( 'GadgetTestOffByDefault', LocalGadgetRepo::singleton() );
		$gOff->setSettings( $defaultOff['settings'] );
		$gOff->setModuleData( $defaultOff['module'] );

		$gOn = new Gadget( 'GadgetTestOnByDefault', LocalGadgetRepo::singleton() );
		$gOn->setSettings( $defaultOn['settings'] );
		$gOn->setModuleData( $defaultOn['module'] );

		$user = new User;

		$this->assertFalse( $gOff->isEnabledByDefault(), 'isEnabledByDefault for gOff' );
		$this->assertTrue( $gOn->isEnabledByDefault(), 'isEnabledByDefault for gOn' );
		$this->assertFalse( $gOff->isEnabledForUser( $user ), 'isEnabledForUser for gOff with default pref' );
		$this->assertTrue( $gOn->isEnabledForUser( $user ), 'isEnabledForUser for gOn with default pref' );

		$user->setOption( 'gadget-GadgetTestOffByDefault', 0 );
		$this->assertFalse( $gOff->isEnabledForUser( $user ), 'isEnabledForUser for gOff with pref off' );
		$user->setOption( 'gadget-GadgetTestOffByDefault', 1 );
		$this->assertTrue( $gOff->isEnabledForUser( $user ), 'isEnabledForUser for gOff with pref on' );

		$user->setOption( 'gadget-GadgetTestOnByDefault', 0 );
		$this->assertFalse( $gOn->isEnabledForUser( $user ), 'isEnabledForUser for gOn with pref off' );
		$user->setOption( 'gadget-GadgetTestOnByDefault', 1 );
		$this->assertTrue( $gOn->isEnabledForUser( $user ), 'isEnabledForUser for gOn with pref on' );
	}

	/**
	 * @covers Gadget::isAllowed
	 */
	public function testIsAllowed() {
		$data = self::buildPropertiesArray( array( 'settings' => array( 'rights' => array( 'foo', 'bar' ) ) ) );
		$g = new Gadget( 'GadgetTest', LocalGadgetRepo::singleton() );
		$g->setSettings( $data['settings'] );
		$g->setModuleData( $data['module'] );

		$user = new User;

		// This is dirty, but I don't know how I would otherwise test this
		$user->mRights = array();
		$this->assertFalse( $g->isAllowed( $user ), 'user has no rights' );
		$user->mRights = array( 'foo' );
		$this->assertFalse( $g->isAllowed( $user ), 'user has foo right only' );
		$user->mRights = array( 'bar' );
		$this->assertFalse( $g->isAllowed( $user ), 'user has bar right only' );
		$user->mRights = array( 'foo', 'bar' );
		$this->assertTrue( $g->isAllowed( $user ), 'user has both foo and bar rights' );
	}

	/**
	 * @covers Gadget::supportsSkin
	 */
	public function testSupportsSkin() {
		$data = self::buildPropertiesArray( array( 'settings' => array( 'skins' => array( 'monobook', 'modern' ) ) ) );
		$g = new Gadget( 'GadgetTest', LocalGadgetRepo::singleton() );
		$g->setSettings( $data['settings'] );
		$g->setModuleData( $data['module'] );

		$this->assertTrue( $g->supportsSkin( 'monobook' ), 'supportsSkin() returns true for skins enumerated in skins list (1)' );
		$this->assertTrue( $g->supportsSkin( 'modern' ), 'supportsSkin() returns true for skins enumerated in skins list (2)' );
		$this->assertFalse( $g->supportsSkin( 'vector' ), 'supportsSkin() returns false for skin not listed in skins list' );
		$this->assertFalse( $g->supportsSkin( 'viosrtubulviurbhujvwkctuljvilubhyjvca' ), 'supportsSkin() returns false for nonexistent skin' );

		$data2 = self::buildPropertiesArray( array( 'settings' => array( 'skins' => true ) ) );
		$g2 = new Gadget( 'GadgetTest', LocalGadgetRepo::singleton() );
		$g2->setSettings( $data2['settings'] );
		$g2->setModuleData( $data2['module'] );

		$this->assertTrue( $g2->supportsSkin( 'monobook' ), 'supportsSkin() returns true for all skins if skins=true (1)' );
		$this->assertTrue( $g2->supportsSkin( 'modern' ), 'supportsSkin() returns true for all skins if skins=true (2)' );
		$this->assertTrue( $g2->supportsSkin( 'vector' ), 'supportsSkin() returns true for all skins if skins=true (3)' );
		$this->assertTrue( $g2->supportsSkin( 'viosrtubulviurbhujvwkctuljvilubhyjvca' ), 'supportsSkin() returns true for nonexistent skin if skins=true' );

		$data3 = self::buildPropertiesArray( array( 'settings' => array( 'skins' => array() ) ) );
		$g3 = new Gadget( 'GadgetTest', LocalGadgetRepo::singleton() );
		$g3->setSettings( $data3['settings'] );
		$g3->setModuleData( $data3['module'] );

		$this->assertFalse( $g3->supportsSkin( 'monobook' ), 'supportsSkin() returns false for all skins if skins list is empty (1)' );
		$this->assertFalse( $g3->supportsSkin( 'modern' ), 'supportsSkin() returns false for all skins if skins list is empty (2)' );
		$this->assertFalse( $g3->supportsSkin( 'vector' ), 'supportsSkin() returns false for all skins if skins list is empty (3)' );
		$this->assertFalse( $g3->supportsSkin( 'viosrtubulviurbhujvwkctuljvilubhyjvca' ), 'supportsSkin() returns false for nonexistent skin if skins list is empty' );
	}
}

