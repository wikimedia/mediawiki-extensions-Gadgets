<?php

/**
 * @group Gadgets
 */
class GadgetTest extends MediaWikiUnitTestCase {
	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 */
	public function testInvalidLines() {
		$repo = new MediaWikiGadgetsDefinitionRepo();
		$this->assertFalse( $repo->newFromDefinition( '', 'misc' ) );
		$this->assertFalse( $repo->newFromDefinition( '<foo|bar>', 'misc' ) );
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget
	 */
	public function testSimpleCases() {
		$g = GadgetTestUtils::makeGadget( '* foo bar| foo.css|foo.js|foo.bar' );
		$this->assertEquals( 'foo_bar', $g->getName() );
		$this->assertEquals( 'ext.gadget.foo_bar', Gadget::getModuleName( $g->getName() ) );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.js' ], $g->getScripts() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.css' ], $g->getStyles() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.js', 'MediaWiki:Gadget-foo.css' ], $g->getScriptsAndStyles() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.js' ], $g->getLegacyScripts() );
		$this->assertFalse( $g->supportsResourceLoader() );
		$this->assertTrue( $g->hasModule() );
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget::supportsResourceLoader
	 * @covers Gadget::getLegacyScripts
	 */
	public function testRLtag() {
		$g = GadgetTestUtils::makeGadget( '*foo [ResourceLoader]|foo.js|foo.css' );
		$this->assertEquals( 'foo', $g->getName() );
		$this->assertTrue( $g->supportsResourceLoader() );
		$this->assertCount( 0, $g->getLegacyScripts() );
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget
	 */
	public function testPackaged() {
		$g = GadgetTestUtils::makeGadget( '* foo bar[ResourceLoader|package]| foo.css|foo.js|foo.bar|foo.json' );
		$this->assertEquals( 'foo_bar', $g->getName() );
		$this->assertEquals( 'ext.gadget.foo_bar', Gadget::getModuleName( $g->getName() ) );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.js' ], $g->getScripts() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.css' ], $g->getStyles() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.json' ], $g->getJSONs() );
		$this->assertEquals( [ 'MediaWiki:Gadget-foo.js', 'MediaWiki:Gadget-foo.css', 'MediaWiki:Gadget-foo.json' ],
			$g->getScriptsAndStyles() );
		$this->assertEquals( [], $g->getLegacyScripts() );
		$this->assertTrue( $g->supportsResourceLoader() );
		$this->assertTrue( $g->hasModule() );
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget::isAllowed
	 */
	public function testIsAllowed() {
		$user = $this->getMockBuilder( User::class )
			->onlyMethods( [ 'isAllowedAll' ] )
			->getMock();
		$user->method( 'isAllowedAll' )
			->willReturnCallback(
				static function ( ...$rights ) {
					return array_diff( $rights, [ 'test' ] ) === [];
				}
			);

		/** @var User $user */
		$gUnset = GadgetTestUtils::makeGadget( '*foo[ResourceLoader]|foo.js' );
		$gAllowed = GadgetTestUtils::makeGadget( '*bar[ResourceLoader|rights=test]|bar.js' );
		$gNotAllowed = GadgetTestUtils::makeGadget( '*baz[ResourceLoader|rights=nope]|baz.js' );
		$this->assertTrue( $gUnset->isAllowed( $user ) );
		$this->assertTrue( $gAllowed->isAllowed( $user ) );
		$this->assertFalse( $gNotAllowed->isAllowed( $user ) );
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget::isSkinSupported
	 */
	public function testSkinsTag() {
		$gUnset = GadgetTestUtils::makeGadget( '*foo[ResourceLoader]|foo.js' );
		$gSkinSupported = GadgetTestUtils::makeGadget( '*bar[ResourceLoader|skins=fallback]|bar.js' );
		$gSkinNotSupported = GadgetTestUtils::makeGadget( '*baz[ResourceLoader|skins=bar]|baz.js' );
		$skin = new SkinFallback();
		$this->assertTrue( $gUnset->isSkinSupported( $skin ) );
		$this->assertTrue( $gSkinSupported->isSkinSupported( $skin ) );
		$this->assertFalse( $gSkinNotSupported->isSkinSupported( $skin ) );
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget::isActionSupported
	 */
	public function testActionsTag() {
		$gUnset = GadgetTestUtils::makeGadget( '*foo[ResourceLoader]|foo.js' );
		$gActionSupported = GadgetTestUtils::makeGadget( '*bar[ResourceLoader|actions=edit]|bar.js' );
		$gActionNotSupported = GadgetTestUtils::makeGadget( '*baz[ResourceLoader|actions=history]|baz.js' );
		$this->assertTrue( $gUnset->isActionSupported( 'edit' ) );
		$this->assertTrue( $gActionSupported->isActionSupported( 'edit' ) );
		$this->assertFalse( $gActionNotSupported->isActionSupported( 'edit' ) );

		// special case
		$this->assertTrue( $gActionSupported->isActionSupported( 'submit' ) );

		$gMultiActions = GadgetTestUtils::makeGadget( '*bar[ResourceLoader|actions=unknown,history]|bar.js' );
		$this->assertTrue( $gMultiActions->isActionSupported( 'history' ) );
		$this->assertFalse( $gMultiActions->isActionSupported( 'view' ) );
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget::getTargets
	 */
	public function testTargets() {
		$g = GadgetTestUtils::makeGadget( '*foo[ResourceLoader]|foo.js' );
		$g2 = GadgetTestUtils::makeGadget( '*bar[ResourceLoader|targets=desktop,mobile]|bar.js' );
		$this->assertEquals( [ 'desktop' ], $g->getTargets() );
		$this->assertEquals( [ 'desktop', 'mobile' ], $g2->getTargets() );
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget::getDependencies
	 */
	public function testDependencies() {
		$g = GadgetTestUtils::makeGadget( '* foo[ResourceLoader|dependencies=jquery.ui]|bar.js' );
		$this->assertEquals( [ 'MediaWiki:Gadget-bar.js' ], $g->getScripts() );
		$this->assertTrue( $g->supportsResourceLoader() );
		$this->assertEquals( [ 'jquery.ui' ], $g->getDependencies() );
	}

	public static function provideGetType() {
		return [
			[
				'Default (mixed)',
				'* foo[ResourceLoader]|bar.css|bar.js',
				'general',
				ResourceLoaderModule::LOAD_GENERAL,
			],
			[
				'Default (styles only)',
				'* foo[ResourceLoader]|bar.css',
				'styles',
				ResourceLoaderModule::LOAD_STYLES,
			],
			[
				'Default (scripts only)',
				'* foo[ResourceLoader]|bar.js',
				'general',
				ResourceLoaderModule::LOAD_GENERAL,
			],
			[
				'Default (styles only with dependencies)',
				'* foo[ResourceLoader|dependencies=jquery.ui]|bar.css',
				'general',
				ResourceLoaderModule::LOAD_GENERAL,
			],
			[
				'Styles type (mixed)',
				'* foo[ResourceLoader|type=styles]|bar.css|bar.js',
				'styles',
				ResourceLoaderModule::LOAD_STYLES,
			],
			[
				'Styles type (styles only)',
				'* foo[ResourceLoader|type=styles]|bar.css',
				'styles',
				ResourceLoaderModule::LOAD_STYLES,
			],
			[
				'Styles type (scripts only)',
				'* foo[ResourceLoader|type=styles]|bar.js',
				'styles',
				ResourceLoaderModule::LOAD_STYLES,
			],
			[
				'General type (mixed)',
				'* foo[ResourceLoader|type=general]|bar.css|bar.js',
				'general',
				ResourceLoaderModule::LOAD_GENERAL,
			],
			[
				'General type (styles only)',
				'* foo[ResourceLoader|type=general]|bar.css',
				'general',
				ResourceLoaderModule::LOAD_GENERAL,
			],
			[
				'General type (scripts only)',
				'* foo[ResourceLoader|type=general]|bar.js',
				'general',
				ResourceLoaderModule::LOAD_GENERAL,
			],
		];
	}

	/**
	 * @dataProvider provideGetType
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget::getType
	 * @covers GadgetResourceLoaderModule::getType
	 */
	public function testType( $message, $definition, $gType, $mType ) {
		$g = GadgetTestUtils::makeGadget( $definition );
		$this->assertEquals( $gType, $g->getType(), "Gadget: $message" );
		$this->assertEquals( $mType, GadgetTestUtils::makeGadgetModule( $g )->getType(), "Module: $message" );
	}

	/**
	 * @covers MediaWikiGadgetsDefinitionRepo::newFromDefinition
	 * @covers Gadget::isHidden
	 */
	public function testIsHidden() {
		$g = GadgetTestUtils::makeGadget( '* foo[hidden]|bar.js' );
		$this->assertTrue( $g->isHidden() );

		$g = GadgetTestUtils::makeGadget( '* foo[ResourceLoader|hidden]|bar.js' );
		$this->assertTrue( $g->isHidden() );

		$g = GadgetTestUtils::makeGadget( '* foo[ResourceLoader]|bar.js' );
		$this->assertFalse( $g->isHidden() );
	}
}
