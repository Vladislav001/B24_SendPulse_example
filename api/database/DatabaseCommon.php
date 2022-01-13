<?php

abstract class DatabaseCommon
{
	protected static $instance;

	/**
	 * @var \mysqli|null
	 */
	protected $connection = null;

	protected function __construct()
	{
		$config = static::getSettings();
		$this->connection = mysqli_connect($config['DB_HOST'], $config['DB_LOGIN'], $config['DB_PASSWORD'], $config['DB_NAME']);
		if ($this->connection->connect_errno)
		{
			throw new \Exception('Database connect error:' . $this->connection->connect_error);
		}
	}

	protected static function getSettings()
	{
		$settings = parse_ini_file(LANG_ROOT_PATH . '/settings.ini');

		return array(
			"DB_HOST" => $settings['DB_HOST'],
			"DB_LOGIN" => $settings['DB_LOGIN'],
			"DB_PASSWORD" => $settings['DB_PASSWORD'],
			"DB_NAME" => $settings['DB_NAME'],
			"DB_TABLE_NAME_SITE" => $settings['DB_TABLE_NAME_SITE'],
			"DB_TABLE_NAME_USER" => $settings['DB_TABLE_NAME_USER'],
		);
	}

	/**
	 * @return static
	 */
	public static function getInstance()
	{
		$class = get_called_class();

		if (!static::$instance[$class])
		{
			static::$instance[$class] = new $class();
		}

		return static::$instance[$class];
	}

	abstract protected function checkDatabase();

	abstract protected function getInstallTableName();

	/*  placeholders for prepared statements like (?,?,?)  */
	protected static function placeholders($text, $count = 0, $separator = ",")
	{
		$result = array();
		if ($count > 0)
		{
			for ($x = 0; $x < $count; $x++)
			{
				$result[] = $text;
			}
		}

		return implode($separator, $result);
	}

	// todo пока не юзается нигде
	public function getData($filter = false)
	{
		$data = array();

		if (!$filter)
		{
			$filter = '';
		}

		$this->checkDatabase();
		$tableName = $this->getInstallTableName();
		$sql = "SELECT * FROM $tableName $filter";

		$stmt = $this->connection->prepare($sql);
		$stmt->execute();
		$result = $stmt->get_result();

		while ($element = $result->fetch_assoc())
		{
			$data[] = $element;
		}

		return $data;
	}
}