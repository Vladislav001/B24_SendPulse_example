<?
require_once "DatabaseCommon.php";

class DatabaseUser extends DatabaseCommon
{
	protected function getInstallTableName()
	{
		$settigs = self::getSettings();
		$result = $settigs['DB_TABLE_NAME_USER'];
		return $result;
	}

	protected function checkDatabase()
	{
		$tableName = self::getInstallTableName();
		$sql = "CREATE TABLE IF NOT EXISTS $tableName (
			ID INT(11) NOT NULL AUTO_INCREMENT,
			DATE_INSERT TIMESTAMP NOT NULL DEFAULT current_timestamp,
    		EMAIL VARCHAR(2000),
    		PHONE VARCHAR(2000),
			USER_NAME VARCHAR(2000),
			PRIMARY KEY (ID),
		);";
		$result = $this->connection->query($sql);

		if (!$result)
		{
			throw new \Exception('Database query error: ' . $this->connection->error);
		}
	}

	/**
	 * Массовое добавление записей
	 *
	 * @param $data
	 *
	 * @return bool
	 * @throws Exception
	 */
	public function addMultiple($data)
	{
		$this->checkDatabase();
		$tableName = self::getInstallTableName();

		$this->connection->begin_transaction();

		$insertValues = array();
		foreach ($data as $d)
		{
			$questionMarks[] = '(' . $this->placeholders('?', count($d)) . ')';
			$insertValues = array_merge($insertValues, array_values($d));
			$datafields = array_keys($d);
		}

		$sql = "INSERT INTO $tableName (" . implode(",", $datafields) . ") VALUES " . implode(',', $questionMarks);
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param($this->placeholders('s', count($insertValues), ''), ...$insertValues);

		$stmt->execute();

		return $this->connection->commit();
	}

	public function test()
	{
		$this->checkDatabase();
		return 1;
	}
}