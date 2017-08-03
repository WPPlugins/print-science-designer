<?php
Class ExternalDB
{
	private $connect;
	private $dbsuccess;

	public function __construct($type)
	{
		global $EXTERNAL_MYSQL_DATABASES;
		$this->dbsuccess = false;
		if ($EXTERNAL_MYSQL_DATABASES && is_array($EXTERNAL_MYSQL_DATABASES)) {
			if ($EXTERNAL_MYSQL_DATABASES[$type] && is_array($EXTERNAL_MYSQL_DATABASES[$type])) {
				$db_host = $EXTERNAL_MYSQL_DATABASES[$type]['host'];
				$db_name = $EXTERNAL_MYSQL_DATABASES[$type]['dbname'];
				$db_user = $EXTERNAL_MYSQL_DATABASES[$type]['dbusername'];
				$db_pass = $EXTERNAL_MYSQL_DATABASES[$type]['dbpassword'];

				if (strlen($db_host) && strlen($db_name) && strlen($db_user) && strlen($db_pass)) {
					$this->connect = @mysqli_connect($db_host, $db_user, $db_pass);
					if ($this->connect) {
						$this->dbsuccess = $this->connect->select_db($db_name);
					}
				}
			}
		}
	}

	public function query($query)
	{
		return $this->connect->query($query);
	}

	public function get_row($query)
	{
		$results = false;
		$db_results = $this->query($query);
		if ($db_results) {
			$results = $db_results->fetch_object();
			$db_results->close();
		}
		return $results;
	}

	public function get_results($query)
	{
		$results = false;
		$db_results = $this->query($query);
		if ($db_results) {
			$results = array();
			while ($row = $db_results->fetch_object()) {
				$results[] = $row;
			}
			$db_results->close();
		}
		return $results;
	}

	public function is_sucess()
	{
		return $this->dbsuccess;
	}
}
?>