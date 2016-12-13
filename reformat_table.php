<?php
include("config.php");
include("engine/MyTableClass.php");

ini_set('max_execution_time', 0); //300 seconds = 5 minutes
error_reporting("E_ALL");

mysql_connect($config['server'], $config['user'], $config['pass']) or die("Cannot connect mysql");
mysql_select_db($config['db']) or die("Cannot select db");


// ===============
// Kick Off
// ===============

echo PHP_EOL.'++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++'.PHP_EOL;
echo PHP_EOL.' CONVERT DATABASE TABLES BACKUP INTO MONTHLY DATABASE TABLES BACKUP'.PHP_EOL;
echo PHP_EOL.'++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++++'.PHP_EOL;

$mytable = new MyTable($config);

if(is_array($table_data) && sizeof($table_data) > 0) {

	$mytable->showConfirmationFormCli($table_data);

	echo '----------------------------------------------------------'.PHP_EOL;
	echo ' RESULT:'.PHP_EOL;
	echo '----------------------------------------------------------'.PHP_EOL;

	foreach ($table_data as $key => $data) {

		// set global wild card first
		$mytable->prepare($data);

		// prepare the needed resources and merge the data
		$mytable->run();

		// delete the rest backup tables:
		// - table without the "table_auto_keyword" (see config)
		// - table that are not mentioned as "table_wildcard" (see config)
		// $mytable->deleteOldBackupTables();
	}

} else {
	echo "No table found in config file.";
}