<?
if (!$_SERVER['DOCUMENT_ROOT'])
{
	$_SERVER['DOCUMENT_ROOT'] = dirname(__FILE__);
}

if (!defined('LANG_ROOT_PATH'))
{
	define('LANG_ROOT_PATH', $_SERVER['DOCUMENT_ROOT']);
}

require_once(dirname(dirname(__FILE__)) . '/cron.php');