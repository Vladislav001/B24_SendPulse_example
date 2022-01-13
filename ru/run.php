<?
//// TODO dev
////$_SERVER['DOCUMENT_ROOT'] = $_SERVER['DOCUMENT_ROOT'] . '/develop';
///
//ini_set('error_reporting', E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);

if (!defined('LANG_ROOT_PATH'))
{
	define('LANG_ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/ru');
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/langs.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/application.php');

try {
	B24Application::run();
} catch (Exception $exception) {
	B24Common::exceptionLog($exception);
}