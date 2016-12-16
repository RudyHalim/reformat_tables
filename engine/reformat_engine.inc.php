<?php
include("engine/MyTableClass.php");

ini_set('max_execution_time', 0); //300 seconds = 5 minutes
error_reporting("E_ALL");

mysql_connect($config['server'], $config['user'], $config['pass']) or die("Cannot connect mysql");
mysql_select_db($config['db']) or die("Cannot select db");

// set default param
$confirm = isset($_GET['confirm']) && !empty($_GET['confirm']) ? $_GET['confirm'] : false;

if(!$config['html_report']) {
	$confirm = true;
	include("engine/MyTableController.php");
} else {
	?>

	<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "DTD/xhtml1-transitional.dtd">
	<html xmlns="http://www.w3.org/1999/xhtml">
		<head>
			<style type="text/css">
				body {background-color: #fff; color: #222; font-family: sans-serif;}
				pre {margin: 0; font-family: monospace;}
				a:link {color: #009; text-decoration: none; text-decoration: underline;}
				a:hover {text-decoration: underline;}
				table {border-collapse: collapse; border: 0; width: 934px; box-shadow: 1px 2px 3px #ccc;}
				.center {text-align: center;}
				.center table {margin: 1em auto; text-align: left;}
				.center th {text-align: center !important;}
				td, th {border: 1px solid #666; font-size: 75%; vertical-align: baseline; padding: 4px 5px;}
				h1 {font-size: 150%;}
				h2 {font-size: 125%;}
				.p {text-align: left;}
				.e {background-color: #ccf; width: 300px; font-weight: bold;}
				.h {background-color: #99c; font-weight: bold;}
				.v {background-color: #ddd; max-width: 300px; overflow-x: auto; word-wrap: break-word;}
				.v i {color: #999;}
				img {float: right; border: 0;}
				hr {width: 934px; background-color: #ccc; border: 0; height: 1px;}
				input[type=submit] { padding: 15px 32px; background-color: #99c; color: #000; font-weight: bold; cursor: pointer; font-size: 15px; }
				input[type=submit]:hover {text-decoration: underline;}
			</style>
		</head>
		<body>
			<div class="center">
				<table><tr class="h"><td><h1 class="center">Tidy Up Backup Tables</h1></td></tr></table>
				
				<?php include("engine/MyTableController.php"); ?>

				<pre>&nbsp;</pre>
				<table><tr class="v"><td class="center">
					<p>PT. Lingua Asiatic &copy; <?=date("Y")?></p>
					<p>Comments &amp; Suggestion please email to <a href="mailto:rudy@linguaasiatic.com">rudy@linguaasiatic.com</a></p>
				</td></tr></table>
			</div>
		</body>
	</html>
	<?php
} 
?>