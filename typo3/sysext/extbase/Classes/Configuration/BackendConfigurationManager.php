<?php
/***************************************************************
*  Copyright notice
*
*  (c) 2009 Jochen Rau <jochen.rau@typoplanet.de>
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
*
*  This script is distributed in the hope that it will be useful,
*  but WITHOUT ANY WARRANTY; without even the implied warranty of
*  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
*  GNU General Public License for more details.
*
*  This copyright notice MUST APPEAR in all copies of the script!
***************************************************************/

/**
 * A general purpose configuration manager used in backend mode.
 *
 * @package Extbase
 * @subpackage Configuration
 * @version $ID:$
 */
class Tx_Extbase_Configuration_BackendConfigurationManager extends Tx_Extbase_Configuration_AbstractConfigurationManager {

	/**
	 * @var array
	 */
	protected $typoScriptSetupCache = NULL;

	/**
	 * t3lib_queryGenerator is needed to recursively fetch a page tree
	 *
	 * @var t3lib_queryGenerator
	 */
	protected $queryGenerator;

	/**
	 * Inject query generator
	 *
	 * @param t3lib_queryGenerator $queryGenerator
	 */
	public function injectQueryGenerator(t3lib_queryGenerator $queryGenerator) {
		$this->queryGenerator = $queryGenerator;
	}

	/**
	 * Returns TypoScript Setup array from current Environment.
	 *
	 * @return array the raw TypoScript setup
	 */
	public function getTypoScriptSetup() {
		if ($this->typoScriptSetupCache === NULL) {
			$template = t3lib_div::makeInstance('t3lib_TStemplate');
				// do not log time-performance information
			$template->tt_track = 0;
			$template->init();
				// Get the root line
			$sysPage = t3lib_div::makeInstance('t3lib_pageSelect');
				// get the rootline for the current page
			$rootline = $sysPage->getRootLine($this->getCurrentPageId());
				// This generates the constants/config + hierarchy info for the template.
			$template->runThroughTemplates($rootline, 0);
			$template->generateConfig();
			$this->typoScriptSetupCache = $template->setup;
		}
		return $this->typoScriptSetupCache;
	}

	/**
	 * Returns the TypoScript configuration found in module.tx_yourextension_yourmodule
	 * merged with the global configuration of your extension from module.tx_yourextension
	 *
	 * @param string $extensionName
	 * @param string $pluginName in BE mode this is actually the module signature. But we're using it just like the plugin name in FE
	 * @return array
	 */
	protected function getPluginConfiguration($extensionName, $pluginName) {
		$setup = $this->getTypoScriptSetup();
		$pluginConfiguration = array();
		if (is_array($setup['module.']['tx_' . strtolower($extensionName) . '.'])) {
			$pluginConfiguration = Tx_Extbase_Utility_TypoScript::convertTypoScriptArrayToPlainArray($setup['module.']['tx_' . strtolower($extensionName) . '.']);
		}
		$pluginSignature = strtolower($extensionName . '_' . $pluginName);
		if (is_array($setup['module.']['tx_' . $pluginSignature . '.'])) {
			$pluginConfiguration = t3lib_div::array_merge_recursive_overrule($pluginConfiguration, Tx_Extbase_Utility_TypoScript::convertTypoScriptArrayToPlainArray($setup['module.']['tx_' . $pluginSignature . '.']));
		}
		return $pluginConfiguration;
	}

	/**
	 * Returns the configured controller/action pairs of the specified module in the format
	 * array(
	 *  'Controller1' => array('action1', 'action2'),
	 *  'Controller2' => array('action3', 'action4')
	 * )
	 *
	 * @param string $extensionName
	 * @param string $pluginName in BE mode this is actually the module signature. But we're using it just like the plugin name in FE
	 * @return array
	 */
	protected function getSwitchableControllerActions($extensionName, $pluginName) {
		$switchableControllerActions = $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['extbase']['extensions'][$extensionName]['modules'][$pluginName]['controllers'];
		if (!is_array($switchableControllerActions)) {
			$switchableControllerActions = array();
		}
		return $switchableControllerActions;
	}

	/**
	 * Returns the page uid of the current page.
	 * If no page is selected, we'll return the uid of the first root page.
	 *
	 * @return integer current page id. If no page is selected current root page id is returned
	 */
	protected function getCurrentPageId() {
		$pageId = (integer)t3lib_div::_GP('id');
		if ($pageId > 0) {
			return $pageId;
		}

			// get current site root
		$rootPages = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('uid', 'pages', 'deleted=0 AND hidden=0 AND is_siteroot=1', '', '', '1');
		if (count($rootPages) > 0) {
			return $rootPages[0]['uid'];
		}

			// get root template
		$rootTemplates = $GLOBALS['TYPO3_DB']->exec_SELECTgetRows('pid', 'sys_template', 'deleted=0 AND hidden=0 AND root=1', '', '', '1');
		if (count($rootTemplates) > 0) {
			return $rootTemplates[0]['pid'];
		}

			// fallback
		return self::DEFAULT_BACKEND_STORAGE_PID;
	}

	/**
	 * We need to set some default request handler if the framework configuration
	 * could not be loaded; to make sure Extbase also works in Backend modules
	 * in all contexts.
	 *
	 * @return array
	 */
	protected function getContextSpecificFrameworkConfiguration(array $frameworkConfiguration) {
		if (!isset($frameworkConfiguration['mvc']['requestHandlers'])) {
			$frameworkConfiguration['mvc']['requestHandlers'] = array(
				'Tx_Extbase_MVC_Web_FrontendRequestHandler' => 'Tx_Extbase_MVC_Web_FrontendRequestHandler',
				'Tx_Extbase_MVC_Web_BackendRequestHandler' => 'Tx_Extbase_MVC_Web_BackendRequestHandler'
			);
		}
		return $frameworkConfiguration;
	}

	/**
	 * Takes care of extending the list of storage PIDs (persistence.storagePid)
	 * with the PIDs of sub pages for persistence.recursive levels. If recursive
	 * lookup is not configured (persistence.recursive not net or 0),
	 * $frameworkConfiguration is returned as is.
	 *
	 * @param array $frameworkConfiguration
	 * @return array $frameworkConfiguration
	 */
	protected function getRecursiveStoragePids(array $frameworkConfiguration) {
		if (!isset($frameworkConfiguration['persistence']) ||
			!isset($frameworkConfiguration['persistence']['storagePid']) ||
			!isset($frameworkConfiguration['persistence']['recursive']) ||
			$frameworkConfiguration['persistence']['recursive'] == '0') {
			return $frameworkConfiguration;
		}

		$recursiveStoragePids = '';
		$storagePids = t3lib_div::intExplode(',', $frameworkConfiguration['persistence']['storagePid']);
		foreach ($storagePids as $storagePid) {
			$pids = $this->queryGenerator->getTreeList($storagePid, $frameworkConfiguration['persistence']['recursive'], 0, 1);
			if (strlen($pids) > 0) {
				$recursiveStoragePids .= ',' . $pids;
			}
		}

		if (strlen($recursiveStoragePids) > 0) {
			$frameworkConfiguration['persistence']['storagePid'] .= $recursiveStoragePids;
		}
		return $frameworkConfiguration;
	}
}
?>