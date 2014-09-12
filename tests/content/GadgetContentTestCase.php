<?php

class GadgetContentTestCase extends MediaWikiLangTestCase {

	protected function getMockWikiPage() {
		$page = $this->getMockBuilder( 'WikiPage' )->disableOriginalConstructor()
			->getMock();
		$page->expects( $this->any() )->method( 'exists' )->will( $this->returnValue( true ) );
		$page->expects( $this->any() )->method( 'getTitle' )->will( $this->returnValue( Title::newMainPage() ) );
		return $page;
	}
}
