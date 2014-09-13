<?php

class GadgetJsContent extends JavaScriptContent {

	public function __construct( $text ) {
		parent::__construct( $text, 'GadgetJs' );
	}

	/**
	 * @param WikiPage $page
	 * @param ParserOutput $parserOutput
	 * @return DataUpdate[]
	 */
	public function getDeletionUpdates( WikiPage $page, ParserOutput $parserOutput = null ) {
		return array_merge(
			parent::getDeletionUpdates( $page, $parserOutput ),
			array( new GadgetScriptDeletionUpdate( $page->getTitle() ) )
		);
	}

	/**
	 * @param Title $title
	 * @param Content $old
	 * @param bool $recursive
	 * @param ParserOutput $parserOutput
	 * @return DataUpdate[]
	 */
	public function getSecondaryDataUpdates( Title $title, Content $old = null,
		$recursive = true, ParserOutput $parserOutput = null
	) {
		return array_merge(
			parent::getSecondaryDataUpdates( $title, $old, $recursive, $parserOutput ),
			array( new GadgetScriptSecondaryDataUpdate( $title, 'js' ) )
		);
	}
}
