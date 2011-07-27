<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2011 Philipp Gampe (dev.typo3@philippgampe.info)
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
 * Observer to automatically save a http request chunk by chunk to a file.
 * If the file already exists, it will be overridden.
 * This follow an example in HTTP_Request2 manual.
 * @see http://pear.php.net/manual/en/package.http.http-request2.observers.php
 */
class t3lib_http_observer_Download implements SplObserver {

	/**
	 * @var resource A file pointer resource
	 */
	protected $filepointer;

	/**
	 * @var string The full filename including the leading directory
	 */
	protected  $target;

	/**
	 * @var string The name of the target directory
	 */
	protected $directory;

	/**
	 * @var string The name of the target file
	 */
	protected $filename;

	/**
	 * Constructor
	 *
	 * @throws Exception if directory is not found
	 * @param string $dir The absolute path to the directory in which the file is saved.
	 * @param string $filename The filename - if not set, it is determined automatically.
	 */
	public function __construct($directory, $filename = NULL) {
		$this->setDirectory($directory);
		$this->setFilename($filename);
	}

	/**
	 * The update method is called for each chunk
	 *
	 * @throws Exception if file can not be opened
	 * @param SplSubject $subject
	 * @return void
	 */
	public function update(SplSubject $subject)	{
		$event = $subject->getLastEvent();

		switch ($event['name']) {
			case 'receivedHeaders':
				if (empty($this->filename)) {
					$disposition = $event['data']->getHeader('content-disposition');
					if ($disposition && 0 == strpos($disposition, 'attachment')
									&& preg_match('/filename="([^"]+)"/', $disposition, $matches)
					) {
						$this->filename = basename($matches[1]);
					} else {
						$this->filename = basename($subject->getUrl()->getPath());
					}
				}

				$this->target = $this->directory . DIRECTORY_SEPARATOR . $this->filename;

				if (!($this->filepointer = @fopen($this->target, 'wb'))) {
					throw new Exception('Cannot open target file ' . $this->target, 1320833203);
				}

				break;

			case 'receivedBodyPart':
				// Fall through
			case 'receivedEncodedBodyPart':
				fwrite($this->filepointer, $event['data']);
				break;

			case 'receivedBody':
				fclose($this->filepointer);
				$this->filepointer = NULL;
				t3lib_div::fixPermissions($this->target);
		}
	}

	/**
	 * Sets the directory - Checks whether the directory is available
	 *
	 * @throws Exception if directory is not found
	 * @param string $directory The absolute path to the directory in which the file is saved.
	 * @return void
	 */
	public function setDirectory($directory) {
		if (!is_dir($directory)) {
			throw new Exception($directory . 'is not a directory', 1312223779);
		}
		if (substr($directory, -1) === DIRECTORY_SEPARATOR) {
			$directory = substr($directory, 0, -1);
		}
		$this->directory = $directory;
	}

	/**
	 * Sets the filename
	 *
	 * @param string $filename The filename - if not set, it is determined automatically.
	 * @return void
	 */
	public function setFilename($filename = NULL) {
		$this->filename = $filename;
	}
}
?>