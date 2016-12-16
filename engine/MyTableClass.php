<?php
class MyTable
{
	private $config;
	private $table_data;

	private $ori_table_name;
	private $new_table_name;
	private $mindate;
	private $maxdate;

	private $text_output;

	function __construct($config) 
	{
		$this->config 						= $config;

		$this->ori_table_name 				= str_replace("%", "", $this->table_data['table_wildcard']);
		$this->new_table_name 				= $this->ori_table_name."_".$this->config['table_auto_keyword']."_";
	}

	function prepare($table_data)
	{
		$this->table_data = $table_data;

		$this->ori_table_name = str_replace("%", "", $this->table_data['table_wildcard']);
		$this->new_table_name = $this->ori_table_name."_".$this->config['table_auto_keyword']."_";

		$this->checkMasterTableExistance();
	}

	function showConfigInformation()
	{
		echo 'Loading information..'.PHP_EOL.PHP_EOL;
		
		$parameters = MyCommon::generateTableLists($this->getConfigConfirmationValues($this->table_data));

		echo 'Please verify below config parameters:'.PHP_EOL;
		echo $parameters.PHP_EOL;
		
		MyCommon::confirm("Are you sure this information is correct?  Type 'y' to continue: ");
	}

	function askQuestionsConfirmation()
	{
		MyCommon::confirm("1. Do you want to create mysqldump for these tables? Type 'y' to continue: ");
		MyCommon::confirm("2. Do you want to run tidy up database tables monthly? Type 'y' to continue: ");
		MyCommon::confirm("3. Do you want to delete temp folder? Type 'y' to continue: ");

		echo "lanjuttt.....";
	}

	function dumpTablesToFiles()
	{
		$this->confirm("Do you want to create mysqldump for these tables? Type 'y' to continue: ");
	}

	function getConfigConfirmationValues($table_data)
	{
		$return = array();

		$return['Configuration'] = array(
			'Database Name' 		=> $this->config['db']
			, 'Table Auto Keyword' 	=> $this->config['table_auto_keyword']
		);

		foreach ($table_data as $key => $value) {
			$this->prepare($value);
			print_r($this->getTableNames());

			// $this->setMonthsGroup($this->getTableNames());

			$return['SCENARIO '.($key+1)] = array(
				'Master Table for Scenario '.($key+1) 		=> $this->ori_table_name
				// , 'Table Wild Card to SEARCH' 				=> $this->table_data['table_wildcard']
				// , 'Datetime Column' 						=> $this->table_data['datetime_column']
				// , 'Full Columns Name' 						=> implode(", ", $this->table_data['full_column_name'])
				// , 'Merge from Table'						=> implode(", ", $this->getTableNames())
				// , 'Range Date Time'							=> $this->mindate && $this->mindate ? $this->mindate." to ".$this->maxdate : "(No more table to update)"
				// , 'Table to DROP on success'				=> $this->getOldBackupTableNames() ? implode(", ", $this->getOldBackupTableNames()) : "(No more table to update)"
				// , 'New Table to CREATE'						=> $this->getTableNamesToCreate() ? implode(", ", $this->getTableNamesToCreate()) : "(No more table to update)"
			);
		}
		return $return;
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
		$sql = mysql_query($q) or die($q." ".mysql_error());
		$r=mysql_fetch_assoc($sql);

		$this->mindate = $r['mindate'];
		$this->maxdate = $r['maxdate'];
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

}
