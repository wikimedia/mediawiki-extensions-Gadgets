<?php

/**
 * DeletionUpdate for GadgetCssContent and GadgetJsContent
 */
class GadgetScriptDeletionUpdate extends DataUpdate {

	public function __construct( Title $title ) {
		$this->title = $title;
	}

	public function doUpdate() {
		wfGetDB( DB_MASTER )->delete(
			'gadgetpagelist',
			array(
				'gpl_title' => $this->title->getDBkey(),
				'gpl_namespace' => $this->title->getNamespace(),
			),
			__METHOD__
		);
	}
}
