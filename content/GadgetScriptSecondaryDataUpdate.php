<?php

/**
 * DeletionUpdate for GadgetCssContent and GadgetJsContent
 */
class GadgetScriptSecondaryDataUpdate extends DataUpdate {

	/**
	 * @param Title $title
	 * @param string $type either "css" or "js"
	 * @throws InvalidArgumentException
	 */
	public function  __construct( Title $title, $type ) {
		$this->title = $title;

		if ( !in_array( $type, array( 'css', 'js' ) ) ) {
			throw new InvalidArgumentException( "$type is not a valid Gadget script type" );
		}
		$this->type = $type;
	}


	public function doUpdate() {
		wfGetDB( DB_MASTER )->insert(
			'gadgetpagelist',
			array(
				'gpl_title' => $this->title->getDBkey(),
				'gpl_namespace' => $this->title->getNamespace(),
				'gpl_extension' => $this->type,
			),
			__METHOD__,
			array( 'IGNORE' )
		);
	}
}