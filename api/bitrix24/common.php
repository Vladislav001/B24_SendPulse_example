<?
require_once($_SERVER['DOCUMENT_ROOT'] . '/api/database/DatabaseSite.php');

class B24Common
{
	private static $NUMBER_ATTEMPTS = 0;
	protected static $TIMEOUT = 10; //seconds
	protected static $applicationOptions = false;

	const LEAD_ENTITY_TYPE_ID = '1';
	const DEAL_ENTITY_TYPE_ID = '2';
	const CONTACT_ENTITY_TYPE_ID = '3';
	const COMPANY_ENTITY_TYPE_ID = '4';

	protected static function getSettings()
	{
		$settings = parse_ini_file(LANG_ROOT_PATH . '/settings.ini');
		$module = DatabaseSite::getInstance()->getModuleInformationByDomain($_REQUEST['DOMAIN'], $_REQUEST['member_id']);

		return array(
			"COUNT_DAYS_KEEP_LOGS" => $settings['COUNT_DAYS_KEEP_LOGS'],
			"DEBUG_MODE" => $settings['DEBUG_MODE'],
			"URL_BITRIX24" => $module['URL_BITRIX24'],
			"AUTH_ID" => $module['AUTH_ID'],
			"REFRESH_ID" => $module['REFRESH_ID'],
			"CLIENT_ID" => $settings['CLIENT_ID'],
			"CLIENT_SECRET" => $settings['CLIENT_SECRET'],
			"MODULE_ID" => $module['ID'],
			"PROTOCOL" => 'https://',
			"OAUTH_URL" => $settings['OAUTH_URL']
		);
	}

	public static function getApplicationOptions()
	{
		if (!self::$applicationOptions) {
			$data = static::request('app.option.get', 'POST', array());
			self::$applicationOptions = $data['result'];
		}

		return self::$applicationOptions;
	}

	protected static function request($methodBX24, $httpMethod, array $content)
	{
		$settings = static::getSettings();

		if ($settings['URL_BITRIX24'] && $settings['AUTH_ID'])
		{
			$url = $settings['PROTOCOL'] . $settings['URL_BITRIX24'] . '/rest/' . $methodBX24;

			$content['access_token'] = $settings['AUTH_ID'];
			$context = stream_context_create([
				'http' => [
					'method' => $httpMethod,
					'content' => http_build_query($content),
					'header' => 'Content-Type: application/x-www-form-urlencoded',
					'timeout' => self::$TIMEOUT,
				],
			]);

			$response = file_get_contents($url, false, $context);

			static::log('B24 request', [
				'method' => $methodBX24,
				'params' => $content,
				'response' => $response,
				'url' => $settings['URL_BITRIX24'],
				//'headers' => $http_response_header,
			]);
			static::deleteOldLogs();

			$data = json_decode($response, true);

			// the extension of the access_token and refresh_token if necessary
			if (empty($data))
			{
				if (self::$NUMBER_ATTEMPTS == 0)
				{
					static::refreshOAuth();
					self::$NUMBER_ATTEMPTS++;
					$data = self::request($methodBX24, $httpMethod, $content);
				} else
				{
					static::log('REST-API request failed with status', $http_response_header[0]);
					throw new ErrorException('REST-API request failed with status ' . $http_response_header[0]);
				}
			}

			return $data;
		}

		return array();
	}

	// The extension of the OAuth 2.0 authorization
	protected static function refreshOAuth()
	{
		$settings = static::getSettings();

		if ($settings['CLIENT_ID'] && $settings['CLIENT_SECRET'] && $settings['REFRESH_ID'])
		{
			$content = array(
				'grant_type' => 'refresh_token',
				'client_id' => $settings['CLIENT_ID'],
				'client_secret' => $settings['CLIENT_SECRET'],
				'refresh_token' => $settings['REFRESH_ID']
			);
			$context = stream_context_create([
				'http' => [
					'method' => 'POST',
					'content' => http_build_query($content),
					'header' => 'Content-Type: application/x-www-form-urlencoded',
					'timeout' => self::$TIMEOUT,
				],
			]);

			$response = file_get_contents($settings['OAUTH_URL'], false, $context);
			$data = json_decode($response, true);

			if (!$data['error'] && $data['access_token'])
			{
				DatabaseSite::getInstance()->updateAuthID($settings['MODULE_ID'], $data['access_token']);
				return true;
			}
		}

		return false;
	}

	/**
	 * Log to file
	 *
	 * @param string $name
	 * @param mixed $data
	 */
	public static function log($name, $data)
	{
		$settings = static::getSettings();
		if ($settings['DEBUG_MODE'])
		{
			$tempFile = fopen(static::getLogFilePath(), 'a');

			if ($tempFile)
			{
				fwrite($tempFile, __METHOD__ . PHP_EOL . '(' . date('Y-m-d H:i:s') . ')' . PHP_EOL . $name . ' = ' . PHP_EOL . print_r($data, true) . PHP_EOL . PHP_EOL);
				fclose($tempFile);
			}
		}
	}

	public static function exceptionLog($exception) {

		$tempFile = fopen(static::getLogErrorFilePath(), 'a');

		if ($tempFile) {
			fwrite(
				$tempFile,
				__METHOD__ . PHP_EOL . '(' . date('Y-m-d H:i:s') . ')' . PHP_EOL
				. print_r($exception, true)
				. PHP_EOL . PHP_EOL
			);
			fclose($tempFile);
		}

	}

	protected static function getLogFilePath()
	{
		return LANG_ROOT_PATH . '/logs/bitrix24/' . date('Y-m-d') . '.log';
	}

	protected static function getLogErrorFilePath()
	{
		return LANG_ROOT_PATH . '/logs/bitrix24/errors/' . date('Y-m-d') . '.log';
	}

	protected static function deleteOldLogs()
	{
		if ($directory = opendir(LANG_ROOT_PATH . '/logs/bitrix24/'))
		{
			$currentDate = date('Y-m-d');
			$settings = static::getSettings();
			$timeLife = $settings['COUNT_DAYS_KEEP_LOGS'];

			while (false !== ($file = readdir($directory)))
			{
				if ($file != "." && $file != ".." && $file != '.htaccess')
				{
					$fileName = stristr($file, '.log', true);
					$diffDays = (strtotime($currentDate) - strtotime($fileName)) / 86400;

					// чтобы не было warning для папки errors
					if ($diffDays > $timeLife && !is_dir(LANG_ROOT_PATH . '/logs/bitrix24/' . $file))
					{
						unlink(LANG_ROOT_PATH . '/logs/bitrix24/' . $file);
					}
				}
			}
			closedir($directory);
		}
	}

	//TODO
	// protected static function deleteOldLogsErrors()
}