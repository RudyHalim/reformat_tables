<?php
// /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
// LOCALHOST with dummy data
// /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

$config = array(
	'server' => 'localhost'
	, 'user' => 'root'
	, 'pass' => ''
	, 'db' => 'test'
	, 'table_auto_keyword' => 'reformat'			// output become: log_ack_reformat_month_1 for default value: reformat_month
	, 'temp_folder_name' => 'temp_mysql_DATETIME' 	// it will replace "DATETIME" with datetime. default: temp_mysql_DATETIME
	, 'table_data' => array(
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
	)
);
