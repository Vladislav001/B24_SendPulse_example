<?
//// TODO dev
//$_SERVER['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_ROOT'] . '/develop';

if (!defined('LANG_ROOT_PATH'))
{
	// для en, испанского, португальского и т.п - только en
	if($_POST['lang'] == 'ru')
	{
		define('LANG_ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/ru');
	}
	else
	{
		define('LANG_ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/en');
	}
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/langs.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/sendpulse/ApiClient.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/sendpulse/storage/FileStorage.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/common.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/bitrix24/common.php');

$result = ApiCommon::exportEntityFromB24($_POST);
echo json_encode($result);
return;