<?php
require_once('API/utils/Model.php');
session_start();

if (isset($_SESSION['email'])) {
	echo "Current user: " . $_SESSION['email'] . "<br/><br/>";
}

$model = new Model();

$tables = $model->GetTableNames();

foreach ($tables as $table) {
	if ($table === "sysdiagrams") continue;
	$rows = $model->GetAllRowsFromTable($table);
	echo $table ." (" . count($rows) . ")<br/>";
	echo "<table style='width:97%'>";
	foreach ($rows as $row) {
		echo "<tr>";
		foreach ($row as $key => $value) {
			if (!is_numeric($key)) {
				echo "<td>" . $value . "</td>";
			}
		}
		unset($key);
		unset($value);
		echo "<tr/>";
	}
	unset($row);
	echo "</table><br/>";
}
unset($table);

phpinfo();