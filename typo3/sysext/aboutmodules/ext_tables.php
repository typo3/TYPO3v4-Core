<?php
if (!defined ('TYPO3_MODE')) {
	die ('Access denied.');
}

	// Avoid that this block is loaded in frontend or within upgrade wizards
if (TYPO3_MODE === 'BE' && !(TYPO3_REQUESTTYPE & TYPO3_REQUESTTYPE_INSTALL)) {
	Tx_Extbase_Utility_Extension::registerModule(
		$_EXTKEY,
		'help',
		'aboutmodules',
		'after:about',
		array(
			'Modules' => 'index',
		),
		array(
			'access' => 'user,group',
			'icon' => 'EXT:aboutmodules/Resources/Public/Images/moduleicon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf',
		)
	);
}

?>