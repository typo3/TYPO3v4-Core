<?php
namespace TYPO3\CMS\Core\Collection;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Steffen Ritter <typo3steffen-ritter.net>
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
 *
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
/**
 * Collection for handling records from a single database-table.
 *
 * @author Steffen Ritter <typo3steffen-ritter.net>
 * @package TYPO3
 * @subpackage t3lib
 */
interface RecordCollectionInterface extends \TYPO3\CMS\Core\Collection\CollectionInterface, \TYPO3\CMS\Core\Collection\NameableCollectionInterface
{
	/**
	 * Setter for the name of the data-source table
	 *
	 * @param string $tableName
	 * @return void
	 */
	public function setItemTableName($tableName);

	/**
	 * Setter for the name of the data-source table
	 *
	 * @return string
	 */
	public function getItemTableName();

}

?>