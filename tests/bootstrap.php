<?php
global $filesRoot;

use Symfony\Component\Process\Process;
use Symfony\Component\HttpClient\HttpClient;

require_once 'vendor/autoload.php';
require_once(__DIR__ . '/../API/utils/ConnectionSettings.php');


//var_dump(dirname(__DIR__));

//echo 'ROOT_PATH' .ROOT_PATH . PHP_EOL;

//echo 'FileRoot : ' . $filesRoot . PHP_EOL;




// TODO setup file root for test env

// TODO Setup Project Directory for test env

try {
    $conn = new PDO(DB_DNS);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to MySQL successfully\n";

    // Execute the SQL queries to create tables
    $sqlFile = __DIR__ . '/sql/new-schema.sql';
    $sqlQueries = file_get_contents($sqlFile);
    $conn->exec($sqlQueries);
    echo "Data tables created successfully\n";



    // Execute the SQL queries to seed data
    $sqlFile = __DIR__ . '/../docs/seed.sql';
    $sqlQueries = file_get_contents($sqlFile);
    $conn->exec($sqlQueries);
    echo "Data seed queries executed successfully\n";


    // Fetch list of tables
    $q = $conn->query("SELECT name FROM sqlite_master WHERE type='table';");
    $tables = $q->fetchAll(PDO::FETCH_COLUMN);

    // Output the list of tables
    echo "Tables in  database:\n";
    foreach ($tables as $table) {
        echo $table . "\n";
    }


} catch (PDOException $e) {
    die("MySQL Connection failed: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error executing SQL file: " . $e->getMessage() . "\n");
}

// Close the connection
$conn = null;


//
//
////// Start the web server in the background
//$webServerCommand = 'php -S localhost:9000 -t /Users/sp.singh@contino.io/workspace/up/ROCC/.';
//$process = new Process(explode(' ', $webServerCommand));
//$process->start();
//
//// Wait until the web server is up by making a request to a known endpoint
//$httpClient = HttpClient::create();
//$endpoint = 'http://localhost:9000'; // Adjust the endpoint based on your application
//
//sleep(1000);
//while (true) {
//    try {
//        $response = $httpClient->request('GET', $endpoint);
//        if ($response->getStatusCode() === 200) {
//            break; // Server is up, break the loop
//        }
//    } catch (\Exception $e) {
//        // Ignore exceptions, server is not yet up
//    }
//
//    sleep(1); // Wait for 1 second before the next check
//}

// Your additional setup code goes here
