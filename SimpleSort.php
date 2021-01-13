<?php

if ( function_exists( 'wfLoadExtension' ) ) {
	wfLoadExtension( 'SimpleSort' );
	// Keep i18n globals so mergeMessageFileList.php doesn't break
	$wgMessagesDirs['SimpleSort'] = __DIR__ . '/i18n';
	$wgExtensionMessagesFiles['SimpleSortMagic'] = __DIR__ . '/SimpleSort.i18n.magic.php';
	wfWarn(
		'Deprecated PHP entry point used for the SimpleSort extension. ' .
		'Please use wfLoadExtension() instead, ' .
		'see https://www.mediawiki.org/wiki/Special:MyLanguage/Manual:Extension_registration for more details.'
	);
	return true;
} else {
	die( 'This version of the SimpleSort extension requires MediaWiki 1.25+' );
}
