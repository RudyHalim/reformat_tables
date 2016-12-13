<?php
class MyTable
{
	private $config;
	private $table_data;

	private $mindate;
	private $maxdate;

	private $ori_table_name;
	private $new_table_name;

	private $allow_delete_backup_tables;
	private $text_output;

	function __construct($config, $table_data) 
	{
		$this->config 						= $config;
		$this->allow_delete_backup_tables 	= false;
		$this->table_data = $table_data;
		$this->ori_table_name = str_replace("%", "", $this->table_data['table_wildcard']);
		$this->new_table_name = $this->ori_table_name."_".$this->config['table_auto_keyword']."_";

		$this->checkMasterTableExistance();
	}

	function runTidyTables()
	{
		$table_list = $this->getTableNames();

		echo str_pad("Table Name: ", 30).strtoupper($this->ori_table_name).PHP_EOL;
		
		// create the new possible table names per months
		$this->createTableperMonths($table_list);

		// smart auto merge the table data
		$this->mergeData($table_list);

		// creating logs
		$this->writeLogs();
	}

	function checkMasterTableExistance() {

		$q = "SELECT 1 FROM ".$this->ori_table_name." LIMIT 1";
		$sql = mysql_query($q);

		if(!$sql) {
			echo "\n**************************************************************\n";
    		echo "\nPlease create Master Table (".$this->ori_table_name.") first in order to continue.\n";
    		echo "\n**************************************************************\n".$q;
    		die();
    	}
	}

	function getTableNames()
	{
		$return = array();
		$q = "SELECT table_name FROM information_schema.tables WHERE table_schema LIKE '".$this->config['db']."' AND table_name like '".$this->table_data['table_wildcard']."' AND table_name NOT LIKE '%".$this->config['table_auto_keyword']."%';";
		$sql = mysql_query($q);
		while ($r=mysql_fetch_assoc($sql)) {
			$return[] = $r['table_name'];
		}
		return $return;
	}

	function setMonthsGroup($table_list)
	{
		/*
		 * SELECT MIN(MONTH(dari)), MAX(MONTH(sampai)) FROM (
		 * 	SELECT MIN(created) dari, MAX(created) sampai FROM table_log_a 
		 * 	UNION ALL
		 * 	SELECT MIN(created) dari, MAX(created) sampai FROM table_log_b 
		 * 	UNION ALL
		 * 	SELECT MIN(created) dari, MAX(created) sampai FROM table_log_c 
		 * ) AS X
		 */

		// Compose the query string
		$q = "SELECT MIN(dari) mindate, MAX(sampai) maxdate FROM (";
		foreach ($table_list as $key => $table_name) {
			if($key > 0) 
				$q .= " UNION ALL "; 
			$q .= "SELECT MIN(".$this->table_data['datetime_column'].") dari, MAX(".$this->table_data['datetime_column'].") sampai FROM ".$table_name." WHERE ".$this->table_data['datetime_column']." != '0000-00-00 00:00:00'";
		}
		$q .= ") as x";
		$sql = mysql_query($q) or die(mysql_error());
		$r=mysql_fetch_assoc($sql);

		$this->mindate = $r['mindate'];
		$this->maxdate = $r['maxdate'];
	}

	function createTableperMonths($table_list)
	{

		if(is_array($table_list) && sizeof($table_list) > 0) {

			echo str_pad("Creating monthly tables  ", 30);

			// set the month's range
			$this->setMonthsGroup($table_list);

			foreach ($this->getTableNamesToCreate() as $key => $value) {
			    $q = "CREATE TABLE IF NOT EXISTS ".$value." LIKE ".$this->ori_table_name;
			    $sql = mysql_query($q);

		    	$this->text_output .= PHP_EOL.$q." - ".($sql ? "OK" : "Notice: ".mysql_error())."\n";
			}

			echo "OK".PHP_EOL;
		}
		
	}

	function getTableNamesToCreate()
	{
		$return = array();

		// try to create table if not exists
		$start = $month = strtotime($this->mindate);
		$end = strtotime($this->maxdate);
		while($month < $end) {
		    $counter = date('Y_m', $month);
		    $return[] = $this->new_table_name.$counter;
		    $month = strtotime("+1 month", $month);
		}

		return $return;
	}

	function mergeData($table_list)
	{
		// need a local variable for reference first before overwrite the global for safety reason
		$is_success_all = true;
		$entered_loop 	= false;

		echo str_pad('Start Merging ', 30);

		foreach ($table_list as $table_name) {
			$entered_loop = true;

			$start = $month = strtotime($this->mindate);
			$end = strtotime($this->maxdate);
			while($month < $end) {
				$counter = date('Y_m', $month);

				// avoid comparing to the auto table
				if(!strpos($table_name.$counter, $this->config['table_auto_keyword']) !== false) {

					$column_to = implode(",", $this->table_data['full_column_name']);
					$column_from = implode(",", $this->getQueryComparingColumns($table_name));

					$q="INSERT IGNORE INTO ".$this->new_table_name.$counter." (".$column_to.") SELECT ".$column_from." FROM ".$table_name." WHERE YEAR(".$this->table_data['datetime_column'].") = ".date('Y', $month)." AND MONTH(".$this->table_data['datetime_column'].") = ".date('m', $month).";";
					$sql = mysql_query($q);

					if(!$sql) {
						$is_success_all = false;
					}

					$this->text_output .= $q." - ".($sql ? "OK" : "Error ".mysql_errno().": ".mysql_error()).PHP_EOL;
				}
				$month = strtotime("+1 month", $month);
			}
		}

		echo 'OK'.PHP_EOL;

		// put it to global var
		if($entered_loop && $is_success_all) {
			$this->allow_delete_backup_tables = $is_success_all;
		}
	}

	function getColumnsFromTable($table_name)
	{
		$return = array();
		$q = "SELECT column_name FROM information_schema.`COLUMNS` WHERE table_schema = '".$this->config['db']."' AND table_name = '".$table_name."';";
		$sql = mysql_query($q);
		while ($r=mysql_fetch_assoc($sql)) {
			$return[] = $r['column_name'];
		}
		return $return;
	}

	function getQueryComparingColumns($table_name)
	{
		$return = array();
		$full_column_name 	= $this->table_data['full_column_name'];
		$columns 			= $this->getColumnsFromTable($table_name);

		// adjusting column method, copy it first
		$return = $full_column_name;

		// check if the column is mentioned at the second table, else remove it
		foreach ($return as $key => $value) {
			if(!in_array($value, $columns)) {
				$return[$key] = "''"; 	// set the missing column name to empty to match the column count number
			}
		}
		return $return;
	}

	function deleteOldBackupTables() 
	{
		// CONDITION:
		// delete the rest backup tables:
		// - table without the "table_auto_keyword" (see config)
		// - table that are not mentioned as "table_wildcard" (see config)

		if($this->allow_delete_backup_tables) {
			$backup_tables = $this->getOldBackupTableNames();
			if(sizeof($backup_tables) > 0) {

				echo 'Start Delete Backup Tables ... ';

				foreach ($backup_tables as $key => $value) {
					$q = "DROP TABLE ".$value;
					$sql = mysql_query($q);

					$this->text_output .= $q." - ".($sql ? "OK" : "Error ".mysql_errno().": ".mysql_error()).PHP_EOL;
				}
				echo 'OK'.PHP_EOL;

			}
		} else {
			echo "Backup tables for ".$this->ori_table_name." are not allowed to delete.".PHP_EOL;
		}
	}

	function getOldBackupTableNames()
	{
		$return = array();
		$q = "SELECT table_name FROM information_schema.tables 
					WHERE table_schema LIKE '".$this->config['db']."' 
					AND table_name like '".$this->table_data['table_wildcard']."' 
					AND table_name NOT LIKE '%".$this->config['table_auto_keyword']."%'
					AND table_name != '".$this->ori_table_name."' 
					;";
		$sql = mysql_query($q);
		while ($r=mysql_fetch_assoc($sql)) {
			$return[] = $r['table_name'];
		}
		return $return;
	}

	function showConfirmationForm($table_data)
	{
		echo PHP_EOL.'Please verify below config parameters:'.PHP_EOL;
		echo $this->generateTableListsCli($this->generateConfirmationFormLists($table_data));
		
		echo PHP_EOL."Are you sure this information is correct?  Type 'y' to continue: ";
		$handle = fopen ("php://stdin","r");
		$line = fgets($handle);
		if(trim($line) != 'y'){
		    echo "ABORTING!\n";
		    exit;
		}
		fclose($handle);
		echo "\n"; 
	}

	function generateTableListsCli($array) 
	{
		$return = '';
		if(is_array($array)) {
			foreach ($array as $group => $arrayvalue) {
				$return .= PHP_EOL . $group . PHP_EOL;

				foreach ($arrayvalue as $key => $value) {
					$return .= str_pad($key, 30) . $value . PHP_EOL;
				}
			}
		}
		return $return;
	}

	function generateConfirmationFormLists($table_data)
	{
		$return = array();

		$return['Configuration'] = array(
			'Database Name' 		=> $this->config['db']
			, 'Table Auto Keyword' 	=> $this->config['table_auto_keyword']
		);

		foreach ($table_data as $key => $value) {
			$this->prepare($value);
			$this->setMonthsGroup($this->getTableNames());

			$return['SCENARIO '.($key+1)] = array(
				'Master Table for Scenario '.($key+1) 		=> $this->ori_table_name
				, 'Table Wild Card to SEARCH' 				=> $this->table_data['table_wildcard']
				, 'Datetime Column' 						=> $this->table_data['datetime_column']
				, 'Full Columns Name' 						=> implode(", ", $this->table_data['full_column_name'])
				, 'Merge from Table'						=> implode(", ", $this->getTableNames())
				, 'Range Date Time'							=> $this->mindate && $this->mindate ? $this->mindate." to ".$this->maxdate : "(No more table to update)"
				, 'Table to DROP on success'				=> $this->getOldBackupTableNames() ? implode(", ", $this->getOldBackupTableNames()) : "(No more table to update)"
				, 'New Table to CREATE'						=> $this->getTableNamesToCreate() ? implode(", ", $this->getTableNamesToCreate()) : "(No more table to update)"
			);
		}
		return $return;
	}

	function writeLogs()
	{
		echo str_pad("Write to log ", 30);
		if(strlen($this->text_output) > 0) {
			$filename = $this->config['table_auto_keyword'].'_'.$this->ori_table_name.'.log';

			$create_file = file_put_contents($filename, $this->text_output);
			echo ($create_file ? "OK" : "Fail")." (".$filename.")";
		} else {
			echo "No more query executed.";
		}
		echo PHP_EOL.PHP_EOL;
	}
}
