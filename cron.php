<?
require_once('api/database/DatabaseSite.php');

class Cron
{
	protected static function getSettings()
	{
		$settings = parse_ini_file(LANG_ROOT_PATH . '/settings.ini');

		return array(
			"CLIENT_ID" => $settings['CLIENT_ID'],
			"CLIENT_SECRET" => $settings['CLIENT_SECRET'],
			"OAUTH_URL" => $settings['OAUTH_URL'],
			"DB_COUNT_DAYS_KEEP_BACKUPS" => $settings['DB_COUNT_DAYS_KEEP_BACKUPS'],
            "DB_TABLE_NAME_SITE" => $settings['DB_TABLE_NAME_SITE']
		);
	}

	protected  static  function deleteOldBackups()
	{
		if ($directory = opendir($_SERVER['DOCUMENT_ROOT'] . '/backups/'))
		{
			$currentDate = date('Y-m-d');
			$settings = static::getSettings();
			$timeLife = $settings['DB_COUNT_DAYS_KEEP_BACKUPS'];
			$tableName = $settings['DB_TABLE_NAME_SITE'];

			while (false !== ($file = readdir($directory)))
			{
				if ($file != "." && $file != ".." && $file != '.htaccess')
				{
					$fileName = str_replace(array($tableName . "_", ".sql"), "", $file);

					$diffDays = (strtotime($currentDate) - strtotime($fileName)) / 86400;

					if ($diffDays > $timeLife)
					{
						unlink($_SERVER['DOCUMENT_ROOT'] . '/backups/' . $file);
					}
				}
			}
			closedir($directory);
		}
	}

	// create backup of table and update refresh_token for all entries
	public static function run()
	{
		self::deleteOldBackups();
		DatabaseSite::getInstance()->createBackupTable();
		DatabaseSite::getInstance()->createBackupDB(); // в en и ru дубли будут, но ничего страшного

		$modules = DatabaseSite::getInstance()->getAllModules();
		$settings = static::getSettings();

		foreach ($modules as $module)
		{
			$content = array(
				'grant_type' => 'refresh_token',
				'client_id' => $settings['CLIENT_ID'],
				'client_secret' => $settings['CLIENT_SECRET'],
				'refresh_token' => $module['REFRESH_ID']
			);
			$context = stream_context_create([
				'http' => [
					'method' => 'POST',
					'content' => http_build_query($content),
				],
			]);

			$response = file_get_contents($settings['OAUTH_URL'], false, $context);
			$data = json_decode($response, true);

			if (!$data['error'] && $data['refresh_token'])
			{
				DatabaseSite::getInstance()->updateRefreshID($module['ID'], $data['refresh_token']);
				DatabaseSite::getInstance()->updateDateRefreshID($module['ID'], date('Y-m-d H:i:s'));
			}
		}
	}
}

Cron::run();