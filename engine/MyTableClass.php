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

	function __construct($config) 
	{
		$this->config 						= $config;
		$this->allow_delete_backup_tables 	= false;
	}

	function prepare($table_data)
	{
		$this->table_data = $table_data;

		$this->ori_table_name = str_replace("%", "", $this->table_data['table_wildcard']);
		$this->new_table_name = $this->ori_table_name."_".$this->config['table_auto_keyword']."_";
	}

	function run()
	{
		$table_list = $this->getTableNames();
		
		// create the new possible table names per months
		$this->createTableperMonths($table_list);

		// smart auto merge the table data
		$this->mergeData($table_list);
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

		// if($this->config['html_report']) {
		// 	echo $q."<br /><br />";
		// }

		$this->mindate = $r['mindate'];
		$this->maxdate = $r['maxdate'];
	}

	function createTableperMonths($table_list)
	{

		if(is_array($table_list) && sizeof($table_list) > 0) {

			// set the month's range
			$this->setMonthsGroup($table_list);

			foreach ($this->getTableNamesToCreate() as $key => $value) {
			    $q = "CREATE TABLE IF NOT EXISTS ".$value." LIKE ".$this->ori_table_name;
			    $sql = mysql_query($q);

			    if($this->config['html_report']) {
			    	
			    } else {
			    	echo $q." - ".($sql ? "OK" : "Notice: ".mysql_error())."\n";
			    }
			}

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

		if($this->config['html_report']) {
			echo '<h4>Process result:</h4>';
			echo '<table>';
			echo '<tr class="h"><td>Query</td><td>Status</td></tr>';
		}

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

					if($this->config['html_report']) {
						$trclass = "e" ? "v" : "e";
						echo '<tr class="'.$trclass.'"><td>'.$q."</td><td>".($sql ? "OK" : "Error ".mysql_errno().": ".mysql_error()).'</td></tr>';
					} else {
						echo $q." - ".($sql ? "OK" : "Error ".mysql_errno().": ".mysql_error())."\n";
					}
				}
				$month = strtotime("+1 month", $month);
			}
		}

		if($this->config['html_report']) {
			echo '</table>';
		}

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
				if($this->config['html_report']) {
					echo '<table>';
					echo '<tr class="h"><td>Query</td><td>Status</td></tr>';
				}

				foreach ($backup_tables as $key => $value) {
					$q = "DROP TABLE ".$value;
					$sql = mysql_query($q);

					if($this->config['html_report']) {
						$trclass = "e" ? "v" : "e";
						echo '<tr class="'.$trclass.'"><td>'.$q."</td><td>".($sql ? "OK" : "Error ".mysql_errno().": ".mysql_error()).'</td></tr>';
					} else {
						echo $q." - ".($sql ? "OK" : "Error ".mysql_errno().": ".mysql_error())."\n";
					}
				}

				if($this->config['html_report']) {
					echo '</table>';
				}
			}

		} else {
			echo "Backup tables for <b>".$this->ori_table_name."</b> are not allowed to delete.\n";
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
		$string = '<form method="GET">';
		$string .= '<h4>Please confirm following information to proceed:</h4>';
		$string .= $this->generateTableLists($this->generateConfirmationFormLists($table_data));
		$string .= '<h2><input type="submit" name="confirm" value="Confirm &amp; Proceed" /></h2>';
		$string .= '</form>';

		if($this->config['html_report']) {
			echo $string;
		}
	}

	function generateTableLists($array) 
	{
		$return = '';
		if(is_array($array)) {
			foreach ($array as $group => $arrayvalue) {
				$return .= '<h2>'.$group.'</h2>';

				$return .= '<table>';
				foreach ($arrayvalue as $key => $value) {
					$return .= '<tr><td class="e">'.$key.'</td><td class="v">'.$value.'</td></tr>';
				}
				$return .= '</table>';
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

			$return['Scenario '.($key+1)] = array(
				'Master Table for Scenario '.($key+1) 		=> $this->ori_table_name
				, 'Table Wild Card to SEARCH' 				=> $this->table_data['table_wildcard']
				, 'Datetime Column' 						=> $this->table_data['datetime_column']
				, 'Full Columns Name' 						=> implode(", ", $this->table_data['full_column_name'])
				, 'Merge from Table'						=> implode("<br />", $this->getTableNames())
				, 'Range Date Time'							=> $this->mindate." to ".$this->maxdate
				, 'Table to DROP on success'				=> implode("<br />", $this->getOldBackupTableNames())
				, 'New Table to CREATE'						=> implode("<br />", $this->getTableNamesToCreate())
			);


		}

		return $return;
	}
}