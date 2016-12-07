<?php
include("config.php");

if(sizeof($table_data) > 0) {
	$shell = $command = '';

	echo "***********************************************\n";

	// create files
	foreach ($table_data as $key => $value) {
		$filename = $config['file_auto_keyword']."_".$key.".php";
		$filelog = $config['file_auto_keyword']."_".$key.'.log';

		$content = '<?php '. PHP_EOL;

		$content .= '/* **************************************************' . PHP_EOL;
		$content .= ' * Tidy Up Database Tables' . PHP_EOL;
		$content .= ' * ' . PHP_EOL;
		$content .= ' * This file is auto created from create_service.php ' . PHP_EOL;
		$content .= ' * ' . PHP_EOL;
		$content .= ' ****************************************************/' . PHP_EOL;

		$content .= '$config = '.var_export($config, true).';'. PHP_EOL;
		$content .= '$table_data[0] = '.var_export($table_data[$key], true).';'. PHP_EOL;
		$content .= 'include("engine/reformat_engine.inc.php");';

		$create_file = file_put_contents($filename, $content);
		echo ($create_file ? "Successfully" : "Fail")." created file ".$filename."\n";

		$command = 'php '.$filename.' > '.$filelog;
		$shell .= $command. ' & '	;
		echo $command.PHP_EOL.PHP_EOL;
	}
	
	$shell = rtrim($shell, " & ");
	$ll = shell_exec($shell);
	echo $ll;

	echo "***********************************************\n";
}
