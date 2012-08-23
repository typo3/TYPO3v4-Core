<?php
namespace TYPO3\CMS\Filelist\Controller;

/**
 * Script Class for creating the list of files in the File > Filelist module
 *
 * @author Kasper Skårhøj <kasperYYYY@typo3.com>
 * @package TYPO3
 * @subpackage core
 */
class FileListController {

	// Module configuration
	/**
	 * @todo Define visibility
	 */
	public $MCONF = array();

	/**
	 * @todo Define visibility
	 */
	public $MOD_MENU = array();

	/**
	 * @todo Define visibility
	 */
	public $MOD_SETTINGS = array();

	// Internal:
	// Accumulated HTML output
	/**
	 * @todo Define visibility
	 */
	public $content;

	/**
	 * Document template object
	 *
	 * @var \TYPO3\CMS\Backend\Template\DocumentTemplate
	 * @todo Define visibility
	 */
	public $doc;

	// Internal, static: GPvars:
	// "id" -> the path to list.
	/**
	 * @todo Define visibility
	 */
	public $id;

	/* @var t3lib_file_Folder $folderObject */
	protected $folderObject;

	/* @var t3lib_FlashMessage $errorMessage */
	protected $errorMessage;

	// Pointer to listing
	/**
	 * @todo Define visibility
	 */
	public $pointer;

	// "Table"
	/**
	 * @todo Define visibility
	 */
	public $table;

	// Thumbnail mode.
	/**
	 * @todo Define visibility
	 */
	public $imagemode;

	/**
	 * @todo Define visibility
	 */
	public $cmd;

	/**
	 * @todo Define visibility
	 */
	public $overwriteExistingFiles;

	/**
	 * Initialize variables, file object
	 * Incoming GET vars include id, pointer, table, imagemode
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function init() {
		// Setting GPvars:
		$this->id = ($combinedIdentifier = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('id'));
		$this->pointer = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('pointer');
		$this->table = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('table');
		$this->imagemode = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('imagemode');
		$this->cmd = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('cmd');
		$this->overwriteExistingFiles = \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('overwriteExistingFiles');
		// Setting module name:
		$this->MCONF = $GLOBALS['MCONF'];
		// Create the folder object
		try {
			if ($combinedIdentifier) {
				$fileFactory = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Resource\\ResourceFactory');
				$this->folderObject = $fileFactory->getFolderObjectFromCombinedIdentifier($combinedIdentifier);
				// Disallow the rendering of the processing folder (e.g. could be called manually)
				// and all folders without any defined storage
				if ($this->folderObject && ($this->folderObject->getStorage()->getUid() == 0 || trim($this->folderObject->getStorage()->getProcessingFolder()->getIdentifier(), '/') == trim($this->folderObject->getIdentifier(), '/'))) {
					$this->folderObject = NULL;
				}
			} else {
				// Take the first object of the first storage
				$fileStorages = $GLOBALS['BE_USER']->getFileStorages();
				$fileStorage = reset($fileStorages);
				if ($fileStorage) {
					// Validating the input "id" (the path, directory!) and
					// checking it against the mounts of the user. - now done in the controller
					$this->folderObject = $fileStorage->getRootLevelFolder();
				} else {
					$this->folderObject = NULL;
				}
			}
		} catch (\TYPO3\CMS\Core\Resource\Exception\FolderDoesNotExistException $fileException) {
			// Set folder object to null and throw a message later on
			$this->folderObject = NULL;
			$this->errorMessage = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Messaging\\FlashMessage', sprintf($GLOBALS['LANG']->getLL('folderNotFoundMessage', TRUE), htmlspecialchars($this->id)), $GLOBALS['LANG']->getLL('folderNotFoundTitle', TRUE), \TYPO3\CMS\Core\Messaging\FlashMessage::ERROR);
		}
		// Configure the "menu" - which is used internally to save the values of sorting, displayThumbs etc.
		$this->menuConfig();
	}

	/**
	 * Setting the menu/session variables
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function menuConfig() {
		// MENU-ITEMS:
		// If array, then it's a selector box menu
		// If empty string it's just a variable, that'll be saved.
		// Values NOT in this array will not be saved in the settings-array for the module.
		$this->MOD_MENU = array(
			'sort' => '',
			'reverse' => '',
			'displayThumbs' => '',
			'clipBoard' => '',
			'bigControlPanel' => ''
		);
		// CLEANSE SETTINGS
		$this->MOD_SETTINGS = \TYPO3\CMS\Backend\Utility\BackendUtility::getModuleData($this->MOD_MENU, \TYPO3\CMS\Core\Utility\GeneralUtility::_GP('SET'), $this->MCONF['name']);
	}

	/**
	 * Main function, creating the listing
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function main() {
		// Initialize the template object
		$this->doc = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Template\\DocumentTemplate');
		$this->doc->backPath = $GLOBALS['BACK_PATH'];
		$this->doc->setModuleTemplate('templates/file_list.html');
		$this->doc->getPageRenderer()->loadPrototype();
		// There there was access to this file path, continue, make the list
		if ($this->folderObject) {
			// Include the initialization for the flash uploader
			if ($GLOBALS['BE_USER']->uc['enableFlashUploader']) {
				$this->doc->JScodeArray['flashUploader'] = ((((((('
					if (top.TYPO3.FileUploadWindow.isFlashAvailable()) {
						document.observe("dom:loaded", function() {
								// monitor the button
							$("button-upload").observe("click", initFlashUploader);

							function initFlashUploader(event) {
									// set the page specific options for the flashUploader
								var flashUploadOptions = {
									uploadURL:           top.TS.PATH_typo3 + "ajax.php",
									uploadFileSizeLimit: "' . \TYPO3\CMS\Core\Utility\GeneralUtility::getMaxUploadFileSize()) . '",
									uploadFileTypes: {
										allow:  "') . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']['webspace']['allow']) . '",
										deny: "') . $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']['webspace']['deny']) . '"
									},
									uploadFilePostName:  "upload_1",
									uploadPostParams: {
										"file[upload][1][target]": "') . ($this->folderObject ? $this->folderObject->getCombinedIdentifier() : '')) . '",
										"file[upload][1][data]": 1,
										"file[upload][1][charset]": "utf-8",
										"ajaxID": "TYPO3\\CMS\\Backend\\Controller\\File\\FileController::process"
									}
								};

									// get the flashUploaderWindow instance from the parent frame
								var flashUploader = top.TYPO3.FileUploadWindow.getInstance(flashUploadOptions);
								// add an additional function inside the container to show the checkbox option
								var infoComponent = new top.Ext.Panel({
									autoEl: { tag: "div" },
									height: "auto",
									bodyBorder: false,
									border: false,
									hideBorders: true,
									cls: "t3-upload-window-infopanel",
									id: "t3-upload-window-infopanel-addition",
									html: \'<label for="overrideExistingFilesCheckbox"><input id="overrideExistingFilesCheckbox" type="checkbox" onclick="setFlashPostOptionOverwriteExistingFiles(this);" />\' + top.String.format(top.TYPO3.LLL.fileUpload.infoComponentOverrideFiles) + \'</label>\'
								});
								flashUploader.add(infoComponent);

									// do a reload of this frame once all uploads are done
								flashUploader.on("totalcomplete", function() {
									window.location.reload();
								});

									// this is the callback function that delivers the additional post parameter to the flash application
								top.setFlashPostOptionOverwriteExistingFiles = function(checkbox) {
									var uploader = top.TYPO3.getInstance("FileUploadWindow");
									if (uploader.isVisible()) {
										uploader.swf.addPostParam("overwriteExistingFiles", (checkbox.checked == true ? 1 : 0));
									}
								};

								event.stop();
							};
						});
					}
				';
			}
			// Create filelisting object
			$this->filelist = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\FileList\\FileList');
			$this->filelist->backPath = $GLOBALS['BACK_PATH'];
			// Apply predefined values for hidden checkboxes
			// Set predefined value for DisplayBigControlPanel:
			if ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableDisplayBigControlPanel') === 'activated') {
				$this->MOD_SETTINGS['bigControlPanel'] = TRUE;
			} elseif ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableDisplayBigControlPanel') === 'deactivated') {
				$this->MOD_SETTINGS['bigControlPanel'] = FALSE;
			}
			// Set predefined value for DisplayThumbnails:
			if ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableDisplayThumbnails') === 'activated') {
				$this->MOD_SETTINGS['displayThumbs'] = TRUE;
			} elseif ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableDisplayThumbnails') === 'deactivated') {
				$this->MOD_SETTINGS['displayThumbs'] = FALSE;
			}
			// Set predefined value for Clipboard:
			if ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableClipBoard') === 'activated') {
				$this->MOD_SETTINGS['clipBoard'] = TRUE;
			} elseif ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableClipBoard') === 'deactivated') {
				$this->MOD_SETTINGS['clipBoard'] = FALSE;
			}
			// If user never opened the list module, set the value for displayThumbs
			if (!isset($this->MOD_SETTINGS['displayThumbs'])) {
				$this->MOD_SETTINGS['displayThumbs'] = $GLOBALS['BE_USER']->uc['thumbnailsByDefault'];
			}
			$this->filelist->thumbs = $this->MOD_SETTINGS['displayThumbs'];
			// Create clipboard object and initialize that
			$this->filelist->clipObj = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Backend\\Clipboard\\Clipboard');
			$this->filelist->clipObj->fileMode = 1;
			$this->filelist->clipObj->initializeClipboard();
			$CB = \TYPO3\CMS\Core\Utility\GeneralUtility::_GET('CB');
			if ($this->cmd == 'setCB') {
				$CB['el'] = $this->filelist->clipObj->cleanUpCBC(array_merge(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('CBH'), \TYPO3\CMS\Core\Utility\GeneralUtility::_POST('CBC')), '_FILE');
			}
			if (!$this->MOD_SETTINGS['clipBoard']) {
				$CB['setP'] = 'normal';
			}
			$this->filelist->clipObj->setCmd($CB);
			$this->filelist->clipObj->cleanCurrent();
			// Saves
			$this->filelist->clipObj->endClipboard();
			// If the "cmd" was to delete files from the list (clipboard thing), do that:
			if ($this->cmd == 'delete') {
				$items = $this->filelist->clipObj->cleanUpCBC(\TYPO3\CMS\Core\Utility\GeneralUtility::_POST('CBC'), '_FILE', 1);
				if (count($items)) {
					// Make command array:
					$FILE = array();
					foreach ($items as $v) {
						$FILE['delete'][] = array('data' => $v);
					}
					// Init file processing object for deleting and pass the cmd array.
					$fileProcessor = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance('TYPO3\\CMS\\Core\\Utility\\File\\ExtendedFileUtility');
					$fileProcessor->init($GLOBALS['FILEMOUNTS'], $GLOBALS['TYPO3_CONF_VARS']['BE']['fileExtensions']);
					$fileProcessor->init_actionPerms($GLOBALS['BE_USER']->getFileoperationPermissions());
					$fileProcessor->dontCheckForUnique = $this->overwriteExistingFiles ? 1 : 0;
					$fileProcessor->start($FILE);
					$fileProcessor->processData();
					$fileProcessor->printLogErrorMessages();
				}
			}
			if (!isset($this->MOD_SETTINGS['sort'])) {
				// Set default sorting
				$this->MOD_SETTINGS['sort'] = 'file';
				$this->MOD_SETTINGS['reverse'] = 0;
			}
			// Start up filelisting object, include settings.
			$this->pointer = \TYPO3\CMS\Core\Utility\MathUtility::forceIntegerInRange($this->pointer, 0, 100000);
			$this->filelist->start($this->folderObject, $this->pointer, $this->MOD_SETTINGS['sort'], $this->MOD_SETTINGS['reverse'], $this->MOD_SETTINGS['clipBoard'], $this->MOD_SETTINGS['bigControlPanel']);
			// Generate the list
			$this->filelist->generateList();
			// Write the footer
			$this->filelist->writeBottom();
			// Set top JavaScript:
			$this->doc->JScode = $this->doc->wrapScriptTags((('

			if (top.fsMod) top.fsMod.recentIds["file"] = unescape("' . rawurlencode($this->id)) . '");
			function jumpToUrl(URL) {	//
				window.location.href = URL;
			}

			') . $this->filelist->CBfunctions());
			// This will return content necessary for the context sensitive clickmenus to work: bodytag events, JavaScript functions and DIV-layers.
			$this->doc->getContextMenuCode();
			// Setting up the buttons and markers for docheader
			list($buttons, $otherMarkers) = $this->filelist->getButtonsAndOtherMarkers($this->folderObject);
			// add the folder info to the marker array
			$otherMarkers['FOLDER_INFO'] = $this->filelist->getFolderInfo();
			$docHeaderButtons = array_merge($this->getButtons(), $buttons);
			// Build the <body> for the module
			// Create output
			$pageContent = '';
			$pageContent .= ('<form action="' . htmlspecialchars($this->filelist->listURL())) . '" method="post" name="dblistForm">';
			$pageContent .= $this->filelist->HTMLcode;
			$pageContent .= '<input type="hidden" name="cmd" /></form>';
			// Making listing options:
			if ($this->filelist->HTMLcode) {
				$pageContent .= '

					<!--
						Listing options for extended view, clipboard and thumbnails
					-->
					<div id="typo3-listOptions">
				';
				// Add "display bigControlPanel" checkbox:
				if ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableDisplayBigControlPanel') === 'selectable') {
					$pageContent .= ((\TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->id, 'SET[bigControlPanel]', $this->MOD_SETTINGS['bigControlPanel'], '', '', 'id="bigControlPanel"') . '<label for="bigControlPanel"> ') . $GLOBALS['LANG']->getLL('bigControlPanel', TRUE)) . '</label><br />';
				}
				// Add "display thumbnails" checkbox:
				if ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableDisplayThumbnails') === 'selectable') {
					$pageContent .= ((\TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->id, 'SET[displayThumbs]', $this->MOD_SETTINGS['displayThumbs'], '', '', 'id="checkDisplayThumbs"') . ' <label for="checkDisplayThumbs">') . $GLOBALS['LANG']->getLL('displayThumbs', TRUE)) . '</label><br />';
				}
				// Add "clipboard" checkbox:
				if ($GLOBALS['BE_USER']->getTSConfigVal('options.file_list.enableClipBoard') === 'selectable') {
					$pageContent .= ((\TYPO3\CMS\Backend\Utility\BackendUtility::getFuncCheck($this->id, 'SET[clipBoard]', $this->MOD_SETTINGS['clipBoard'], '', '', 'id="checkClipBoard"') . ' <label for="checkClipBoard">') . $GLOBALS['LANG']->getLL('clipBoard', TRUE)) . '</label>';
				}
				$pageContent .= '
					</div>
				';
				// Set clipboard:
				if ($this->MOD_SETTINGS['clipBoard']) {
					$pageContent .= $this->filelist->clipObj->printClipboard();
					$pageContent .= \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'filelist_clipboard', $GLOBALS['BACK_PATH']);
				}
			}
			$markerArray = array(
				'CSH' => $docHeaderButtons['csh'],
				'FUNC_MENU' => \TYPO3\CMS\Backend\Utility\BackendUtility::getFuncMenu($this->id, 'SET[function]', $this->MOD_SETTINGS['function'], $this->MOD_MENU['function']),
				'CONTENT' => $pageContent
			);
			$this->content = $this->doc->moduleBody(array(), $docHeaderButtons, array_merge($markerArray, $otherMarkers));
			// Renders the module page
			$this->content = $this->doc->render($GLOBALS['LANG']->getLL('files'), $this->content);
		} else {
			$content = '';
			if ($this->errorMessage) {
				$content = $this->doc->moduleBody(array(), array_merge(array('LEVEL_UP' => '', 'REFRESH' => ''), $this->getButtons()), array('CSH' => '', 'TITLE' => '', 'FOLDER_INFO' => '', 'PAGE_ICON' => '', 'FUNC_MENU' => '', 'CONTENT' => $this->errorMessage->render()));
			}
			// Create output - no access (no warning though)
			$this->content = $this->doc->render($GLOBALS['LANG']->getLL('files'), $content);
		}
	}

	/**
	 * Outputting the accumulated content to screen
	 *
	 * @return void
	 * @todo Define visibility
	 */
	public function printContent() {
		echo $this->content;
	}

	/**
	 * Create the panel of buttons for submitting the form or otherwise perform operations.
	 *
	 * @return array All available buttons as an assoc. array
	 * @todo Define visibility
	 */
	public function getButtons() {
		$buttons = array(
			'csh' => '',
			'shortcut' => '',
			'upload' => '',
			'new' => ''
		);
		// Add shortcut
		if ($GLOBALS['BE_USER']->mayMakeShortcut()) {
			$buttons['shortcut'] = $this->doc->makeShortcutIcon('pointer,id,target,table', implode(',', array_keys($this->MOD_MENU)), $this->MCONF['name']);
		}
		// FileList Module CSH:
		$buttons['csh'] = \TYPO3\CMS\Backend\Utility\BackendUtility::cshItem('xMOD_csh_corebe', 'filelist_module', $GLOBALS['BACK_PATH'], '', TRUE);
		// Upload button (only if upload to this directory is allowed)
		if (($this->folderObject && $this->folderObject->getStorage()->checkUserActionPermission('upload', 'File')) && $this->folderObject->checkActionPermission('write')) {
			$buttons['upload'] = ((((((((('<a href="' . $GLOBALS['BACK_PATH']) . 'file_upload.php?target=') . rawurlencode($this->folderObject->getCombinedIdentifier())) . '&amp;returnUrl=') . rawurlencode($this->filelist->listURL())) . '" id="button-upload" title="') . $GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:cm.upload', 1))) . '">') . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-edit-upload')) . '</a>';
		}
		// New folder button
		if ($this->folderObject && $this->folderObject->checkActionPermission('add')) {
			$buttons['new'] = ((((((((('<a href="' . $GLOBALS['BACK_PATH']) . 'file_newfolder.php?target=') . rawurlencode($this->folderObject->getCombinedIdentifier())) . '&amp;returnUrl=') . rawurlencode($this->filelist->listURL())) . '" title="') . $GLOBALS['LANG']->makeEntities($GLOBALS['LANG']->sL('LLL:EXT:lang/locallang_core.xml:cm.new', 1))) . '">') . \TYPO3\CMS\Backend\Utility\IconUtility::getSpriteIcon('actions-document-new')) . '</a>';
		}
		return $buttons;
	}

}


?>