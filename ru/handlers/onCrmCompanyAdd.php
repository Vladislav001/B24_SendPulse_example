<?
// TODO dev
//$_SERVER['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_ROOT'] . '/develop';

if (!defined('LANG_ROOT_PATH'))
{
	define('LANG_ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/ru');
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/handlers/onCrmCompanyAddCommon.php');

onCrmCompanyAddCommon::run();