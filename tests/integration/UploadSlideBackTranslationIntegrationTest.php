<?php

namespace integration;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use PHPUnit\TextUI\XmlConfiguration\File;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use PDO;

class UploadSlideBackTranslationIntegrationTest extends TestCase
{
    private static $db;

    /** @var Process */
    private static Process $process;

    private static string $host = "localhost:8899";

    private string $storyTemplate = 'test_story';
    private string $storyTemplate2 = 'test_story2';

    private string $uri = '/API/uploadSlideBacktranslation.php';

    private static string $filesRoot;
    private static string $uploadedProjectDir;
    private static Filesystem $fileSystem;

    private Client $httpClient;

    public static function setUpBeforeClass(): void
    {
        // start php in-built server
        self::$process = new Process(["php", "-S", self::$host, "-t", ROOT_PATH . "."]);
        self::$process->start();
        usleep(100000);

        // create required directory for test data
        self::$fileSystem = new Filesystem();
        self::$filesRoot = $GLOBALS['filesRoot'];
        $templateDir = self::$filesRoot . "/Templates";
        self::$fileSystem->mkdir($templateDir);
        // copy test story template files to files root
        self::$fileSystem->mirror(dirname(__DIR__) . "/data/templates", $templateDir);
        self::$uploadedProjectDir = self::$filesRoot . "/Projects";

        self::$db = new PDO(DB_DNS);
        self::$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    public static function tearDownAfterClass(): void
    {
        self::$fileSystem->remove(self::$filesRoot);
        self::$process->stop();
        self::$db->query('DELETE FROM Slide');
        self::$db->query('DELETE FROM Stories');
        self::$db = null;
    }


    public function setUp(): void
    {
        $this->httpClient = new Client(['http_errors' => false]);
    }

    /**
     *
     * @return int
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function testCreateNewStory(): void
    {
        // request payload
        $payload = [
            'Key' => '',
            'PhoneId' => 'rstuvw',
            'Data' => base64_encode('Test data for story file'),
            'TemplateTitle' => $this->storyTemplate,
            'IsWholeStory' => "true"
        ];

        $response = $this->httpClient->request("POST", self::$host . $this->uri, ['form_params' => $payload]);
        $responseJson = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode());
        $responseArr = json_decode($responseJson, true);
        $this->assertArrayHasKey('StoryId', $responseArr);
        $storyId = $responseArr['StoryId'];


        # verify story is created correctly in the database
        $stories = $this->getStories($storyId);
        $this->assertCount(1, $stories);

        $this->assertEquals([
            "id" => $storyId,
            "title" => "test_story",
            "language" => "",
            "projectId" => $this->getProjectId($payload['PhoneId']),
            "note" => "",
            'FirstThreshold' => null,
            'SecondThreshold' => null,
        ], $stories[0]);


        # verify slides are all created correctly in the database
        $slides = $this->getSlides($storyId);
        $this->assertCount(3, $slides);
        $this->assertEquals(0, $slides[0]['slideNumber']);
        $this->assertEquals(1, $slides[1]['slideNumber']);
        # In test `story.json`, third slide type is `COPYRIGHT` which is skipped as per implementation
        $this->assertEquals(3, $slides[2]['slideNumber']);

        $storyFile = sprintf("%s/%s/%s/wholeStory.m4a", self::$uploadedProjectDir, $payload['PhoneId'], $storyId);
        $this->assertFileExists($storyFile);
        $this->assertEquals(file_get_contents($storyFile), base64_decode($payload['Data']));
    }


    /**
     * @depends testCreateNewStory
     *
     * @return void
     * @throws GuzzleException
     * @throws \Exception
     */
    public function testUpdateExistingStory()
    {
        // payload for creating a new story
        $payload = [
            'Key' => '',
            'PhoneId' => 'lmnopq',
            'Data' => base64_encode('Test data for story file'),
            'TemplateTitle' => $this->storyTemplate,
            'IsWholeStory' => "true",
            'Language' => 'es',
        ];

        $response = $this->httpClient->request("POST", self::$host . $this->uri, ['form_params' => $payload]);
        $responseJson = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode());

        $responseArr = json_decode($responseJson, true);
        $storyId = $responseArr['StoryId'];

        # verify story is created correctly in the database
        $stories = $this->getStories($storyId);
        $this->assertCount(1, $stories);
        $story = $stories[0];

        $this->assertEquals($payload['Language'], $story['language']);
        $this->assertEquals($payload['TemplateTitle'], $story['title']);
        $this->assertEquals($this->getProjectId($payload['PhoneId']), $story['projectId']);


        # verify slides are all created correctly in the database
        $slides = $this->getSlides($storyId);
        $this->assertCount(3, $slides);


        $storyFile = sprintf("%s/%s/%s/wholeStory.m4a", self::$uploadedProjectDir, $payload['PhoneId'], $storyId);
        $this->assertFileExists($storyFile);
        $this->assertEquals(file_get_contents($storyFile), base64_decode($payload['Data']));


        // Update story created above identified by $storyId

        // payload for updating a new story
        $payload = [
            'Key' => '',
            'PhoneId' => 'lmnopq',
            'Data' => base64_encode('Story data has been updated'),
            'TemplateTitle' => 'Template title updated',
            'IsWholeStory' => "true",
            'Language' => 'pl',
            'StoryId' => $storyId
        ];

        $response = $this->httpClient->request("POST", self::$host . $this->uri, ['form_params' => $payload]);
        $responseJson = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode());

        $responseArr = json_decode($responseJson, true);

        // should not create a new story
        $this->assertEquals($storyId, $responseArr['StoryId']);

        # verify story is updated correctly in the database
        $stories = $this->getStories($storyId);
        $this->assertCount(1, $stories);
        $story = $stories[0];

        // assert that story data isn't impacted with story update request
        $this->assertEquals('es', $story['language']);
        $this->assertEquals($this->storyTemplate, $story['title']);
        $this->assertEquals($this->getProjectId($payload['PhoneId']), $story['projectId']);

        // assert that slides data isn't impacted with story update request
        $slides = $this->getSlides($storyId);
        $this->assertCount(3, $slides);

        // verify story data has been updated successfully
        $storyFile = sprintf("%s/%s/%s/wholeStory.m4a", self::$uploadedProjectDir, $payload['PhoneId'], $storyId);
        $this->assertFileExists($storyFile);
        $this->assertEquals(file_get_contents($storyFile), base64_decode($payload['Data']));

    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws \Exception
     */
    public function testUpdateExistingStoryWithSpecificSlideData()
    {
        // payload for creating a new story
        $payload = [
            'Key' => '',
            'PhoneId' => 'ghijkl',
            'Data' => base64_encode('Story data is saved into wholeStory.m4a'),
            'TemplateTitle' => $this->storyTemplate,
            'IsWholeStory' => "true",
        ];

        $response = $this->httpClient->request("POST", self::$host . $this->uri, ['form_params' => $payload]);
        $responseJson = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode());

        $responseArr = json_decode($responseJson, true);
        $storyId = $responseArr['StoryId'];

        $storyFile = sprintf("%s/%s/%s/wholeStory.m4a", self::$uploadedProjectDir, $payload['PhoneId'], $storyId);
        $this->assertFileExists($storyFile);
        $this->assertEquals(file_get_contents($storyFile), base64_decode($payload['Data']));


        // Update story created above identified by $storyId

        // payload for updating a new story
        $payload = [
            'Key' => '',
            'PhoneId' => 'ghijkl',
            'Data' => base64_encode('Story data been updated and saved into a slide file i.e. 2.m4a'),
            'TemplateTitle' => 'Template title updated',
            'SlideNumber' => 1,
            'StoryId' => $storyId
        ];

        $response = $this->httpClient->request("POST", self::$host . $this->uri, ['form_params' => $payload]);
        $responseJson = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode());

        $responseArr = json_decode($responseJson, true);

        // should not create a new story
        $this->assertEquals($storyId, $responseArr['StoryId']);


        # verify story is updated correctly in the database
        $stories = $this->getStories($storyId);
        $this->assertCount(1, $stories);
        $story = $stories[0];
        $this->assertNotNull($story['FirstThreshold']);
        $this->assertNotNull($story['SecondThreshold']);

        // assert that story data isn't impacted with story update request
        $this->assertEquals($this->storyTemplate, $story['title']);
        $this->assertEquals($this->getProjectId($payload['PhoneId']), $story['projectId']);

        // assert that slides data isn't impacted with story update request
        $slides = $this->getSlides($storyId);
        $this->assertCount(3, $slides);

        $storyFile = sprintf("%s/%s/%s/%s.m4a", self::$uploadedProjectDir, $payload['PhoneId'], $storyId, $payload['SlideNumber']);
        $this->assertFileExists($storyFile);
        $this->assertEquals(file_get_contents($storyFile), base64_decode($payload['Data']));
    }

    private function getProjectId(string $androidId): int|string
    {
        $q = self::$db->query('SELECT id FROM Projects where androidId = ?');
        $q->execute([$androidId]);
        $project = $q->fetch(PDO::FETCH_ASSOC);

        if ($project) {
            return $project['id'];
        }

        throw new \Exception('Project not found in the database');
    }

    /**
     * @param string $storyId
     * @return array
     */
    private function getSlides(string $storyId): array
    {
        $q = self::$db->prepare('SELECT * FROM Slide WHERE storyId = ?');
        $q->execute([$storyId]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * @param string $storyId
     *
     * @return array
     */
    private function getStories(string $storyId): array
    {
        $q = self::$db->query('SELECT * FROM Stories where id = ?');
        $q->execute([$storyId]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }
}
