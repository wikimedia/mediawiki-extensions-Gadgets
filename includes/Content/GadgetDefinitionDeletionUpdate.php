<?php
/**
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

namespace MediaWiki\Extension\Gadgets\Content;

use DataUpdate;
use MediaWiki\Linker\LinkTarget;
use MediaWiki\MediaWikiServices;

/**
 * DataUpdate to run whenever a page in the Gadget definition
 * is deleted.
 */
class GadgetDefinitionDeletionUpdate extends DataUpdate {
	/**
	 * Page that was deleted
	 * @var LinkTarget
	 */
	private $target;

	public function __construct( LinkTarget $target ) {
		$this->target = $target;
	}

	public function doUpdate() {
		$gadgetRepo = MediaWikiServices::getInstance()->getService( 'GadgetsRepo' );
		$gadgetRepo->handlePageUpdate( $this->target );
	}
}
