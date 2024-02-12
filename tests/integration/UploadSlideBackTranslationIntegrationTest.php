<?php

namespace integration;
use GuzzleHttp\Exception\GuzzleException;
use PDO;

class UploadSlideBackTranslationIntegrationTest extends BaseIntegrationTest
{
    const STORY_TEMPLATE = 'story_1';
    const URI = '/API/uploadSlideBacktranslation.php';
    const PHONE_ID_1 = "rstuvw";
    const PHONE_ID_2 = "lmnopq";
    const PHONE_ID_3 = "ghijkl";
    const STORY_FILE_CONTENTS = "Test data for story file";
    const STORY_FILE_CONTENTS_UPDATED = "Story data has been updated";
    const SLIDE_FILE_CONTENTS = "'Story data been updated and saved into a slide file i.e. 2.m4a'";

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
