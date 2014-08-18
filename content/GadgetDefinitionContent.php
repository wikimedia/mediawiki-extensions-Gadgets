<?php
/**
 * Copyright 2014
 *
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 */

class GadgetDefinitionContent extends JSONContent {

	public function __construct( $text ) {
		parent::__construct( $text, 'GadgetDefinition' );
	}

	public function isValid() {
		if ( !parent::isValid() ) {
			return false;
		}

		// @todo we should figure out how to expose more specific error messages
		$status = Gadget::validatePropertiesArray( $this->getJsonData(), 'tolerateMissing' );
		return $status->isOK();
	}

	public function getSettings() {
		$json = $this->getJsonData();
		return isset( $json['settings'] ) ? $json['settings'] : array();
	}

	public function getModuleData() {
		$json = $this->getJsonData();
		return isset( $json['module'] ) ? $json['module'] : array();
	}

	public function preSaveTransform( Title $title, User $user, ParserOptions $popts ) {
		$gadget = new Gadget(
			$title->getText(),
			LocalGadgetRepo::singleton()
		);
		$gadget->setSettings( $this->getSettings() );
		$gadget->setModuleData( $this->getModuleData() );

		return new GadgetDefinitionContent( $gadget->getPrettyJSON() );
	}

	/**
	 * @param WikiPage $page
	 * @param ParserOutput $parserOutput
	 * @return DataUpdate[]
	 */
	public function getDeletionUpdates( WikiPage $page, ParserOutput $parserOutput = null ) {
		return array_merge(
			parent::getDeletionUpdates( $page, $parserOutput ),
			array( new GadgetDefinitionDeletionUpdate( $page->getTitle()->getText() ) )
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
			array( new GadgetDefinitionSecondaryDataUpdate( $title->getText(), $this ) )
		);
	}
}
