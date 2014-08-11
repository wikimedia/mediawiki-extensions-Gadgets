<?php

/**
 * DeletionUpdate for GadgetCssContent and GadgetJsContent
 */
class GadgetScriptDeletionUpdate extends DataUpdate {

	public function __construct( Title $title ) {
		$this->title = $title;
	}

	public function doUpdate() {
		GadgetPageList::delete( $this->title );
	}
}
