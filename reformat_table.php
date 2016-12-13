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

$mytable = new MyTable($config, $table_data);

if(is_array($table_data) && sizeof($table_data) > 0) {


	// STEP 1: Confirmation from Config Files
	$mytable->showConfirmationForm();

	// STEP 2: Ask if necessary to dump tables to sql file
	$mytable->dumpTablesToFiles();

	// STEP 3: Ask if necessary to run tidy up database tables
	$mytable->runTidyTables();

	// STEP 4: Ask if necessary to delete dump sql folder
	$mytable->deleteDumpFolder();


} else {
	echo "No table found in config file.".PHP_EOL;
}