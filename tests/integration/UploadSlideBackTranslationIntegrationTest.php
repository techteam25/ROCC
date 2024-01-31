<?php

namespace integration;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use PDO;

class UploadSlideBackTranslationIntegrationTest extends TestCase
{
    const string HOST = "localhost:8899";
    const string STORY_TEMPLATE = 'story_1';
    const string URI = '/API/uploadSlideBacktranslation.php';
    const string PHONE_ID_1 = "rstuvw";
    const string PHONE_ID_2 = "lmnopq";
    const string PHONE_ID_3 = "ghijkl";
    const string STORY_FILE_CONTENTS = "Test data for story file";
    const string STORY_FILE_CONTENTS_UPDATED = "Story data has been updated";
    const string SLIDE_FILE_CONTENTS = "'Story data been updated and saved into a slide file i.e. 2.m4a'";
    private static $db;
    /** @var Process */
    private static Process $process;
    private static string $filesRoot;
    private static string $uploadedProjectDir;
    private static Filesystem $fileSystem;

    private Client $httpClient;

    public static function setUpBeforeClass(): void
    {
        // start php in-built server
        self::$process = new Process(["php", "-S", self::HOST, "-t", ROOT_PATH . "."]);
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

        self::$db = new PDO(DB_DNS,
            $GLOBALS['databaseUser'], $GLOBALS['databasePassword']);
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
     * @return int
     * @throws \GuzzleHttp\Exception\GuzzleException
     * @throws \Exception
     */
    public function testCreateNewStory(): void
    {
        // request payload
        $payload = [
            'Key' => '',
            'PhoneId' => self::PHONE_ID_1,
            'Data' => base64_encode(self::STORY_FILE_CONTENTS),
            'TemplateTitle' => self::STORY_TEMPLATE,
            'IsWholeStory' => "true"
        ];

        $storyId = $this->sendRequestAndReturnStoryId($payload);

        # verify story is created correctly in the database
        $stories = $this->getStories($storyId);
        $this->assertCount(1, $stories);
        $this->assertEquals(self::STORY_TEMPLATE, $stories[0]['title']);
        $this->assertEquals($this->getProjectId($payload['PhoneId']), $stories[0]['projectId']);

        # verify slides are all created correctly in the database
        $slides = $this->getSlides($storyId);
        $this->assertCount(3, $slides);
        $this->assertEquals(0, $slides[0]['slideNumber']);
        $this->assertEquals(1, $slides[1]['slideNumber']);
        # In test `story.json`, third slide type is `COPYRIGHT` which is skipped as per implementation
        $this->assertEquals(3, $slides[2]['slideNumber']);

        $storyFile = sprintf("%s/%s/%s/wholeStory.m4a", self::$uploadedProjectDir, $payload['PhoneId'], $storyId);
        $this->assertFileExists($storyFile);
        $this->assertEquals(file_get_contents($storyFile), self::STORY_FILE_CONTENTS);
    }

    /**
     * @param array $payload
     * @return int
     * @throws GuzzleException
     */
    private function sendRequestAndReturnStoryId(array $payload): int
    {
        $response = $this->httpClient->request("POST", self::HOST . self::URI, ['form_params' => $payload]);
        $responseJson = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode());
        $responseArr = json_decode($responseJson, true);
        $this->assertArrayHasKey('StoryId', $responseArr);
        return $responseArr['StoryId'];
    }

    /**
     * @param string $storyId
     *
     * @return array
     */
    private function getStories(string $storyId): array
    {
        $q = self::$db->prepare('SELECT * FROM Stories where id = ?');
        $q->execute([$storyId]);
        return $q->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getProjectId(string $androidId): int
    {
        $q = self::$db->prepare('SELECT id FROM Projects where androidId = ?');
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
     *
     * @return void
     * @throws GuzzleException
     * @throws \Exception
     */
    public function testUpdateExistingStory()
    {
        // payload for creating a new story
        $createStoryPayload = [
            'Key' => '',
            'PhoneId' => self::PHONE_ID_2,
            'Data' => base64_encode(self::STORY_FILE_CONTENTS),
            'TemplateTitle' => self::STORY_TEMPLATE,
            'IsWholeStory' => "true",
            'Language' => 'es',
        ];

        $storyId = $this->sendRequestAndReturnStoryId($createStoryPayload);

        // Update story created above identified by $storyId

        // payload for updating a new story
        $updateStoryPayload = [
            'Key' => '',
            'PhoneId' => self::PHONE_ID_2,
            'Data' => base64_encode('Story data has been updated'),
            'TemplateTitle' => 'Template title updated',
            'IsWholeStory' => "true",
            'Language' => 'pl',
            'StoryId' => $storyId
        ];

        // update story should not create a new story
        $this->assertEquals($storyId, $this->sendRequestAndReturnStoryId($updateStoryPayload));

        # verify story is updated correctly in the database
        $stories = $this->getStories($storyId);
        $this->assertCount(1, $stories);
        $story = $stories[0];

        // assert that story data isn't impacted with story update request
        $this->assertEquals($createStoryPayload['Language'], $story['language']);
        $this->assertEquals(self::STORY_TEMPLATE, $story['title']);
        $this->assertEquals($this->getProjectId($updateStoryPayload['PhoneId']), $story['projectId']);

        // assert that slides data isn't impacted with story update request
        $slides = $this->getSlides($storyId);
        $this->assertCount(3, $slides);

        // verify story data has been updated successfully
        $storyFile = sprintf("%s/%s/%s/wholeStory.m4a", self::$uploadedProjectDir, $updateStoryPayload['PhoneId'], $storyId);
        $this->assertFileExists($storyFile);
        $this->assertEquals(file_get_contents($storyFile), self::STORY_FILE_CONTENTS_UPDATED);
    }

    /**
     * @return void
     * @throws GuzzleException
     * @throws \Exception
     */
    public function testUpdateExistingStoryWithSpecificSlideData()
    {
        // payload for creating a new story
        $createStoryPayload = [
            'Key' => '',
            'PhoneId' => self::PHONE_ID_3,
            'Data' => base64_encode(self::STORY_FILE_CONTENTS),
            'TemplateTitle' => self::STORY_TEMPLATE,
            'IsWholeStory' => "true",
        ];

        $storyId = $this->sendRequestAndReturnStoryId($createStoryPayload);

        $storyFile = sprintf("%s/%s/%s/wholeStory.m4a", self::$uploadedProjectDir, $createStoryPayload['PhoneId'], $storyId);
        $this->assertFileExists($storyFile);
        $this->assertEquals(file_get_contents($storyFile), base64_decode($createStoryPayload['Data']));


        // Update story created above identified by $storyId

        // payload for updating a new story
        $updateStoryPayload = [
            'Key' => '',
            'PhoneId' => self::PHONE_ID_3,
            'Data' => base64_encode(self::SLIDE_FILE_CONTENTS),
            'TemplateTitle' => 'Template title updated',
            'SlideNumber' => 1,
            'StoryId' => $storyId
        ];

        // should not create a new story
        $this->assertEquals($storyId, $this->sendRequestAndReturnStoryId($updateStoryPayload));

        # verify story is updated correctly in the database
        $stories = $this->getStories($storyId);
        $this->assertCount(1, $stories);
        $story = $stories[0];
        $this->assertNotNull($story['FirstThreshold']);
        $this->assertNotNull($story['SecondThreshold']);

        // assert that story data isn't impacted with story update request
        $this->assertEquals(self::STORY_TEMPLATE, $story['title']);
        $this->assertEquals($this->getProjectId($updateStoryPayload['PhoneId']), $story['projectId']);

        // assert that slides data isn't impacted with story update request
        $slides = $this->getSlides($storyId);
        $this->assertCount(3, $slides);

        $slideFile = sprintf("%s/%s/%s/%s.m4a", self::$uploadedProjectDir, $updateStoryPayload['PhoneId'], $storyId, $updateStoryPayload['SlideNumber']);
        $this->assertFileExists($storyFile);
        $this->assertEquals(file_get_contents($slideFile), self::SLIDE_FILE_CONTENTS);
    }
}
