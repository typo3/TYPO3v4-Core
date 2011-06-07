<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Dominique Feyer (dfeyer@reelpeek.net)
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
 * class.tx_language_cacheabstrat.php
 *
 * Provides the abstract class for the caching subsystem
 *
 * @author Dominique Feyer <dfeyer@reelpeek.net>
 */

/**
 * Provides the abstract class for the caching subsystem
 *
 * @package TYPO3
 * @subpackage core
 * @author	Dominique Feyer <dfeyer@reelpeek.net>
 */
abstract class tx_language_CacheAbstract implements t3lib_Singleton {

	/**
	 * Local serialize function
	 * 
	 * @var string
	 */
	protected $serialize;

	/**
	 * Local unserialize function
	 * 
	 * @var string
	 */
	protected $unserialize;

	/**
	 * Cache constructor
	 *
	 * Detect is the current setup support igbinary
	 */
	public function __construct() {
		if (extension_loaded('igbinary')) {
			$this->serialize = 'igbinary_serialize';
			$this->unserialize = 'igbinary_unserialize';
		} else {
			$this->serialize = 'serialize';
			$this->unserialize = 'unserialize';
		}
	}

	/**
	 * Get the local serializer function
	 * 
	 * @return string
	 */
	protected function getSerialize() {
		return $this->serialize;
	}

	/**
	 * Get the local unserializer function
	 * 
	 * @return string
	 */
	protected function getUnserialize() {
		return $this->unserialize;
	}

	/**
	 * Get cached value
	 *
	 * @param  string $hash Cache hash
	 * @return bool|mixed
	 */
	abstract public function get($hash);

	/**
	 * Add value to the cache
	 *
	 * @throws RuntimeException
	 * @param  string $hash Cache hash
	 * @param  $data
	 * @return void
	 */
	abstract public function set($hash, $data);

}