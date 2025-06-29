<?php

use MediaWiki\Api\ApiMain;
use MediaWiki\Context\RequestContext;
use MediaWiki\Extension\Gadgets\Gadget;
use MediaWiki\Extension\Gadgets\Special\SpecialGadgetUsage;
use MediaWiki\Extension\Gadgets\StaticGadgetRepo;
use MediaWiki\MainConfigNames;
use MediaWiki\Request\FauxRequest;
use Wikimedia\Parsoid\Utils\DOMUtils;

/**
 * @covers \MediaWiki\Extension\Gadgets\Special\SpecialGadgetUsage
 * @group Gadgets
 * @group Database
 */
class SpecialGadgetUsageTest extends MediaWikiIntegrationTestCase {

	private function newPage( array $gadgets = [] ): SpecialGadgetUsage {
		$page = new SpecialGadgetUsage(
			new StaticGadgetRepo( $gadgets ),
			$this->getServiceContainer()->getConnectionProvider()
		);
		$context = new RequestContext();
		$context->setLanguage( 'en' );
		$page->setContext( $context );
		return $page;
	}

	private function newPageWithGadget( string $name, bool $isDefault ): SpecialGadgetUsage {
		return $this->newPage( [
			$name => new Gadget( [ 'name' => $name, 'onByDefault' => $isDefault ] ),
		] );
	}

	private function assertGadgetLink( string $name, string $html ): void {
		$doc = DOMUtils::parseHTML( '<table>' . $html . '</table>' );
		$links = $doc->getElementsByTagName( 'a' );
		$this->assertCount( 1, $links );
		$link = $links->item( 0 );
		$this->assertSame( $name, $link->textContent );
		$this->assertStringContainsString( 'Special:Gadgets#gadget-', rawurldecode( $link->getAttribute( 'href' ) ) );
	}

	private function newResult( string $name, array $counts ): stdClass {
		return (object)[
			'title' => json_encode( [ 'name' => 'gadget-' . $name ] + $counts ),
			// Counts must come from the title payload, not the other cache columns.
			'value' => 9876,
			'namespace' => NS_MEDIAWIKI,
		];
	}

	/** @dataProvider provideApiGadgetNames */
	public function testApiTitlePreservesJson( string $name, bool $cached ): void {
		$this->overrideConfigValues( [
			MainConfigNames::MiserMode => $cached,
			MainConfigNames::ExternalQuerySources => [],
		] );
		$this->setService( 'GadgetsRepo', new StaticGadgetRepo( [
			$name => new Gadget( [ 'name' => $name ] ),
		] ) );
		$user = $this->getTestUser()->getUser();
		$userOptionsManager = $this->getServiceContainer()->getUserOptionsManager();
		$userOptionsManager->setOption( $user, 'gadget-' . $name, true );
		$userOptionsManager->saveOptions( $user );

		if ( $cached ) {
			$page = $this->getServiceContainer()->getSpecialPageFactory()->getPage( 'GadgetUsage' );
			$this->assertSame( 1, $page->recache( 1000 ) );
		}

		$context = new RequestContext();
		$context->setUser( $user );
		$context->setLanguage( 'en' );
		$context->setRequest( new FauxRequest( [
			'action' => 'query',
			'list' => 'querypage',
			'qppage' => 'GadgetUsage',
		] ) );
		$api = new ApiMain( $context );
		$api->execute();
		$data = $api->getResult()->getResultData( null, [ 'Strip' => 'all' ] );
		$queryPage = $data['query']['querypage'];
		$this->assertSame( $cached, $queryPage['cached'] ?? false );
		$this->assertCount( 1, $queryPage['results'] );
		$result = $queryPage['results'][0];
		$this->assertSame( NS_MAIN, $result['ns'] );
		$this->assertSame( [
			'name' => 'gadget-' . $name,
			'on' => 1,
			'off' => 0,
			'activeOn' => 0,
			'activeOff' => 0,
		], json_decode( $result['title'], true, 512, JSON_THROW_ON_ERROR ) );
	}

	public static function provideApiGadgetNames(): iterable {
		foreach ( [ 'Navigation_popups', 'Multiple__underscores', 'Literal\\u005f_underscore' ] as $name ) {
			yield "$name live" => [ $name, false ];
			yield "$name cached" => [ $name, true ];
		}
	}

	public function testFormatNonDefaultGadget(): void {
		$page = $this->newPageWithGadget( 'X1', false );

		$result = $this->newResult( 'X1', [ 'on' => 1234, 'off' => 99, 'activeOn' => 56, 'activeOff' => 7 ] );
		$html = $page->formatResult( null, $result );
		$this->assertStringContainsString( '<td>1,234</td><td>56</td>', $html );
		$this->assertGadgetLink( 'X1', $html );
	}

	/** @dataProvider provideOptOutCounts */
	public function testFormatDefaultGadget( int $off, int $activeOff, string $expectedCells ): void {
		$page = $this->newPageWithGadget( 'DefaultGadget', true );

		$result = $this->newResult( 'DefaultGadget', [
			'on' => 1234, 'off' => $off, 'activeOn' => 56, 'activeOff' => $activeOff,
		] );
		$html = $page->formatResult( null, $result );
		$this->assertStringContainsString( $expectedCells, $html );
		$this->assertGadgetLink( 'DefaultGadget', $html );
	}

	public static function provideOptOutCounts(): iterable {
		yield 'total and active opt-outs' => [
			12, 3,
			'<td data-sort-value="9999999988">Default (12 opted out)</td>' .
			'<td data-sort-value="9999999997">Default (3 opted out)</td>',
		];
		yield 'no active opt-outs' => [
			12, 0,
			'<td data-sort-value="9999999988">Default (12 opted out)</td>' .
			'<td data-sort-value="10000000000">Default (0 opted out)</td>',
		];
		yield 'zero counts are valid' => [
			0, 0,
			'<td data-sort-value="10000000000">Default (0 opted out)</td>' .
			'<td data-sort-value="10000000000">Default (0 opted out)</td>',
		];
	}

	/** @dataProvider provideInvalidTitles */
	public function testSkipInvalidTitle( string $title ): void {
		$page = $this->newPageWithGadget( 'X1', false );
		$this->assertFalse( $page->formatResult( null, (object)[ 'title' => $title ] ) );
	}

	public static function provideInvalidTitles(): iterable {
		yield 'empty title' => [ '' ];
		yield 'legacy cache format' => [ 'gadget-X1' ];
		yield 'truncated JSON' => [ '{"name":"gadget-X1"' ];
		yield 'malformed JSON' => [ '{not JSON}' ];
		yield 'empty object' => [ '{}' ];
		yield 'missing fields' => [ json_encode( [ 'name' => 'gadget-X1', 'on' => 10 ] ) ];
		yield 'null field' => [ json_encode( [ 'name' => 'gadget-X1', 'on' => null, 'off' => 2, 'activeOn' => 5,
			'activeOff' => 1 ] ) ];
	}

	public function testSkipDeletedGadget(): void {
		$page = $this->newPage();
		$result = $this->newResult( 'DeletedGadget', [ 'on' => 10, 'off' => 2, 'activeOn' => 5, 'activeOff' => 1 ] );
		$this->assertFalse( $page->formatResult( null, $result ) );
	}
}
