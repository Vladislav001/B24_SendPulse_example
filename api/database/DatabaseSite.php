<?
require_once "DatabaseCommon.php";

class DatabaseSite extends DatabaseCommon
{
	protected function getInstallTableName()
	{
		$settigs = self::getSettings();
		$result = $settigs['DB_TABLE_NAME_SITE'];
		return $result;
	}

	protected function checkDatabase()
	{
		$tableName = self::getInstallTableName();
		$sql = "CREATE TABLE IF NOT EXISTS $tableName (
			ID INT(11) NOT NULL AUTO_INCREMENT,
			URL_BITRIX24 VARCHAR(2000),
			DATE_INSTALL TIMESTAMP NOT NULL DEFAULT current_timestamp,
			DATE_PAYMENT TIMESTAMP,
			EXTENSION_PERIOD_MONTH INT(6) DEFAULT 0,
			MEMBER_ID VARCHAR(2000),
			AUTH_ID VARCHAR(2000),
			REFRESH_ID VARCHAR(2000),
			DATE_UPDATE_REFRESH_ID TIMESTAMP,
			PRIMARY KEY (ID)
		);";
		$result = $this->connection->query($sql);

		if (!$result)
		{
			throw new \Exception('Database query error: ' . $this->connection->error);
		}
	}

	/**
	 * The method adds information about cloud B24
	 * @param array $data data of $_REQUEST
	 */
	public function addB24($data)
	{
		$this->checkDatabase();
		$tableName = self::getInstallTableName();
		$stmt = $this->connection->prepare("INSERT INTO $tableName (URL_BITRIX24, MEMBER_ID, AUTH_ID, REFRESH_ID) VALUES(?, ?, ?, ?)");
		$stmt->bind_param('ssss', $data['DOMAIN'], $data['member_id'], $data['AUTH_ID'], $data['REFRESH_ID']);
		$stmt->execute();
	}

	/**
	 * The method returns the record ID with the previously installed application for the domain
	 * @param string $domain
	 * @return int
	 */
	public function getDomainID($domain)
	{
		$tableName = self::getInstallTableName();
		$this->checkDatabase();
		$sql = "SELECT * FROM $tableName WHERE URL_BITRIX24=? LIMIT 1";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param('s', $domain);
		$stmt->execute();
		$result = $stmt->get_result()->fetch_assoc();

		return $result;
	}

	/**
	 * The method returns module information by domain and member_id
	 * @param string $domain
	 * @param string $memberID
	 * @return array
	 */
	public function getModuleInformationByDomain($domain, $memberID)
	{
		$this->checkDatabase();
		$tableName = self::getInstallTableName();
		$sql = "SELECT * FROM $tableName WHERE URL_BITRIX24=? AND MEMBER_ID=?";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param('ss', $domain, $memberID);
		$stmt->execute();
		$result = $stmt->get_result();
		return $result->fetch_assoc();
	}

	/**
	 * The method checks whether the trial version of the module for the domain has expired
	 * @param int $id
	 * @param int $daysTrialVersion
	 * @return bool
	 */
	public function moduleTrialIsExpired($id, $daysTrialVersion)
	{
		$this->checkDatabase();
		$tableName = self::getInstallTableName();
		$sql = "SELECT * FROM $tableName WHERE ID=?";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$result = $stmt->get_result()->fetch_assoc();

		$dateInstall = strtotime(date('Y-m-d', strtotime($result['DATE_INSTALL'])));
		$currentDate = strtotime(date('Y-m-d'));
		$secs = $currentDate - $dateInstall;
		$days = $secs / 86400;

		if ($days > $daysTrialVersion)
		{
			return true;
		}

		return false;
	}

	/**
	 * The method checks whether the module is paid
	 * @param int $id
	 * @return bool
	 */
	public function moduleIsPaid($id)
	{
		$this->checkDatabase();
		$tableName = self::getInstallTableName();
		$sql = "SELECT * FROM $tableName WHERE ID=?";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param('i', $id);
		$stmt->execute();
		$result = $stmt->get_result()->fetch_assoc();

		if ($result['DATE_PAYMENT'] != '0000-00-00 00:00:00')
		{
			$currentDate = strtotime(date('Y-m-d'));
			$datePayment = strtotime(date('Y-m-d', strtotime($result['DATE_PAYMENT'])));
			$secs = $currentDate - $datePayment;
			$days = $secs / 86400;
			$months = $days / 31;

			if ($months < $result['EXTENSION_PERIOD_MONTH'])
			{
				return true;
			}
		}

		return false;
	}

	/**
	 * The method updates AUTH_ID
	 * @param int $id
	 * @param string $authID
	 */
	public function updateAuthID($id, $authID)
	{
		$tableName = self::getInstallTableName();
		$sql = "UPDATE $tableName SET AUTH_ID=? WHERE ID=?";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param('si', $authID, $id);
		$stmt->execute();
	}

	/**
	 * The method updates REFRESH_ID
	 * @param int $id
	 * @param string $authID
	 */
	public function updateRefreshID($id, $refreshID)
	{
		$tableName = self::getInstallTableName();
		$sql = "UPDATE $tableName SET REFRESH_ID=? WHERE ID=?";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param('si', $refreshID, $id);
		$stmt->execute();
	}

	/**
	 * The method updates the last-modified date REFRESH_ID
	 * @param int $id
	 * @param string $date
	 */
	public function updateDateRefreshID($id, $date)
	{
		$tableName = self::getInstallTableName();
		$sql = "UPDATE $tableName SET DATE_UPDATE_REFRESH_ID=? WHERE ID=?";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param('si', $date, $id);
		$stmt->execute();
	}

	/**
	 * The method returns information about all modules
	 * @return array
	 */
	public function getAllModules()
	{
		$arModules = array();

		$this->checkDatabase();
		$tableName = self::getInstallTableName();
		$sql = "SELECT * FROM $tableName";
		$stmt = $this->connection->prepare($sql);
		$stmt->execute();
		$result = $stmt->get_result();

		while ($module = $result->fetch_assoc())
		{
			$arModules[] = $module;
		}

		return $arModules;
	}

	public function createBackupTable()
	{
		$tableName = self::getInstallTableName();
		$config = static::getSettings();

		exec("mysqldump --user=" . $config["DB_LOGIN"] .
			" '--password=" . $config["DB_PASSWORD"] .
			"' --host=" .  $config["DB_HOST"] . " " . $config["DB_NAME"] .
			" $tableName > " .  $_SERVER['DOCUMENT_ROOT'] . "/backups/$tableName"."_" . date('Y-m-d') . ".sql");
	}

	public function createBackupDB()
	{
		$config = static::getSettings();

		exec('mysqldump --user=' . $config['DB_LOGIN'] .
			" '--password=" . $config['DB_PASSWORD'] .
			"' --host=" .  $config['DB_HOST'] . ' ' . $config['DB_NAME'] . ' > ' .  $_SERVER['DOCUMENT_ROOT'] . '/db_backup/' . $config["DB_NAME"] . '.sql');
	}
}