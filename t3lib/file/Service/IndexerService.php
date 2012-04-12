<?php
/***************************************************************
 *  Copyright notice
 *
 *  (c) 2011 Andreas Wolf <andreas.wolf@ikt-werk.de>
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
 * Indexer for the virtual file system
 * should only be accessed through the FileRepository for now
 *
 * @author Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package TYPO3
 * @subpackage t3lib
 *
 */
class t3lib_file_Service_IndexerService implements t3lib_Singleton {

	/**
	 * @var t3lib_file_Repository_FileRepository
	 */
	protected $repository;

	/**
	 * @var t3lib_file_Factory
	 */
	protected $factory;

	/**
	 * empty constructor, nothing to do here yet
	 */
	public function __construct() {
	}

	/**
	 * Internal function to retrieve the file repository,
	 * if it does not exist, an instance will be created
	 *
	 * @return t3lib_file_Repository_FileRepository
	 */
	protected function getRepository() {
		if ($this->repository === NULL) {
			$this->repository = t3lib_div::makeInstance('t3lib_file_Repository_FileRepository');
		}

		return $this->repository;
	}

	/**
	 * Setter function for the fileFactory
	 * returns the object itself for chaining purposes
	 *
	 * @param t3lib_file_Factory $factory
	 * @return t3lib_file_Service_IndexerService
	 */
	public function setFactory(t3lib_file_Factory $factory) {
		$this->factory = $factory;
		return $this;
	}

	/**
	 * Creates or updates a file index entry from a file object.
	 *
	 * @param t3lib_file_File $fileObject
	 * @param bool $updateObject Set this to FALSE to get the indexed values. You have to take care of updating the object yourself then!
	 * @return t3lib_file_File|array the indexed $fileObject or an array of indexed properties.
	 */
	public function indexFile(t3lib_file_File $fileObject, $updateObject = TRUE) {
			// Get the file information of this object
		$fileInfo = $this->gatherFileInformation($fileObject);

			// Signal slot BEFORE the file was indexed
		$this->emitPreFileIndexSignal($fileObject, $fileInfo);

			// If the file is already indexed, then the file information will
			// be updated on the existing record
		if ($fileObject->isIndexed()) {
			$GLOBALS['TYPO3_DB']->exec_UPDATEquery('sys_file', sprintf('uid = %d', $fileObject->getUid()), $fileInfo);
		} else {
				// Check if a file has been moved outside of FAL -- we have some
				// orphaned index record in this case we could update
			$otherFiles = $this->getRepository()->findBySha1Hash($fileInfo['sha1']);
			$movedFile = FALSE;
			/** @var $otherFile t3lib_file_File */
			foreach ($otherFiles as $otherFile) {
				if (!$otherFile->exists()) {
						// @todo: create a log entry
					$movedFile = TRUE;
					$otherFile->updateProperties($fileInfo);
					$this->getRepository()->update($otherFile);
					$fileInfo['uid'] = $otherFile->getUid();
					$fileObject = $otherFile;
						// Skip the rest of the files here as we might have more files that are missing, but we can only
						// have one entry. The optimal solution would be to merge these records then, but this requires
						// some more advanced logic that we currently have not implemented.
					break;
				}
			}

				// File was not moved, so it is a new index record
			if ($movedFile === FALSE) {
					// Crdate and tstamp should not be present when updating
					// the file object, as they only relate to the index record
				$indexRecord = array_merge($fileInfo,
					array(
						'crdate' => $GLOBALS['EXEC_TIME'],
						'tstamp' => $GLOBALS['EXEC_TIME']
					)
				);
				$GLOBALS['TYPO3_DB']->exec_INSERTquery('sys_file', $indexRecord);
				$fileInfo['uid'] = $GLOBALS['TYPO3_DB']->sql_insert_id();
			}
		}

			// Check for an error during the execution and throw an exception
		$error = $GLOBALS['TYPO3_DB']->sql_error();
		if ($error) {
			throw new RuntimeException('Error during file indexing: "' . $error . '"', 1314455642);
		}

			// Signal slot AFTER the file was indexed
		$this->emitPostFileIndexSignal($fileObject, $fileInfo);

		if ($updateObject) {
			$fileObject->updateProperties($fileInfo);
			return $fileObject;
		} else {
			return $fileInfo;
		}
	}

	/**
	 * Indexes an array of file objects
	 * currently this is done in a simple way, however could be changed to be more performant
	 *
	 * @param t3lib_file_File[] $fileObjects
	 * @return void
	 */
	public function indexFiles(array $fileObjects) {
		foreach ($fileObjects as $fileObject) {
			$this->indexFile($fileObject);
		}
	}

	/**
	 * Indexes all files in a given storage folder.
	 * currently this is done in a simple way, however could be changed to be more performant
	 *
	 * @param t3lib_file_Folder $folder
	 * @return int The number of indexed files.
	 */
	public function indexFilesInFolder(t3lib_file_Folder $folder) {
		$numberOfIndexedFiles = 0;

			// Index all files in this folder
		$fileObjects = $folder->getFiles();
		foreach ($fileObjects as $fileObject) {
			$this->indexFile($fileObject);
			$numberOfIndexedFiles++;
		}

			// Call this function recursively for each subfolder
		$subFolders = $folder->getSubfolders();
		foreach ($subFolders as $subFolder) {
			$numberOfIndexedFiles += $this->indexFilesInFolder($subFolder);
		}

		return $numberOfIndexedFiles;
	}


	/**
	 * Fetches the information for a sys_file record
	 * based on a single file
	 * this function shouldn't be used, if someone needs to fetch the file information
	 * from a file object, should be done by getProperties etc
	 *
	 * @param t3lib_file_File $file the file to fetch the information from
	 * @return array the file information as an array
	 */
	protected function gatherFileInformation(t3lib_file_File $file) {
		$storage = $file->getStorage();

		// TODO: See if we can't just return info, as it contains most of the
		// stuff we put together in array form again later here.
		$info = $storage->getFileInfo($file);

		$fileInfo = array(
			'creation_date' => $info['ctime'],
			'modification_date' => $info['mtime'],
			'size' => $info['size'],
			'identifier' => $file->getIdentifier(),
			'storage' => $storage->getUid(),
			'name' => $file->getName(),
			'sha1' => $storage->hashFile($file, 'sha1'),
			'type' => $file->getType(),
			'mime_type' => $file->getMimeType(),
			'extension' => $file->getExtension(),
		);

		return $fileInfo;
	}


	/**
	 * Signal that is called after a file object was indexed
	 *
	 * @param t3lib_file_File $fileObject
	 * @param array $fileInfo
	 * @signal
	 */
	protected function emitPreFileIndexSignal(t3lib_file_File $fileObject, $fileInfo) {
		$this->getSignalSlotDispatcher()->dispatch(
			't3lib_file_Storage',
			'preFileIndex',
			array($fileObject, $fileInfo)
		);
	}

	/**
	 * Signal that is called after a file object was indexed
	 *
	 * @param t3lib_file_File $fileObject
	 * @param array $fileInfo
	 * @signal
	 */
	protected function emitPostFileIndexSignal(t3lib_file_File $fileObject, $fileInfo) {
		$this->getSignalSlotDispatcher()->dispatch(
			't3lib_file_Storage',
			'postFileIndex',
			array($fileObject, $fileInfo)
		);
	}

	/**
	 * @return Tx_Extbase_SignalSlot_Dispatcher
	 */
	protected function getSignalSlotDispatcher() {
		return $this->getObjectManager()->get('Tx_Extbase_SignalSlot_Dispatcher');
	}

	/**
	 * @return Tx_Extbase_Object_ObjectManager
	 */
	protected function getObjectManager() {
		return t3lib_div::makeInstance('Tx_Extbase_Object_ObjectManager');
	}
}


if (defined('TYPO3_MODE') && isset($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/file/Service/IndexerService.php'])) {
	include_once($GLOBALS['TYPO3_CONF_VARS'][TYPO3_MODE]['XCLASS']['t3lib/file/Service/IndexerService.php']);
}

?>