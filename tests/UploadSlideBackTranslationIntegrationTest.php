<?php
use PHPUnit\Framework\TestCase;
use Symfony\Component\Process\Process;
use GuzzleHttp\Client;

class UploadSlideBackTranslationIntegrationTest extends TestCase
{
    protected $db;

    /** @var Process */
    private static Process $process;

    private static string $host = "localhost:8899";

    private string $storyTemplate = 'test_story';

    private string $uri = '/API/uploadSlideBacktranslation.php';

    public static function setUpBeforeClass(): void
    {
        self::$process  = new Process(["php", "-S", self::$host, "-t",  ROOT_PATH . "."]);
        self::$process->start();
        usleep(100000);
    }

    public static function tearDownAfterClass(): void
    {
        self::$process->stop();
    }

    public function setUp(): void
    {
        $this->db = new PDO(DB_DNS);
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // TODO
        // seed projects data (already done in the setup)

        // create template directory
        // with or without language
        // Add project/story.json to the template dir
        // add json file to project dir


        //
        // one scenario where story already exists so no need to create new one; `StoryId` param
    }

    public function tearDown(): void
    {
        $this->db = null;
    }


    /**
     * @return void
     * @throws \GuzzleHttp\Exception\GuzzleException
     *
     * Scenario 1: Story does not exist
     * Scenario 2: Story exists in the database
     * Scenario 3: StoryId - provide storyId in the request
     */

    public function test200()
    {

        $client = new Client(['http_errors' => false]);

        // request paylaod
        $payload = [
            'Key' => 'value',
            'PhoneId' => 'ghijkl',
            'Data' => 'Test data for story file',
            'TemplateTitle' => $this->storyTemplate,
//            'StoryId' => 1,
            'IsWholeStory' => "true"
        ];

        $response = $client->request("POST", self::$host . $this->uri, ['form_params' =>$payload]);
        $jsonRes = $response->getBody()->getContents();

        $this->assertEquals(200, $response->getStatusCode());
        $this->assertEquals('{"StoryId":"1"}', $jsonRes);


        $arrRes = json_decode($jsonRes, true);

        $createdStoryId = $arrRes['StoryId'];

        # verify story is created correctly in the database
        $q = $this->db->query('SELECT * FROM Stories where id = ?');
        $q->execute([$createdStoryId]);
        $stories = $q->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(1, $stories);

        $this->assertEquals([
            "id" =>$createdStoryId,
            "title" => "test_story",
            "language"=> "",
            "projectId" => 2, // TODO fetch project using PhoneId
            "note" => ""
        ], $stories[0]);


        # verify slides are all created correctly in the database
        $q = $this->db->prepare('SELECT * FROM Slide WHERE storyId = ?');
        $q->execute([$createdStoryId]);
        $rows = $q->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(3, $rows);


        $this->assertEquals(0, $rows[0]['slideNumber']);
        $this->assertEquals(1, $rows[1]['slideNumber']);
        # In test `stroy.json`, third slide type is `COPYRIGHT` which is skipped as per implementation
        $this->assertEquals(3, $rows[2]['slideNumber']);

        $storyFile = sprintf("%sFiles/Projects/%s/%s/wholeStory.m4a", ROOT_PATH, $payload['PhoneId'], $createdStoryId);

        $this->assertFileExists($storyFile);
        $this->assertEquals(file_get_contents($storyFile), base64_decode($payload['Data']));
    }

}
