<?php

namespace integration;

use GuzzleHttp\Client;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use PDO;

abstract class BaseIntegrationTest extends TestCase
{
    const HOST = "localhost:8899";
    protected static PDO|null $db;
    /** @var Process */
    private static Process $process;
    private static string $filesRoot;
    protected static string $uploadedProjectDir;
    private static Filesystem $fileSystem;
    protected Client $httpClient;

    /** @var \Model Data model class */
    protected static \Model $model;

    public static function setUpBeforeClass(): void
    {
        // start php in-built server
        self::$process = new Process(["php", "-S", self::HOST, "-t", dirname(__DIR__) . '/../.']);
        self::$process->start();
        usleep(500000);

        // create required directory for test data
        self::$fileSystem = new Filesystem();
        self::$filesRoot = $GLOBALS['filesRoot'];
        $templateDir = self::$filesRoot . "/Templates";
        self::$fileSystem->mkdir($templateDir);
        // copy test story template files to files root
        self::$fileSystem->mirror(dirname(__DIR__) . "/data/templates", $templateDir);
        self::$uploadedProjectDir = self::$filesRoot . "/Projects";
        self::$db = new PDO(DB_DNS, $GLOBALS['databaseUser'], $GLOBALS['databasePassword']);
        self::$model = new \Model();
    }

    public static function tearDownAfterClass(): void
    {
        self::$fileSystem->remove(self::$filesRoot);
        self::$process->stop();
        self::$db->query('DELETE FROM Slide');
        self::$db->query('DELETE FROM Stories');
        self::$db->query('DELETE FROM WordlinkTranslations');
        self::$db = null;
    }

    public function setUp(): void
    {
        $this->httpClient = new Client(['http_errors' => false]);
    }
}