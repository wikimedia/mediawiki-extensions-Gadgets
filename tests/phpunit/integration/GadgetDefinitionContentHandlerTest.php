<?php

namespace MediaWiki\Extension\Gadgets\Tests;

use MediaWiki\Extension\Gadgets\Content\GadgetDefinitionContent;
use MediaWiki\Parser\ParserOutputLinkTypes;
use MediaWiki\Revision\RevisionRecord;
use MediaWiki\Revision\SlotRecord;
use MediaWiki\Title\Title;
use MediaWikiIntegrationTestCase;

/**
 * @covers \MediaWiki\Extension\Gadgets\Content\GadgetDefinitionContent
 * @covers \MediaWiki\Extension\Gadgets\Content\GadgetDefinitionContentHandler
 * @group Gadgets
 * @group Database
 */
class GadgetDefinitionContentHandlerTest extends MediaWikiIntegrationTestCase {

	public function testHandler() {
		$status = $this->editPage( 'MediaWiki:Gadgets/X1.json', '{}' );
		/** @var RevisionRecord $rev */
		$rev = $status->getValue()['revision-record'];
		$revText = $rev->getContent( SlotRecord::MAIN )->serialize();
		$handler = $this->getServiceContainer()->getContentHandlerFactory()->getContentHandler( 'GadgetDefinition' );
		$this->assertEquals( $handler->makeEmptyContent()->serialize(), $revText );
	}

	private function getLinks( string $json ): array {
		$content = new GadgetDefinitionContent( $json );
		$parserOutput = $this->getServiceContainer()->getContentRenderer()->getParserOutput(
			$content,
			Title::makeTitle( NS_MEDIAWIKI, 'Gadgets/X1.json' )
		);
		return $parserOutput->getLinkList( ParserOutputLinkTypes::LOCAL );
	}

	public function testLinksForValidContent() {
		$json = json_encode( [
			'settings' => [ 'categories' => [ 'Foo' ] ],
			'module' => [ 'pages' => [ 'X1.js' ], 'messages' => [ 'Bar' ] ],
		] );
		$links = array_map(
			static fn ( $link ) => $link['link']->getDBkey(),
			$this->getLinks( $json )
		);
		$this->assertContains( 'Gadget-X1.js', $links );
		$this->assertContains( 'Bar', $links );
		$this->assertContains( 'Foo', $links );
	}

	public static function provideInvalidContent() {
		return [
			'module.pages is a number' => [ '{"module":{"pages":5}}' ],
			'module.pages has an object' => [ '{"module":{"pages":[{"a":1}]}}' ],
			'module.peers is a number' => [ '{"module":{"peers":5}}' ],
			'module.dependencies has an object' => [ '{"module":{"dependencies":[{"a":1}]}}' ],
			'module.messages is a number' => [ '{"module":{"messages":5}}' ],
			'settings.categories is a number' => [ '{"settings":{"categories":5}}' ],
			'settings.section is an object' => [ '{"settings":{"section":{"a":1}}}' ],
		];
	}

	/**
	 * @dataProvider provideInvalidContent
	 */
	public function testInvalidContent( string $json ) {
		$this->assertFalse( ( new GadgetDefinitionContent( $json ) )->isValid() );
		// Must not emit warnings or throw (T428773)
		$this->assertSame( [], $this->getLinks( $json ) );
	}
}
