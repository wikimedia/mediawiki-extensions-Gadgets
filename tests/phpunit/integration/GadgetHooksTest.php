<?php

use MediaWiki\Extension\Gadgets\Gadget;
use MediaWiki\Extension\Gadgets\Hooks as GadgetHooks;
use MediaWiki\Extension\Gadgets\MediaWikiGadgetsDefinitionRepo;
use MediaWiki\Extension\Gadgets\StaticGadgetRepo;
use Wikimedia\TestingAccessWrapper;

/**
 * @group Gadgets
 * @group Database
 */
class GadgetHooksTest extends MediaWikiIntegrationTestCase {
	use GadgetTestTrait;

	/**
	 * @covers \MediaWiki\Extension\Gadgets\Hooks::onBeforePageDisplay
	 * @covers \MediaWiki\Extension\Gadgets\GadgetLoadConditions
	 */
	public function testDefaultGadget() {
		$services = $this->getServiceContainer();
		$repo = new StaticGadgetRepo( [
			'g1' => new Gadget( [ 'name' => 'g1', 'onByDefault' => true, 'styles' => 'test.css' ] ),
		] );
		$hooks = new GadgetHooks( $repo, $services->getUserOptionsLookup(), null );
		$out = new OutputPage( RequestContext::getMain() );
		$out->setTitle( Title::newMainPage() );
		$skin = $this->createMock( Skin::class );
		$hooks->onBeforePageDisplay( $out, $skin );
		$this->assertArrayEquals( [ 'ext.gadget.g1' ], $out->getModuleStyles() );
	}

	/**
	 * @covers \MediaWiki\Extension\Gadgets\Hooks::onBeforePageDisplay
	 * @covers \MediaWiki\Extension\Gadgets\GadgetLoadConditions
	 */
	public function testEnabledGadget() {
		$services = $this->getServiceContainer();
		$repo = new StaticGadgetRepo( [
			'g1' => new Gadget( [ 'name' => 'g1', 'scripts' => 'test.js', 'resourceLoaded' => true ] ),
		] );
		$hooks = new GadgetHooks( $repo, $services->getUserOptionsLookup(), null );
		$context = RequestContext::getMain();
		$out = new OutputPage( $context );
		$out->setTitle( Title::newMainPage() );
		$user = $this->getTestUser()->getUser();
		$context->setUser( $user );
		$skin = $this->createMock( Skin::class );

		$hooks->onBeforePageDisplay( $out, $skin );
		$this->assertArrayEquals( [], $out->getModules() );

		$services->getUserOptionsManager()->setOption( $user, 'gadget-g1', true );
		$services->getUserOptionsManager()->saveOptions( $user );
		$hooks->onBeforePageDisplay( $out, $skin );
		$this->assertArrayEquals( [ 'ext.gadget.g1' ], $out->getModules() );
	}

	/**
	 * @covers \MediaWiki\Extension\Gadgets\Hooks::onUserGetDefaultOptions
	 */
	public function testDefaultUserOptions() {
		$repo = new StaticGadgetRepo( [
			'g1' => new Gadget( [ 'name' => 'g1', 'styles' => 'test.css', 'onByDefault' => true ] ),
			'g2' => new Gadget( [ 'name' => 'g2', 'styles' => 'test.css' ] ),
			'g3' => new Gadget( [ 'name' => 'g3', 'styles' => 'test.css', 'hidden' => true ] ),
		] );
		$this->setService( 'GadgetsRepo', $repo );
		$optionsLookup = $this->getServiceContainer()->getUserOptionsLookup();
		$user = $this->getTestUser()->getUser();
		$this->assertSame( 1, $optionsLookup->getOption( $user, 'gadget-g1' ) );
		$this->assertSame( 0, $optionsLookup->getOption( $user, 'gadget-g2' ) );
		$this->assertNull( $optionsLookup->getOption( $user, 'gadget-g3' ) );
	}

	/**
	 * @covers \MediaWiki\Extension\Gadgets\Gadget
	 * @covers \MediaWiki\Extension\Gadgets\Hooks::onGetPreferences
	 * @covers \MediaWiki\Extension\Gadgets\GadgetRepo
	 * @covers \MediaWiki\Extension\Gadgets\MediaWikiGadgetsDefinitionRepo
	 */
	public function testPreferences() {
		$prefs = [];
		$testRightAllowed = 'gadget-test-right-allowed';
		$testRightNotAllowed = 'gadget-test-right-notallowed';
		$services = $this->getServiceContainer();
		$repo = TestingAccessWrapper::newFromObject( new MediaWikiGadgetsDefinitionRepo(
			$services->getMainWANObjectCache(), $services->getRevisionLookup()
		) );

		$gadgetsDef = <<<EOT
* foo | foo.js
==keep-section1==
* bar| bar.js
==remove-section==
* baz [rights=$testRightNotAllowed] |baz.js
==keep-section2==
* quux [rights=$testRightAllowed] | quux.js
EOT;

		$hooks = new GadgetHooks( $repo->object, $services->getUserOptionsLookup(), null );

		/** @var MediaWikiGadgetsDefinitionRepo $repo */
		$gadgets = $repo->fetchStructuredList( $gadgetsDef );
		$this->assertGreaterThanOrEqual( 2, count( $gadgets ), "Gadget list parsed" );

		$repo->definitions = $gadgets;

		$user = $this->createMock( User::class );
		$user->method( 'isAllowedAll' )
			->willReturnCallback( static function ( ...$rights ) use ( $testRightNotAllowed ): bool {
				return !in_array( $testRightNotAllowed, $rights, true );
			} );
		$hooks->onGetPreferences( $user, $prefs );

		$this->assertEquals( 'check', $prefs['gadget-bar']['type'] );
		$this->assertEquals( 'api', $prefs['gadget-baz']['type'],
			'Must not show unavailable gadgets' );
		$this->assertEquals( 'gadgets/gadget-section-keep-section2', $prefs['gadget-quux']['section'] );
	}
}
