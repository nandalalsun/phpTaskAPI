<?php

class DB {
	// Static Class DB Connection Variables (for write and read)
	private static $writeDBConnection;
	private static $readDBConnection;

	// Static Class Method to connect to DB to perform Writes actions
	// handle the PDOException in the controller class to output a json api error
	public static function connectWriteDB() {
		if(self::$writeDBConnection === null) {
				self::$writeDBConnection = new PDO('mysql:host=gurukulsolution.com;dbname=gurukul1_thulobillingnew;charset=utf8', 'gurukul1_thulobilling', '4yf~k!]t3A.G');
				self::$writeDBConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				self::$writeDBConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		}

		return self::$writeDBConnection;
	}

	// Static Class Method to connect to DB to perform read only actions (read replicas)
	// handle the PDOException in the controller class to output a json api error
	public static function connectReadDB() {
		if(self::$readDBConnection === null) {
				self::$readDBConnection = new PDO('mysql:host=gurukulsolution.com;dbname=gurukul1_thulobillingnew;charset=utf8', 'gurukul1_thulobilling', '4yf~k!]t3A.G');
				self::$readDBConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				self::$readDBConnection->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
		}

		return self::$readDBConnection;
	}

}

try {
	$writeDB = DB::connectWriteDB();
	$readDB = DB::connectReadDB();
  }
  catch(PDOException $ex) {
	// log connection error for troubleshooting and return a json error response
	error_log("Connection Error: ".$ex, 0);
	$response = new Response();
	$response->setHttpStatusCode(500);
	$response->setSuccess(false);
	$response->addMessage("Database connection error");
	$response->send();
	exit;
  }