<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/database/DatabaseSite.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/bitrix24/common.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/sendpulse/ApiClient.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/sendpulse/storage/FileStorage.php');

class B24Application
{
	protected static function getSettings()
	{
        $settings = parse_ini_file(LANG_ROOT_PATH . '/settings.ini');
        return array(
			"DEBUG_MODE" => $settings['DEBUG_MODE'],
			"SALT" => $settings['SALT'],
			"DAYS_TRIAL_VERSION" => $settings['DAYS_TRIAL_VERSION']
		);
	}

	public static function install()
	{
		$errors = [];

		try
		{
			$domainID = DatabaseSite::getInstance()->getDomainID($_REQUEST['DOMAIN']);

			if (!$domainID)
			{
				// никогда не устанавливали (нет в бд)
				DatabaseSite::getInstance()->addB24($_REQUEST);
				B24Common::log(getMessage('MODULE_INSTALLED'), $_REQUEST);
			} else
			{
				// переустановка
				B24Common::log(getMessage('MODULE_REINSTALLED'), $_REQUEST);
				DatabaseSite::getInstance()->updateAuthID($domainID, $_REQUEST['AUTH_ID']);
				DatabaseSite::getInstance()->updateRefreshID($domainID, $_REQUEST['REFRESH_ID']);
				DatabaseSite::getInstance()->updateDateRefreshID($domainID, date('Y-m-d H:i:s'));
			}

		} catch (Exception $exception)
		{
			$errors[] = $exception->getMessage();
		}

		return $errors;
	}

	public static function run()
	{
		$settings = static::getSettings();
		$module = DatabaseSite::getInstance()->getModuleInformationByDomain($_REQUEST['DOMAIN'], $_REQUEST['member_id']);
		$isPaid = DatabaseSite::getInstance()->moduleIsPaid($module['ID']);
		$isExpiredTrialVersion = DatabaseSite::getInstance()->moduleTrialIsExpired($module['ID'], $settings['DAYS_TRIAL_VERSION']);

		if (!$isExpiredTrialVersion || $isPaid)
		{
			require "templates/index.php";
		}
		else
		{
			require 'templates/trial_expired.php';
		}

	}
}