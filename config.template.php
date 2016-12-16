<?php
// /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// LOCALHOST with dummy data
// /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$config = array(
	'server' => 'localhost'
	, 'user' => 'root'
	, 'pass' => ''
	, 'db' => 'test'
	, 'table_auto_keyword' => 'reformat'		// output become: log_ack_reformat_month_1 for default value: reformat_month
	, 'file_auto_keyword' => 'autofile_tidy'		// output become: "service_tidy_1.php" for default value: service_tidy
);

$table_data = array(
	'0' => array(
		'table_wildcard' => 'table_log%'
		, 'datetime_column' => 'created'
		, 'full_column_name' => array('created', 'title', 'tipe', 'qty')
	),
	'1' => array(
		'table_wildcard' => 'table_ack%'
		, 'datetime_column' => 'created'
		, 'full_column_name' => array('created', 'title', 'tipe', 'qty')
	)
);
