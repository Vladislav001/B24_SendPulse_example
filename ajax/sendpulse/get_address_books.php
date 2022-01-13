<?
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

use Sendpulse\RestApi\ApiClient;
use Sendpulse\RestApi\Storage\FileStorage;

try {
	$SPApiClient = new ApiClient($_POST['id'], $_POST['secret'], new FileStorage());
	$result = $SPApiClient->listAddressBooks();
} catch (Exception $e) {
	if ($e->getMessage() == 'Could not connect to api, check your ID and SECRET')
	{
		//ApiClient::exceptionLog($e);
		$result['errors'][] = array("title" => getMessage("INVALID_ID_OR_SECRET"));
	}
}

echo json_encode($result);
return;