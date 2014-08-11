<?php

/**
 * DeletionUpdate for GadgetCssContent and GadgetJsContent
 */
class GadgetScriptSecondaryDataUpdate extends DataUpdate {

	public function __construct( Title $title ) {
		$this->title = $title;
	}

	public function doUpdate() {
		GadgetPageList::updatePageStatus( $this->title );
	}
}