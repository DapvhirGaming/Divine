<?php
	class db {
		private $server = "Live";
		public $dbName = '';
		private $dbh;
		private $stmt;
		private $error = array();
		public $totalQueries = 0;
		public $queries = array();
		public function __construct() {
			if($this->server == "Live") {
				$host = "localhost";
				$user = "root";
				$pass = "61fb00e03d0f8645";
				$dbname = "ragnarok_main";
			}
			$this->dbName = $dbname;
			// Set DSN
			$dsn = 'mysql:host=' . $host . ';dbname=' . $dbname.';';
			// Set options
			$options = array(
				PDO::ATTR_PERSISTENT    => true,
				PDO::ATTR_ERRMODE       => PDO::ERRMODE_EXCEPTION,
				PDO::ATTR_EMULATE_PREPARES	=> false,
				PDO::ATTR_DEFAULT_FETCH_MODE	=> PDO::FETCH_ASSOC
			);
			//$dbh->setAttribute(PDO::ATTR_EMULATE_PREPARES,false); 
			// Create a new PDO instanace
			try {
				$this->dbh = new PDO($dsn, $user, $pass, $options);
			}
			// Catch any errors
			catch(PDOException $e){
				$this->error = $e->getMessage();
			}
		}
		public function query($query){
			$this->queries[$this->totalQueries+1]['sql'] = $query;
			$this->stmt = $this->dbh->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY));
			if(!$this->dbh) {
				$this->error[] = $this->dbh->errorInfo();
			}
		}
		public function bindArray($binds) {
			if(is_array($binds)) {
				foreach($binds as $b) {
					$this->bind($b['param'], $b['value']);
				}
			}
		}
		public function bind($param, $value, $type = null){
			if (is_null($type)) {
				switch (true) {
					case is_int($value):
						$type = PDO::PARAM_INT;
					break;
					case is_bool($value):
						$type = PDO::PARAM_BOOL;
					break;
					case is_null($value):
						$type = PDO::PARAM_NULL;
					break;
					default:
						$type = PDO::PARAM_STR;
				}
			}
			$this->queries[$this->totalQueries+1]['values'][] = $param.' = '.$value;
			$this->stmt->bindValue($param, $value, $type);
		}
		public function execute($parameters = ''){
			$this->totalQueries++;
			return $this->stmt->execute($parameters);
		}
		public function resultset(){
			$this->execute();
			return $this->stmt->fetchAll();
		}
		public function single(){
			$this->execute();
			return $this->stmt->fetch();
		}
		public function fetchColumn() {
			$this->execute();
			return $this->stmt->fetchColumn();
		}
		public function fetchAll() {
			$this->execute();
			return $this->stmt->fetchAll();
		}
		public function lastInsertId(){
			return $this->dbh->lastInsertId();
		}
		public function beginTransaction(){
			return $this->dbh->beginTransaction();
		}
		public function endTransaction(){
			return $this->dbh->commit();
		}
		public function cancelTransaction(){
			return $this->dbh->rollBack();
		}
		public function debugDumpParams(){
			return $this->stmt->debugDumpParams();
		}
		public function errorlog() {
			return $this->error;
		}
	}
	$db = new db();
?>