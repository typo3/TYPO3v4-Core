<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2012 Felix Kopp <felix-source@phorax.com>
 *  All rights reserved
 *
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 2 of the License, or
 *  (at your option) any later version.
 *
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  A copy is found in the textfile GPL.txt and important notices to the license
 *  from the author is found in LICENSE.txt distributed with these scripts.
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Repository for Tx_Beuser_Domain_Model_BackendUser
 *
 * @author Felix Kopp <felix-source@phorax.com>
 * @package TYPO3
 * @subpackage beuser
 */
class Tx_Beuser_Domain_Repository_BackendUserSessionRepository extends Tx_Extbase_Persistence_Repository {

	/**
	 * Find Sessions for specific BackendUser
	 * Delivers Array, not ObjectStorage!
	 *
	 * @param Tx_Beuser_Domain_Model_BackendUser
	 * @param array $sessions
	 * @return array
	 */
	public function findByBackendUser(Tx_Beuser_Domain_Model_BackendUser $backendUser) {
		$sessions = array();
		$res = $GLOBALS['TYPO3_DB']->exec_SELECTquery(
			'ses_id AS id, ses_iplock AS ip, ses_tstamp AS timestamp',
			'be_sessions',
			'ses_userid = "' . $backendUser->getUid() . '"',
			'',
			'ses_tstamp ASC'
		);
		while ($row = $GLOBALS['TYPO3_DB']->sql_fetch_assoc($res)) {
			$sessions[] = array(
				'id' => $row['id'],
				'ip' => $row['ip'],
				'timestamp' => new DateTime('@' . $row['timestamp'])
			);
		}

		return $sessions;
	}
}

?>