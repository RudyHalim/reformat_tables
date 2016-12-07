<?php
// ===============
// Kick Off
// ===============
$mytable = new MyTable($config);

if(is_array($table_data) && sizeof($table_data) > 0) {

	if($confirm) {

		foreach ($table_data as $key => $data) {

			// set global wild card first
			$mytable->prepare($data);

			// prepare the needed resources and merge the data
			$mytable->run();

			// delete the rest backup tables:
			// - table without the "table_auto_keyword" (see config)
			// - table that are not mentioned as "table_wildcard" (see config)
			$mytable->deleteOldBackupTables();
		}

	} else {
		$mytable->showConfirmationForm($table_data);
	}
} else {
	echo "No table found in config file.";
}
