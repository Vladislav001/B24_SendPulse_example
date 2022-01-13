<?
if (!defined('LANG_ROOT_PATH'))
{
	define('LANG_ROOT_PATH', $_SERVER['DOCUMENT_ROOT'] . '/en');
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/langs.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/application.php');

try {
	B24Application::run();
} catch (Exception $exception) {
	B24Common::exceptionLog($exception);
}