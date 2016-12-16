<?php
class MyCommon
{
	function confirm($message)
	{
		echo $message;
		$handle = fopen ("php://stdin","r");
		$line = fgets($handle);
		if(trim($line) != 'y'){
		    echo "ABORTING!\n";
		    exit;
		}
		fclose($handle);
		echo "\n";
	}

	function generateTableLists($array) 
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

}
