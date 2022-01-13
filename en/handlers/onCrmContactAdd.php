<?
if (!defined('LANG_ROOT_PATH'))
{
	define('LANG_ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/en');
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/handlers/onCrmContactAddCommon.php');

onCrmContactAddCommon::run();