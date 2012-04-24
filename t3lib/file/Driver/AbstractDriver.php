<?php
/***************************************************************
 * Copyright notice
 *
 * (c) 2011 Ingmar Schlecht <ingmar.schlecht@typo3.org>
 * All rights reserved
 *
 * This script is part of the TYPO3 project. The TYPO3 project is
 * free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the textfile GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 *
 *
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/


/**
 * An abstract implementation of a storage driver.
 *
 * @author Ingmar Schlecht <ingmar.schlecht@typo3.org>
 * @author  Andreas Wolf <andreas.wolf@ikt-werk.de>
 * @package	TYPO3
 * @subpackage	t3lib
 */
abstract class t3lib_file_Driver_AbstractDriver {

	/**
	 * The mount object this driver instance belongs to
	 *
	 * @var t3lib_file_Storage
	 */
	protected $storage;

	/**
	 * A list of all supported hash algorithms, written all lower case and
	 * without any dashes etc. (e.g. sha1 instead of SHA-1)
	 * Be sure to set this in inherited classes!
	 *
	 * @var array
	 */
	protected $supportedHashAlgorithms = array();

	/**
	 * The storage folder that forms the root of this FS tree
	 *
	 * @var t3lib_file_Folder
	 */
	protected $rootLevelFolder;

	/**
	 * The default folder new files should be put into.
	 *
	 * @var t3lib_file_Folder
	 */
	protected $defaultLevelFolder;

	/**
	 * The configuration of this driver
	 *
	 * @var array
	 */
	protected $configuration = array();

	/**
	 * The callback method to handle the files when listing folder contents
	 *
	 * @var string
	 */
	protected $fileListCallbackMethod = 'getFileList_itemCallback';

	/**
	 * The callback method to handle the folders when listing folder contents
	 *
	 * @var string
	 */
	protected $folderListCallbackMethod = 'getFolderList_itemCallback';

	/**
	 * Creates this object.
	 *
	 * @param array $configuration
	 */
	public function __construct(array $configuration = array()) {
		$this->configuration = $configuration;
	}

	/**
	 * Initializes this object. This is called by the storage after the driver
	 * has been attached.
	 *
	 * @return void
	 */
	abstract public function initialize();

	/**
	 * Checks a fileName for validity. This could be overriden in concrete
	 * drivers if they have different file naming rules.
	 *
	 * @param string $fileName
	 * @return boolean TRUE if file name is valid
	 */
	public function isValidFilename($fileName) {
		if (strpos($fileName, '/') !== FALSE) {
			return FALSE;
		}
		if (!preg_match('/^[[:alnum:][:blank:]\.-_]*$/iu', $fileName)) {
			return FALSE;
		}

		return TRUE;
	}

	/**
	 * Sets the storage object that works with this driver
	 *
	 * @param t3lib_file_Storage $storage
	 * @return t3lib_file_Driver_AbstractDriver
	 */
	public function setStorage(t3lib_file_Storage $storage) {
		$this->storage = $storage;
		return $this;
	}

	/**
	 * Checks if a configuration is valid for this driver.
	 * Throws an exception if a configuration will not work.
	 *
	 * @abstract
	 * @param array $configuration
	 * @return void
	 */
	abstract public static function verifyConfiguration(array $configuration);

	/**
	 * processes the configuration, should be overridden by subclasses
	 *
	 * @return void
	 */
	abstract public function processConfiguration();

	/**
	 * Returns the name of a file/folder based on its identifier.
	 *
	 * @param string $identifier
	 * @return string
	 */
	protected function getNameFromIdentifier($identifier) {
		return basename($identifier);
	}

	/**
	 * Generic handler method for directory listings - gluing together the
	 * listing items is done
	 *
	 * @param string $path
	 * @param integer $start
	 * @param integer $numberOfItems
	 * @param array $filterMethods The filter methods used to filter the directory items
	 * @param string $itemHandlerMethod
	 * @param array $itemRows
	 * @return array
	 */
	protected function getDirectoryItemList($path, $start, $numberOfItems, $filterMethods, $itemHandlerMethod, $itemRows = array()) {
		// This is not abstract because PHPUnit cannot mock abstract protected/private methods
	}

	/*******************
	 * CAPABILITIES
	 *******************/

	/**
	 * The capabilities of this driver. See Storage::CAPABILITY_* constants for possible values. This value should be set
	 * in the constructor of derived classes.
	 *
	 * @var integer
	 */
	protected $capabilities = 0;

	/**
	 * Returns the capabilities of this driver.
	 *
	 * @return integer
	 * @see Storage::CAPABILITY_* constants
	 */
	public function getCapabilities() {
		return $this->capabilities;
	}

	/**
	 * Returns TRUE if this driver has the given capability.
	 *
	 * @param int $capability A capability, as defined in a CAPABILITY_* constant
	 * @return boolean
	 */
	public function hasCapability($capability) {
		return ($this->capabilities & $capability == $capability);
	}


	/*******************
	 * FILE FUNCTIONS
	 *******************/

	/**
	 * Returns a temporary path for a given file, including the file extension.
	 *
	 * @param t3lib_file_FileInterface $file
	 * @return string
	 */
	protected function getTemporaryPathForFile(t3lib_file_FileInterface $file) {
		return t3lib_div::tempnam('fal-tempfile-') . '.' . $file->getExtension();
		// @todo: we need to remove the temporary file again
	}

	/**
	 * Returns the public URL to a file.
	 *
	 * @abstract
	 * @param t3lib_file_ResourceInterface $resource
	 * @param bool  $relativeToCurrentScript    Determines whether the URL returned should be relative to the current script, in case it is relative at all (only for the LocalDriver)
	 * @return string
	 */
	abstract public function getPublicUrl(t3lib_file_ResourceInterface $resource, $relativeToCurrentScript = FALSE);

	/**
	 * Returns a list of all hashing algorithms this Storage supports.
	 *
	 * @return array
	 */
	public function getSupportedHashAlgorithms() {
		return $this->supportedHashAlgorithms;
	}

	/**
	 * Creates a (cryptographic) hash for a file.
	 *
	 * @abstract
	 * @param t3lib_file_FileInterface $file
	 * @param string $hashAlgorithm The hash algorithm to use
	 * @return string
	 * TODO switch parameter order?
	 */
	abstract public function hash(t3lib_file_FileInterface $file, $hashAlgorithm);

	/**
	 * Creates a new file and returns the matching file object for it.
	 *
	 * @abstract
	 * @param string $fileName
	 * @param t3lib_file_Folder $parentFolder
	 * @return t3lib_file_File
	 */
	abstract public function createFile($fileName, t3lib_file_Folder $parentFolder);

	/**
	 * Returns the contents of a file. Beware that this requires to load the
	 * complete file into memory and also may require fetching the file from an
	 * external location. So this might be an expensive operation (both in terms
	 * of processing resources and money) for large files.
	 *
	 * @param t3lib_file_FileInterface $file
	 * @return string The file contents
	 */
	abstract public function getFileContents(t3lib_file_FileInterface $file);

	/**
	 * Sets the contents of a file to the specified value.
	 *
	 * @param t3lib_file_FileInterface $file
	 * @param string $contents
	 * @return integer The number of bytes written to the file
	 * @throws RuntimeException if the operation failed
	 */
	abstract public function setFileContents(t3lib_file_FileInterface $file, $contents);

	/**
	 * Adds a file from the local server hard disk to a given path in TYPO3s virtual file system.
	 *
	 * This assumes that the local file exists, so no further check is done here!
	 *
	 * @param string $localFilePath
	 * @param t3lib_file_Folder $targetFolder
	 * @param string $fileName The name to add the file under
	 * @param t3lib_file_AbstractFile $updateFileObject Optional file object to update (instead of creating a new object). With this parameter, this function can be used to "populate" a dummy file object with a real file underneath.
	 * @return t3lib_file_FileInterface
	 */
	abstract public function addFile($localFilePath, t3lib_file_Folder $targetFolder, $fileName, t3lib_file_AbstractFile $updateFileObject = NULL);

	/**
	 * Checks if a resource exists - does not care for the type (file or folder).
	 *
	 * @param $identifier
	 * @return boolean
	 */
	abstract public function resourceExists($identifier);

	/**
	 * Checks if a file exists.
	 *
	 * @abstract
	 * @param string $identifier
	 * @return boolean
	 */
	abstract public function fileExists($identifier);

	/**
	 * Checks if a file inside a storage folder exists.
	 *
	 * @abstract
	 * @param string $fileName
	 * @param t3lib_file_Folder $folder
	 * @return boolean
	 */
	abstract public function fileExistsInFolder($fileName, t3lib_file_Folder $folder);

	/**
	 * Returns a (local copy of) a file for processing it. When changing the
	 * file, you have to take care of replacing the current version yourself!
	 *
	 * @abstract
	 * @param t3lib_file_FileInterface $file
	 * @param bool $writable Set this to FALSE if you only need the file for read operations. This might speed up things, e.g. by using a cached local version. Never modify the file if you have set this flag!
	 * @return string The path to the file on the local disk
	 */
	// TODO decide if this should return a file handle object
	abstract public function getFileForLocalProcessing(t3lib_file_FileInterface $file, $writable = TRUE);

	/**
	 * Returns the permissions of a file as an array (keys r, w) of boolean flags
	 *
	 * @abstract
	 * @param t3lib_file_FileInterface $file
	 * @return array
	 */
	abstract public function getFilePermissions(t3lib_file_FileInterface $file);

	/**
	 * Returns the permissions of a folder as an array (keys r, w) of boolean flags
	 *
	 * @abstract
	 * @param t3lib_file_Folder $folder
	 * @return array
	 */
	abstract public function getFolderPermissions(t3lib_file_Folder $folder);

	/**
	 * Renames a file
	 *
	 * @abstract
	 * @param t3lib_file_FileInterface $file
	 * @param string $newName
	 * @return string The new identifier of the file if the operation succeeds
	 * @throws RuntimeException if renaming the file failed
	 */
	abstract public function renameFile(t3lib_file_FileInterface $file, $newName);

	/**
	 * Replaces the contents (and file-specific metadata) of a file object with a local file.
	 *
	 * @abstract
	 * @param t3lib_file_AbstractFile $file
	 * @param string $localFilePath
	 * @return boolean
	 */
	abstract public function replaceFile(t3lib_file_AbstractFile $file, $localFilePath);

	/**
	 * Returns information about a file for a given file identifier.
	 *
	 * @param string $identifier The (relative) path to the file.
	 * @return array
	 */
	abstract public function getFileInfoByIdentifier($identifier);

	/**
	 * Returns information about a file for a given file object.
	 *
	 * @param t3lib_file_FileInterface $file
	 * @return array
	 */
	public function getFileInfo(t3lib_file_FileInterface $file) {
		return $this->getFileInfoByIdentifier($file->getIdentifier());
	}

	/**
	 * Returns a file object by its identifier.
	 *
	 * @param string $identifier
	 * @return t3lib_file_FileInterface
	 */
	public function getFile($identifier) {
		$fileObject = NULL;

		if (!$this->fileExists($identifier)) {
			throw new t3lib_file_exception_FileDoesNotExistException();
		}
		$fileInfo = $this->getFileInfoByIdentifier($identifier);
		$fileObject = $this->getFileObject($fileInfo);

		return $fileObject;
	}

	/**
	 * Creates a file object from a given file data array
	 *
	 * @param array $fileData
	 * @return t3lib_file_File
	 */
	protected function getFileObject(array $fileData) {
		$fileObject = t3lib_file_Factory::getInstance()->createFileObject($fileData);

		return $fileObject;
	}

	/**
	 * Returns a folder by its identifier.
	 *
	 * @param string $identifier
	 * @return t3lib_file_Folder
	 */
	public function getFolder($identifier) {
		$name = $this->getNameFromIdentifier($identifier);

		return t3lib_file_Factory::getInstance()->createFolderObject(
			$this->storage,
			$identifier,
			$name
		);
	}

	/**
	 * Returns a folder within the given folder. Use this method instead of doing your own string manipulation magic
	 * on the identifiers because non-hierarchical storages might fail otherwise.
	 *
	 * @param $name
	 * @param t3lib_file_Folder $parentFolder
	 * @return t3lib_file_Folder
	 */
	abstract public function getFolderInFolder($name, t3lib_file_Folder $parentFolder);

	/**
	 * Returns TRUE if a file should be excluded from a file listing.
	 *
	 * @param string $identifier
	 * @param string $filename
	 * @return boolean
	 */
	protected function isHiddenFile($identifier, $filename) {
		if (substr($filename, 0, 1) == '.') {
			return TRUE;
		}

		return FALSE;
	}

	/**
	 * Applies a set of filter methods to a file name to find out if it should be used or not. This is e.g. used by
	 * directory listings.
	 *
	 * @param array $filterMethods The filter methods to use
	 * @param string $itemName
	 * @param string $itemIdentifier
	 * @param string $parentIdentifier
	 * @param array $additionalInformation Additional information about the inspected item
	 * @return bool
	 */
	protected function applyFilterMethodsToDirectoryItem(array $filterMethods, $itemName, $itemIdentifier, $parentIdentifier, array $additionalInformation = array()) {
		foreach ($filterMethods as $filter) {
			if (is_array($filter)) {
				$result = call_user_func($filter, $itemName, $itemIdentifier, $parentIdentifier, $additionalInformation, $this);

					// We have to use -1 as the „don't include“ return value, as call_user_func() will return FALSE
					// If calling the method succeeded and thus we can't use that as a return value.
				if ($result === -1) {
					return FALSE;
				} elseif ($result === FALSE) {
					throw new RuntimeException('Could not apply file/folder name filter ' . $filter[0] . '::' . $filter[1]);
				}
			}
		}

		return TRUE;
	}

	/**
	 * Returns a list of files inside the specified path
	 *
	 * @param string $path
	 * @param integer $start The position to start the listing; if not set, start from the beginning
	 * @param integer $numberOfItems The number of items to list; if not set, return all items
	 * @param array $filenameFilterCallbacks The method callbacks to use for filtering the items
	 * @param array $fileData Two-dimensional, identifier-indexed array of file index records from the database
	 * @return array
	 */
	// TODO add unit tests
	public function getFileList($path, $start = 0, $numberOfItems = 0, array $filenameFilterCallbacks = array(), $fileData = array()) {
		return $this->getDirectoryItemList($path, $start, $numberOfItems, $filenameFilterCallbacks, $this->fileListCallbackMethod, $fileData);
	}

	/**
	 * Copies a file to a temporary path and returns that path.
	 *
	 * @abstract
	 * @param t3lib_file_FileInterface $file
	 * @return string The temporary path
	 */
	abstract public function copyFileToTemporaryPath(t3lib_file_FileInterface $file);

	/**
	 * Moves a file *within* the current storage.
	 * Note that this is only about an intra-storage move action, where a file is just
	 * moved to another folder in the same storage.
	 *
	 * @param t3lib_file_FileInterface $file
	 * @param t3lib_file_Folder $targetFolder
	 * @param string $fileName
	 * @return string The new identifier of the file
	 */
	abstract public function moveFileWithinStorage(t3lib_file_FileInterface $file, t3lib_file_Folder $targetFolder, $fileName);

	/**
	 * Copies a file *within* the current storage.
	 * Note that this is only about an intra-storage copy action, where a file is just
	 * copied to another folder in the same storage.
	 *
	 * @param t3lib_file_FileInterface $file
	 * @param t3lib_file_Folder $targetFolder
	 * @param string $fileName
	 * @return t3lib_file_FileInterface The new (copied) file object.
	 */
	abstract public function copyFileWithinStorage(t3lib_file_FileInterface $file, t3lib_file_Folder $targetFolder, $fileName);

	/**
	 * Folder equivalent to moveFileWithinStorage().
	 *
	 * @param t3lib_file_Folder $folderToMove
	 * @param t3lib_file_Folder $targetFolder
	 * @param string $newFolderName
	 * @return array A map of old to new file identifiers
	 */
	abstract public function moveFolderWithinStorage(t3lib_file_Folder $folderToMove, t3lib_file_Folder $targetFolder, $newFolderName);

	/**
	 * Folder equivalent to copyFileWithinStorage().
	 *
	 * @param t3lib_file_Folder $folderToMove
	 * @param t3lib_file_Folder $targetFolder
	 * @param string $newFileName
	 * @return boolean
	 */
	abstract public function copyFolderWithinStorage(t3lib_file_Folder $folderToMove, t3lib_file_Folder $targetFolder, $newFileName);

	/**
	 * Removes a file from this storage. This does not check if the file is
	 * still used or if it is a bad idea to delete it for some other reason
	 * this has to be taken care of in the upper layers (e.g. the Storage)!
	 *
	 * @abstract
	 * @param t3lib_file_FileInterface $file
	 * @return boolean TRUE if deleting the file succeeded
	 */
	abstract public function deleteFile(t3lib_file_FileInterface $file);

	/**
	 * Removes a folder from this storage.
	 *
	 * @param t3lib_file_Folder $folder
	 * @param boolean $deleteRecursively
	 * @return boolean
	 */
	abstract public function deleteFolder(t3lib_file_Folder $folder, $deleteRecursively = FALSE);

	/**
	 * Adds a file at the specified location. This should only be used internally.
	 *
	 * @abstract
	 * @param string $localFilePath
	 * @param t3lib_file_Folder $targetFolder
	 * @param string $targetFileName
	 * @return string The new identifier of the file
	 */
	// TODO check if this is still necessary if we move more logic to the storage
	abstract public function addFileRaw($localFilePath, t3lib_file_Folder $targetFolder, $targetFileName);

	/**
	 * Deletes a file without access and usage checks.
	 * This should only be used internally.
	 *
	 * This accepts an identifier instead of an object because we might want to
	 * delete files that have no object associated with (or we don't want to
	 * create an object for) them - e.g. when moving a file to another storage.
	 *
	 * @abstract
	 * @param string $identifier
	 * @return boolean TRUE if removing the file succeeded
	 */
	abstract public function deleteFileRaw($identifier);

	/*******************
	 * FOLDER FUNCTIONS
	 *******************/

	/**
	 * Returns the root level folder of the storage.
	 *
	 * @abstract
	 * @return t3lib_file_Folder
	 */
	abstract public function getRootLevelFolder();

	/**
	 * Returns the default folder new files should be put into.
	 *
	 * @abstract
	 * @return t3lib_file_Folder
	 */
	abstract public function getDefaultFolder();

	/**
	 * Creates a folder.
	 *
	 * @param string $newFolderName
	 * @param t3lib_file_Folder $parentFolder
	 * @return t3lib_file_Folder The new (created) folder object
	 */
	abstract public function createFolder($newFolderName, t3lib_file_Folder $parentFolder);

	/**
	 * Returns a list of all folders in a given path
	 *
	 * @param string $path
	 * @param integer $start The position to start the listing; if not set, start from the beginning
	 * @param integer $numberOfItems The number of items to list; if not set, return all items
	 * @param array $foldernameFilterCallbacks The method callbacks to use for filtering the items
	 * @return array
	 */
	public function getFolderList($path, $start = 0, $numberOfItems = 0, array $foldernameFilterCallbacks = array()) {
		return $this->getDirectoryItemList($path, $start, $numberOfItems, $foldernameFilterCallbacks, $this->folderListCallbackMethod);
	}

	/**
	 * Checks if a folder exists
	 *
	 * @abstract
	 * @param string $identifier
	 * @return boolean
	 */
	abstract public function folderExists($identifier);

	/**
	 * Checks if a file inside a storage folder exists.
	 *
	 * @abstract
	 * @param string $folderName
	 * @param t3lib_file_Folder $folder
	 * @return boolean
	 */
	abstract public function folderExistsInFolder($folderName, t3lib_file_Folder $folder);

	/**
	 * Renames a folder in this storage.
	 *
	 * @param t3lib_file_Folder $folder
	 * @param string $newName The target path (including the file name!)
	 * @return array A map of old to new file identifiers
	 * @throws RuntimeException if renaming the folder failed
	 */
	abstract public function renameFolder(t3lib_file_Folder $folder, $newName);

	/**
	 * Checks if a given object or identifier is within a container, e.g. if
	 * a file or folder is within another folder.
	 * This can e.g. be used to check for webmounts.
	 *
	 * @abstract
	 * @param t3lib_file_Folder $container
	 * @param mixed $content An object or an identifier to check
	 * @return boolean TRUE if $content is within $container
	 */
	abstract public function isWithin(t3lib_file_Folder $container, $content);

	/**
	 * Checks if a folder contains files and (if supported) other folders.
	 *
	 * @param t3lib_file_Folder $folder
	 * @return boolean TRUE if there are no files and folders within $folder
	 */
	abstract public function isFolderEmpty(t3lib_file_Folder $folder);
}

?>