<?php
$serverName = 'localhost';
$databaseName = 'StoryProducer';
#  This is for rocc.ttapps.org server
$databaseUser = 'StoryP';
$databasePassword = 'StoryProducer';
$filesRoot = '/var/www/html/Files';
$websocketPort = '8010';
$externalWebsocketPort = '443';
$externalWebsocketHost = 'wss.ttapps.org';

//$dns = "mysql:host={$GLOBALS['serverName']};dbname={$GLOBALS['databaseName']}";

define('ROOT_PATH', dirname(__DIR__) . '/../');


$dbPath = ROOT_PATH . $databaseName . '.db';

// TODO duplicate variable, restructure into test config
$filesRoot = ROOT_PATH . 'Files';

$dns = "sqlite:$dbPath";

define('DB_FILE_PATH', $dbPath);

define('DB_DNS', $dns);

#  This is for dev 10.10.10.248 server
#$databaseUser = 'dharding';
#$databasePassword = '4maria';
#$filesRoot = '/var/www/html/rocc/Files';
#$websocketPort = '8082';
#$externalWebsocketPort = '8082';
#$externalWebsocketHost = '10.10.10.248';