<?php
/**
 * Created on July 31st, 2011
 * API for Gadgets extension
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
 */

class ApiGadgetManager extends ApiBase {
	public function __construct( $main, $action ) {
		parent::__construct( $main, $action );
	}
	
	public function execute() {
		global $wgUser;
		$params = $this->extractRequestParams();
		
		$op = $params['op'];
		if ( !$wgUser->isAllowed( "gadgets-manager-$op" ) ) {
			$this->dieUsage( "You are not allowed to $op gadgets", 'permissiondenied' );
		}
		
		if ( $op === 'modify' ) {
			if ( $params['edittimestamp'] === null ) {
				$this->dieUsageMsg( array( 'missingparam', 'edittimestamp' ) );
			}
		}
		
		$repo = new LocalGadgetRepo( array() );
		if ( $op === 'create' || $op === 'modify' ) {
			if ( $params['json'] === null ) {
				$this->dieUsageMsg( array( 'missingparam', 'json' ) );
			}
			$json = FormatJson::decode( $params['json'], true );
			if ( $json === null ) {
				$this->dieUsage( 'Invalid JSON specified for json parameter', 'invalidjson' );
			}
			if ( !Gadget::isValidPropertiesArray( $json ) ) {
				$this->dieUsage( 'Invalid properties array specified for json parameter', 'invalidproperties' );
			}
			
			// FIXME: Passing lasttimestamp into the constructor like this is a bit hacky
			$gadget = new Gadget( $params['id'], $repo, $json, $params['edittimestamp'] );
		}
		
		if ( $op === 'create' ) {
			$status = $repo->addGadget( $gadget );
		} else if ( $op === 'modify' ) {
			$status = $repo->modifyGadget( $gadget );
		} else if ( $op === 'delete' ) {
			$status = $repo->deleteGadget( $params['id'] );
		}
		
		if ( !$status->isGood() ) {
			$errors = $status->getErrorsArray();
			$this->dieUsageMsg( $errors[0] ); // TODO: Actually register all the error messages in ApiBase::$messageMap somehow
		}
		
		$r = array( 'result' => 'success', 'id' => $params['id'], 'op' => $op );
		$this->getResult()->addValue( null, $this->getModuleName(), $r );
	}
	
	public function mustBePosted() {
		return true;
	}

	public function isWriteMode() {
		return true;
	}
	
	public function getAllowedParams() {
		return array(
			'op' => array(
				ApiBase::PARAM_TYPE => array( 'create', 'modify', 'delete' ),
				ApiBase::PARAM_REQUIRED => true
			),
			'id' => array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			),
			'json' => array(
				ApiBase::PARAM_TYPE => 'string'
			),
			'token' => null,
			'edittimestamp' => array(
				ApiBase::PARAM_TYPE => 'timestamp'
			)
		);
	}

	public function getParamDescription() {
		return array(
			'op' => 'Operation to carry out',
			'id' => 'Gadget id',
			'json' => 'If op=create or op=modify, JSON blob with the new gadget metadata. Ignored if op=delete',
			'edittimestamp' => 'If op=modify, the last modified timestamp of the gadget metadata, exactly as given by list=gadgets',
			'token' => 'Edit token',
		);
	}

	public function getDescription() {
		return 'Create, modify and delete gadgets.';
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			// TODO
		) );
	}

	public function needsToken() {
		return true;
	}

	public function getTokenSalt() {
		return '';
	}

	protected function getExamples() {
		return array(
		);
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
}
