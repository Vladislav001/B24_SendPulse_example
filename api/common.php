<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/sendpulse/ApiClient.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/sendpulse/storage/FileStorage.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/bitrix24/common.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/bitrix24/custom.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/database/DatabaseUser.php');

use Sendpulse\RestApi\ApiClient;
use Sendpulse\RestApi\Storage\FileStorage;

class ApiCommon
{
	// экспорт сущности с выбранными полями в SendPulse из Битрикс24
	public static function exportEntityFromB24($inputData)
	{
//		B24Common::log('$data exportEntityFromB24', $inputData);

		$checkRequiredFields = self::checkRequiredFields($inputData, array(
			'entity',
			'address_book',
			'fields'
		));

		if ($checkRequiredFields['errors'])
		{
			return $checkRequiredFields;
		}

		$requiredFields = array("EMAIL");
		$fields = explode(',', $inputData['fields']);

		// навсякий дозаполним обяз.полями
		foreach ($requiredFields as $requiredField)
		{
			if (!in_array($requiredField, $fields))
			{
				$fields[] = $requiredField;
			}
		}

		$inputData['fields'] = $fields;

		// в зависимости от сущности - найти контакты || компании
		switch ($inputData['entity'])
		{
			case B24Common::CONTACT_ENTITY_TYPE_ID:
				$entityData = B24Custom::getAllContacts($fields);
				$fieldsData = B24Custom::getContactFields()["result"];
				break;
			case B24Common::COMPANY_ENTITY_TYPE_ID:
				$entityData = B24Custom::getAllCompanies($fields);
				$fieldsData = B24Custom::getCompanyFields()["result"];
				break;
		}

		// если не нужно экспортировать записи, у которых почта встречается более 1 раза
		if ($inputData['export_duplicates'] == 'false' && count($entityData) > 0)
		{
			$emailKeys = array();

			// распределим ключи по почтам
			foreach ($entityData as $keyRecord => $recordData)
			{
				$emailKeys[$recordData['EMAIL'][0]['VALUE']][] = $keyRecord;
			}

			// удалим записи, у которых почта встречается более, чем в 1 записи
			foreach ($emailKeys as $email => $keys)
			{
				if (count($keys) > 1)
				{
					foreach ($keys as $key)
					{
						unset($entityData[$key]);
					}
				}
			}
		}

		// проверка что записей по сущности > 0
		if (count($entityData) == 0)
		{
			return array(
				"errors" => array(
					array(
						"title" => getMessage("NO_RECORDS_FOUND_TO_EXPORT")
					)
				)
			);
		}

		$emails = array();

		foreach ($entityData as $entityFields)
		{
			$email = $entityFields['EMAIL'][0]['VALUE'];

			if ($email)
			{
				unset($entityFields['EMAIL']);
				unset($entityFields['ID']);

				// зададим полям человекопонятные названия, вместо ID
				foreach ($entityFields as $fieldID => $fieldValue)
				{
					if (is_array($fieldValue))
					{
						$fieldValue = $fieldValue[0]['VALUE'];
					}

					if ($fieldsData[$fieldID]['isDynamic'])
					{
						$key = $fieldsData[$fieldID]['listLabel'];
					} else
					{
						$key = $fieldsData[$fieldID]['title'];
					}

					// для en версии
					if ($key == 'First name')
					{
						$key = 'Name';
					}

					// т.к в SendPulse уже есть поля по дефолту
					if ($fieldID != "NAME" && $fieldID != "Phone")
					{
						$key .= " (" . $fieldID . ")"; //  экспорт названиями как на фронте
					}

					$entityFields[$key] = $fieldValue;

					// Для добавления номера телефона необходимо использовать системную переменную Phone
					if ($fieldID == 'PHONE')
					{
						$entityFields['Phone'] = $fieldValue;

						unset($entityFields[$key]);
					}

					unset($entityFields[$fieldID]);
				}

				$emails[] = array(
					"email" => $email,
					"variables" => $entityFields
				);
			}
		}

		$emails = array_chunk($emails, 900);

		// в массиве должно быть не больше 1000 записей
		foreach ($emails as $emailChunks)
		{
			$SPApiClient = new ApiClient($inputData['id'], $inputData['secret'], new FileStorage());
			$result = $SPApiClient->addEmails($inputData['address_book'], $emailChunks);

			if (!$result->result)
			{
				$title = getMessage("UNEXPECTED_ERROR_OCCURRED");

				// слишком частые запросы
				if ($result->error == "duplicate_request")
				{
					$title = getMessage("DUPLICATE_REQUEST");
				}

				ApiClient::exceptionLog(array_merge($inputData, (array)$result));

				return array(
					"errors" => array(
						array(
							"title" => $title
						)
					)
				);
			}
		}

		return array("success" => getMessage("DATA_EXPORTED_SUCCESSFULLY"));
	}

	// импорт сущности с выбранными полями в Битрикс24 из SendPulse
	public static function importEntityToB24($inputData)
	{
//		B24Common::log('$data importEntityToB24', $inputData);

		$checkRequiredFields = self::checkRequiredFields($inputData, array(
			'entity',
			'address_book',
			'fields'
		));
		if ($checkRequiredFields['errors'])
		{
			return $checkRequiredFields;
		}

		$requiredFields = array("EMAIL");
		$fields = explode(',', $inputData['fields']);

		// навсякий дозаполним обяз.полями
		foreach ($requiredFields as $requiredField)
		{
			if (!in_array($requiredField, $fields))
			{
				$fields[] = $requiredField;
			}
		}

		$inputData['fields'] = $fields;

		$SPApiClient = new ApiClient($inputData['id'], $inputData['secret'], new FileStorage());

		// получать все записи (лимит и смещение) и потом добавлять
		$limit = 100;
		$offset = 0;
		$sendPulseData = array();

		do
		{
			$data = $SPApiClient->getEmailsFromBook($inputData['address_book'], $limit, $offset);
			$data = self::arrayCastRecursive($data);

			if (!empty($data))
			{
				$sendPulseData = array_merge($sendPulseData, $data);

				$offset += $limit;
			} else
			{
				$offset = 0;
			}

		} while ($offset > 0);

		// проверка что записей по сущности > 0
		if (count($sendPulseData) == 0)
		{
			return array(
				"errors" => array(
					array(
						"title" => getMessage("NO_RECORDS_FOUND_TO_IMPORT")
					)
				)
			);
		}

		// найти все контакты || все компании
		switch ($inputData['entity'])
		{
			case B24Common::CONTACT_ENTITY_TYPE_ID:
				$b24Data = B24Custom::getAllContacts($fields);
				break;
		}

		// т.к в Б24 могут быть совпадения по email - обработаем данные + разнесем по email
		$b24ProcessedData = array();

		foreach ($b24Data as $record)
		{
			if ($record['EMAIL'])
			{
				$email = mb_strtolower($record['EMAIL'][0]['VALUE']);
				$data = array(
					"ID" => $record['ID'],
					"NAME" => $record['NAME'],
				);

				if ($record['PHONE'])
				{
					$data['PHONE'] = $record['PHONE'][0]['VALUE'];
				}

				$b24ProcessedData[$email][] = $data;
			}
		}

		// обработаем variables + разнесем по email
		$sendPulseProcessedData = array();

		foreach ($sendPulseData as $record)
		{
			$data = array();

			$data['EMAIL'][0] = array(
				"VALUE" => $record['email'],
				"VALUE_TYPE" => "WORK"
			);

			// т.к имя в SendPulse дефолтное
			if ($record['variables']['имя'])
			{
				$data['NAME'] = $record['variables']['имя'];
			} elseif ($record['variables']['Name'])
			{
				$data['NAME'] = $record['variables']['Name'];
			}

			if ($record['phone'])
			{
				$data['PHONE'][0] = array(
					"VALUE" => $record['phone'],
					"VALUE_TYPE" => "WORK"
				);
			}

			$sendPulseProcessedData[$record['email']] = $data;
		}

	//	self::logToFile($sendPulseProcessedData, '$sendPulseProcessedData_emails.log');

		$updateCount = 0;
		$addCount = 0;

		$b24ProcessedDataKeys = array_keys($b24ProcessedData);

		foreach ($sendPulseProcessedData as $sendPulseEmail => $sendPulseRecord)
		{
			if (in_array($sendPulseEmail, $b24ProcessedDataKeys))
			{
				// надо тут условие, т.к иначе сработает далее и добавятся дубли по email
				if ($inputData['update_existing'] == 'true')
				{
					$updateArr = $b24ProcessedData[$sendPulseEmail];

					// обновляем
					switch ($inputData['entity'])
					{
						case B24Common::CONTACT_ENTITY_TYPE_ID:
							foreach ($updateArr as $b24Record)
							{
								// не имеет смысла обновлять запись у которой только email в SendPulse
								if (count($sendPulseRecord) > 1)
								{
									unset($sendPulseRecord['EMAIL']); // нет смысла обновлять поле на тоже самое значение

									// не обновлять поля в Б24, которые в SendPulse пустые или не выбрали
									foreach ($sendPulseRecord as $key => $value)
									{
										if (!$value || !in_array($key, $inputData['fields']))
										{
											unset($sendPulseRecord[$key]);
										}
									}

									// если значения полей не изменились - то все равно обновится
									B24Custom::updateContact($b24Record['ID'], $sendPulseRecord);
								}
							}
							break;
					}

					$updateCount++;
				}
			} else
			{
				// добавляем
				if (!$sendPulseRecord['NAME'])
				{
					$sendPulseRecord['NAME'] = getMessage('EMPTY_NAME');
				}

				switch ($inputData['entity'])
				{
					case B24Common::CONTACT_ENTITY_TYPE_ID:
						B24Custom::addContact($sendPulseRecord);
						break;
				}

				$addCount++;
			}

		}

		return array(
			"success" => getMessage("DATA_IMPORTED_SUCCESSFULLY", [
				'#COUNT_ADD#' => $addCount,
				'#COUNT_UPDATE#' => $updateCount
			])
		);
	}

	// экспорт 1 записи определенной сущности с выбранными полями в SendPulse из Битрикс24
	public static function exportRecordFromB24($record)
	{
		$checkRequiredFields = self::checkRequiredFields($record, array(
			'sendpulse_id',
			'sendpulse_secret',
			'address_book',
			'fields',
			'data'
		));

		if ($checkRequiredFields['errors'])
		{
			// TODO мб лог писать
			return;
		}

		// в зависимости от сущности - найти контакты || компании
		switch ($record['entity'])
		{
			case B24Common::CONTACT_ENTITY_TYPE_ID:
				$fieldsData = B24Custom::getContactFields()["result"];
				break;
			case B24Common::COMPANY_ENTITY_TYPE_ID:
				$fieldsData = B24Custom::getCompanyFields()["result"];
				break;
		}

		$email = $record['data']['EMAIL'][0]['VALUE'];

		if(!$email)
		{
			return;
		}

		unset($record['data']['EMAIL']);
		unset($record['data']['ID']);

		// зададим полям человекопонятные названия, вместо ID
		foreach ($record['data'] as $fieldID => $fieldValue)
		{
			// пропустить поля, которые не выбраны для экспорта
			if (!in_array($fieldID, $record['fields']))
			{
				unset($record['data'][$fieldID]);
				continue;
			}

			if (is_array($fieldValue))
			{
				$fieldValue = $fieldValue[0]['VALUE'];
			}

			if ($fieldsData[$fieldID]['isDynamic'])
			{
				$key = $fieldsData[$fieldID]['listLabel'];
			} else
			{
				$key = $fieldsData[$fieldID]['title'];
			}

			// для en версии
			if ($key == 'First name')
			{
				$key = 'Name';
			}

			// т.к в SendPulse уже есть поля по дефолту
			if ($fieldID != "NAME" && $fieldID != "Phone")
			{
				$key .= " (" . $fieldID . ")"; //  экспорт названиями как на фронте
			}

			// т.к если выбрано пустое поле для экспорта, то экспортнем "", а то могут не понять почему в SendPulse не появилось поле
			$record['data'][$key] = $fieldValue;

			// Для добавления номера телефона необходимо использовать системную переменную Phone
			if ($fieldID == 'PHONE')
			{
				$record['data']['Phone'] = $fieldValue;

				unset($record['data'][$key]);
			}

			unset($record['data'][$fieldID]);
		}

		$exportData = array(
			"email" => $email,
			"variables" => $record['data']
		);

		$SPApiClient = new ApiClient($record['sendpulse_id'], $record['sendpulse_secret'], new FileStorage());
		$result = $SPApiClient->addEmails($record['address_book'], array($exportData));

		if ($result->result)
		{
			return array("success" => getMessage("DATA_EXPORTED_SUCCESSFULLY"));
		} else
		{
			$title = getMessage("UNEXPECTED_ERROR_OCCURRED");

			// слишком частые запросы
			if ($result->error == "duplicate_request")
			{
				$title = getMessage("DUPLICATE_REQUEST");
			}

			ApiClient::exceptionLog(array_merge($record, (array)$result));

			return array(
				"errors" => array(
					array(
						"title" => $title
					)
				)
			);
		}
	}

	protected static function checkRequiredFields(array $inputFields, array $requiredFields)
	{
		foreach ($requiredFields as $requiredField)
		{
			if (!$inputFields[$requiredField])
			{
				return array(
					"errors" => array(
						array(
							"title" => getMessage("FILL_IN_REQUIRED_FIELDS")
						)
					)
				);
			}
		}

		return true;
	}

	// преобразовать объект в массив (любой уровень вложенности)
	protected static function arrayCastRecursive($array)
	{
		if (is_array($array))
		{
			foreach ($array as $key => $value)
			{
				if (is_array($value))
				{
					$array[$key] = self::arrayCastRecursive($value);
				}
				if ($value instanceof stdClass)
				{
					$array[$key] = self::arrayCastRecursive((array)$value);
				}
			}
		}
		if ($array instanceof stdClass)
		{
			return self::arrayCastRecursive((array)$array);
		}
		return $array;
	}

	// для тестирования
	protected static function generateContacts($count)
	{
		for ($i = 0; $i < $count; $i++)
		{
			$data = array(
				"NAME" => "Сгенерированное имя " . $i,
			);

			$data['EMAIL'][0] = array(
				"VALUE" => "generateEmail" . $i . "@mail.ru",
				"VALUE_TYPE" => "WORK"
			);

			B24Custom::addContact($data);
		}
	}

	// для тестирования
	protected static function generateCompanies($count)
	{
		for ($i = 0; $i < $count; $i++)
		{
			$data = array(
				"TITLE" => "Сгенерированное имя компании " . $i,
			);

			$data['EMAIL'][0] = array(
				"VALUE" => "generateEmail" . $i . "@mail.ru",
				"VALUE_TYPE" => "WORK"
			);

			B24Custom::addCompany($data);
		}
	}

	public static function logToFile($data, $file = 'data_log.log')
	{
		$tempFile = fopen(LANG_ROOT_PATH . '/logs/' . $file, 'a');
		fwrite($tempFile, _FILE_ . ':' . _LINE_ . PHP_EOL . '(' . date('Y-m-d H:i:s') . ')' . PHP_EOL . print_r($data, TRUE) . PHP_EOL . PHP_EOL);
		fclose($tempFile);
	}
}