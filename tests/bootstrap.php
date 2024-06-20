<?php

require_once 'vendor/autoload.php';
require_once(__DIR__ . '/../API/utils/ConnectionSettings.php');
require_once(__DIR__ . '/../API/utils/Model.php');

try {
    $conn = new PDO(DB_DNS, $databaseUser, $databasePassword);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "Connected to MySQL successfully\n";

    // Execute the SQL queries to create tables
    $sqlFile = __DIR__ . '/../docs/new-schema.sql';
    $sqlQueries = file_get_contents($sqlFile);
    $conn->exec($sqlQueries);
    echo "Data tables created successfully\n";


    // Execute the SQL queries to seed data
    $sqlFile = __DIR__ . '/../docs/seed.sql';
    $sqlQueries = file_get_contents($sqlFile);
    $conn->exec($sqlQueries);
    echo "Data seed queries executed successfully\n";
} catch (PDOException $e) {
    die("MySQL Connection failed: " . $e->getMessage() . "\n");
} catch (Exception $e) {
    die("Error executing SQL file: " . $e->getMessage() . "\n");
}

// Close the connection
$conn = null;
