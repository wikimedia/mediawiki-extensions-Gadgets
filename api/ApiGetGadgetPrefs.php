<?php

/**
 * 
 * API for setting Gadget's preferences
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

class ApiGetGadgetPrefs extends ApiBase {

	public function execute() {
		$user = RequestContext::getMain()->getUser();
		
		$params = $this->extractRequestParams();
		//Check permissions
		if ( !$user->isLoggedIn() ) {
			$this->dieUsage( 'You must be logged-in to get gadget\'s preferences', 'notloggedin' );
		}

		$gadgetName = $params['gadget'];
		
		$gadgets = Gadget::loadList();
		$gadget = $gadgets && isset( $gadgets[$gadgetName] ) ? $gadgets[$gadgetName] : null;
		
		if ( $gadget === null ) {
			$this->dieUsage( 'Gadget not found', 'notfound' );
		}
		
		$prefsDescription = $gadget->getPrefsDescription();
		
		if ( $prefsDescription === null ) {
			$this->dieUsage( 'Gadget ' . $gadget->getName() . ' does not have any preference', 'noprefs' );
		}

		$userPrefs = $gadget->getPrefs();
		
		if ( $userPrefs === null ) {
			throw new MWException( __METHOD__ . ': $userPrefs should not be null.' );
		}
				
		//Add user preferences to preference description
		foreach ( $userPrefs as $pref => $value ) {
			$prefsDescription['fields'][$pref]['value'] = $value;
		}

		$this->getResult()->addValue( null, $this->getModuleName(), $prefsDescription );
	}

	public function getAllowedParams() {
		return array(
			'gadget'   	=> array(
				ApiBase::PARAM_TYPE => 'string',
				ApiBase::PARAM_REQUIRED => true
			)
		);
	}

	public function getParamDescription() {
		return array(
			'gadget'  	=> 'The name of the gadget'
		);
	}

	public function getDescription() {
		return 'Allows user code to get preferences for gadgets, along with preference descriptions and values for currently logged in user';
	}

	public function getPossibleErrors() {
		return array_merge( parent::getPossibleErrors(), array(
			array( 'code' => 'notloggedin', 'info' => 'You must be logged-in to get gadget\'s preferences' ),
			array( 'code' => 'notfound', 'info' => 'Gadget not found' ),
			array( 'code' => 'noprefs', 'info' => 'Gadget gadgetname does not have any preferences' ),
		) );
	}

	public function getVersion() {
		return __CLASS__ . ': $Id$';
	}
}
