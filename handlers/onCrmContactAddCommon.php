<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/langs.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/common.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/bitrix24/common.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/bitrix24/custom.php');

class onCrmContactAddCommon
{
	public static function run()
	{
		if ($_REQUEST['event'] == 'ONCRMCONTACTADD' && $_REQUEST['data']['FIELDS']['ID'])
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
				B24Common::log('ONCRMCONTACTADD_REQUEST', $_REQUEST);

				try
				{
					$contactData = B24Custom::getContactByID($_REQUEST['data']['FIELDS']['ID']);
				} catch (Exception $e)
				{
					B24Common::log('Exception getContactByID', $e);
				}

				$options = B24Common::getApplicationOptions();

				if ($contactData['HAS_EMAIL'] == 'N')
				{
					B24Common::log('ONCRMCONTACTADD_REQUEST_EMPTY_EMAIL_CONTACT', array(
						"DOMAIN" => $_REQUEST['DOMAIN'],
						"CONTACT_ID" => $contactData['ID']
					));

					return;
				}

				// проверка, что заданые необходимые условия для экспорта
				if ($options['AUTO_EXPORT_CONTACTS'] == 'true' && $options['SEND_PULSE_ID'] && $options['SEND_PULSE_SECRET'] && $options['AUTO_EXPORT_CONTACTS_SENDPULSE_BOOK'] && count($options['AUTO_EXPORT_CONTACTS_FIELDS']) > 0)
				{
					$record = array(
						'entity' => B24Common::CONTACT_ENTITY_TYPE_ID,
						'sendpulse_id' => $options['SEND_PULSE_ID'],
						'sendpulse_secret' => $options['SEND_PULSE_SECRET'],
						'address_book' => $options['AUTO_EXPORT_CONTACTS_SENDPULSE_BOOK'],
						'fields' => $options['AUTO_EXPORT_CONTACTS_FIELDS'],
						'data' => $contactData
					);

					// экспортировать в SendPulse
					$result = ApiCommon::exportRecordFromB24($record);
					B24Common::log('ONCRMCONTACTADD_RESULT', array_merge($result, array(
						"DOMAIN" => $_REQUEST['DOMAIN'],
						"CONTACT_ID" => $contactData['ID']
					)));
				} else
				{
					B24Common::log('ONCRMCONTACTADD_NOT_FULL_OPTIONS', array(
						"DOMAIN" => $_REQUEST['DOMAIN'],
					));
				}
			} else
			{
				B24Common::log('ONCRMCONTACTADD_REQUEST_TRIAL_EXPIRED', $_REQUEST['DOMAIN']);
				return;
			}
		}
	}
}