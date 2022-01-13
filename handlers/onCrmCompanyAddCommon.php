<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/langs.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/common.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/bitrix24/common.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/bitrix24/custom.php');

class onCrmCompanyAddCommon
{
	public static function run()
	{
		if ($_REQUEST['event'] == 'ONCRMCOMPANYADD' && $_REQUEST['data']['FIELDS']['ID'])
		{
			// получить настройки приложения
			$_REQUEST['DOMAIN'] = $_REQUEST['auth']['domain'];
			$_REQUEST['member_id'] = $_REQUEST['auth']['member_id'];

			// проверка что оплачено или не истекла триалка
			$settings = parse_ini_file(LANG_ROOT_PATH . '/settings.ini');
			$module = Database::getInstance()->getModuleInformationByDomain($_REQUEST['DOMAIN'], $_REQUEST['member_id']);

			$isPaid = Database::getInstance()->moduleIsPaid($module['ID']);
			$isExpiredTrialVersion = Database::getInstance()->moduleTrialIsExpired($module['ID'], $settings['DAYS_TRIAL_VERSION']);

			if (!$isExpiredTrialVersion || $isPaid)
			{
				B24Common::log('ONCRMCOMPANYADD_REQUEST', $_REQUEST);

				try
				{
					$companyData = B24Custom::getCompanyByID($_REQUEST['data']['FIELDS']['ID']);
				} catch (Exception $e)
				{
					B24Common::log('Exception getCompanyByID', $e);
				}

				$options = B24Common::getApplicationOptions();

				if ($companyData['HAS_EMAIL'] == 'N')
				{
					B24Common::log('ONCRMCOMPANYADD_REQUEST_EMPTY_EMAIL_COMPANY', array(
						"DOMAIN" => $_REQUEST['DOMAIN'],
						"COMPANY_ID" => $companyData['ID']
					));

					return;
				}

				// проверка, что заданые необходимые условия для экспорта
				if ($options['AUTO_EXPORT_COMPANIES'] == 'true' && $options['SEND_PULSE_ID'] && $options['SEND_PULSE_SECRET'] && $options['AUTO_EXPORT_COMPANIES_SENDPULSE_BOOK'] && count($options['AUTO_EXPORT_COMPANIES_FIELDS']) > 0)
				{
					$record = array(
						'entity' => B24Common::CONTACT_ENTITY_TYPE_ID,
						'sendpulse_id' => $options['SEND_PULSE_ID'],
						'sendpulse_secret' => $options['SEND_PULSE_SECRET'],
						'address_book' => $options['AUTO_EXPORT_COMPANIES_SENDPULSE_BOOK'],
						'fields' => $options['AUTO_EXPORT_COMPANIES_FIELDS'],
						'data' => $companyData
					);

					// экспортировать в SendPulse
					$result = ApiCommon::exportRecordFromB24($record);
					B24Common::log('ONCRMCOMPANYADD_RESULT', array_merge($result, array(
						"DOMAIN" => $_REQUEST['DOMAIN'],
						"COMPANY_ID" => $companyData['ID']
					)));
				} else
				{
					B24Common::log('ONCRMCOMPANYADD_NOT_FULL_OPTIONS', array(
						"DOMAIN" => $_REQUEST['DOMAIN'],
					));
				}
			} else
			{
				B24Common::log('ONCRMCOMPANYADD_REQUEST_TRIAL_EXPIRED', $_REQUEST['DOMAIN']);
				return;
			}
		}
	}
}