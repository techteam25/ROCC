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
define('ROOT_PATH', dirname(__DIR__) . '/../');

#  This is for dev 10.10.10.248 server
#$databaseUser = 'dharding';
#$databasePassword = '4maria';
#$filesRoot = '/var/www/html/rocc/Files';
#$websocketPort = '8082';
#$externalWebsocketPort = '8082';
#$externalWebsocketHost = '10.10.10.248';

$dns = "mysql:host={$serverName};dbname={$databaseName}";
define('DB_DNS', $dns);
