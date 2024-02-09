<?php

namespace integration;


use GuzzleHttp\Exception\GuzzleException;

class UploadWordLinkRecordingIntegrationTest extends BaseIntegrationTest
{
    const URI = '/API/UploadWordLinkRecording.php';
    const PHONE_ID_1 = "rstuvw";
    const PHONE_ID_2 = "lmnopq";
    const PHONE_ID_3 = "ghijkl";
    const AUDIO_FILE_CONTENT = "Test data for audio recording file";
    const AUDIO_FILE_CONTENT_UPDATED = "Updated test data for audio recording file";
    const TERM_1 = "prayed";
    const TERM_2 = "pray";
    const TEXT_BACK_TRANSLATION_CONTENT = "Test data for text back translation";
    const TEXT_BACK_TRANSLATION_CONTENT_UPDATED = "Updated test data for text back translation";
    const RECORDING_FILE_EXTENSION = "m4a";
    const RECORDING_FILE_NAME_1 = "WordLinks 1." . self::RECORDING_FILE_EXTENSION;
    const RECORDING_FILE_NAME_2 = "WordLinks 2.." . self::RECORDING_FILE_EXTENSION;

    /**
     * @throws \Exception
     * @throws GuzzleException
     */
    public function testCreateNewRecording(): void
    {
        // request payload
        $payload = [
            'PhoneId' => self::PHONE_ID_1,
            'term' =>  self::TERM_1,
            'wordLinkRecording' => [
              'audioRecordingFilename' => self::RECORDING_FILE_NAME_1,
              'textBackTranslation' => self::TEXT_BACK_TRANSLATION_CONTENT
            ],
            'Data' => base64_encode(self::AUDIO_FILE_CONTENT),
        ];

        $recordingId = $this->sendRequestAndReturnRecordingId($payload);

        # verify audio recording is created correctly in the database
        $recording = $this->getRecording($recordingId);

        $this->assertEquals($payload['term'], $recording['term']);
        $this->assertNotEmpty($recording['fileName']);
        $this->assertEquals(self::RECORDING_FILE_EXTENSION, pathinfo($recording['fileName'], PATHINFO_EXTENSION));
        $this->assertEquals(self::TEXT_BACK_TRANSLATION_CONTENT, $recording['textBackTranslation']);
        $this->assertEquals($this->getProjectId($payload['PhoneId']), $recording['projectId']);

        // verify auto recording is uploaded successfully
        $uploadedRecordingFile = sprintf("%s/%s/WordLinks/%s", self::$uploadedProjectDir, $payload['PhoneId'], $recording['fileName']);
        $this->assertFileExists($uploadedRecordingFile);
        $this->assertEquals(self::AUDIO_FILE_CONTENT, file_get_contents($uploadedRecordingFile));
    }

    /**
     * @throws GuzzleException
     */
    public function testUpdateExistingRecording()
    {
        $createRecordingPayload = [
            'PhoneId' => self::PHONE_ID_2,
            'term' => self::TERM_1,
            'wordLinkRecording' => [
                'audioRecordingFilename' =>self::RECORDING_FILE_NAME_1,
                'textBackTranslation' => self::TEXT_BACK_TRANSLATION_CONTENT
            ],
            'Data' => base64_encode(self::AUDIO_FILE_CONTENT),
        ];

        $recordingId = $this->sendRequestAndReturnRecordingId($createRecordingPayload);
        $createdRecording = $this->getRecording($recordingId);

        // request payload
        $updateRecordingPayload = [
            'PhoneId' => self::PHONE_ID_2,
            'term' => self::TERM_1,
            'wordLinkRecording' => [
                'audioRecordingFilename' => self::RECORDING_FILE_NAME_2,
                'textBackTranslation' => self::TEXT_BACK_TRANSLATION_CONTENT_UPDATED
            ],
            'Data' => base64_encode(self::AUDIO_FILE_CONTENT_UPDATED),
            'RecordingId' => $recordingId
        ];


        // update should not create a new recording
        $this->assertEquals($recordingId, $this->sendRequestAndReturnRecordingId($updateRecordingPayload));

        // assert old recording file is deleted
        $createdRecordingFile = sprintf("%s/%s/WordLinks/%s", self::$uploadedProjectDir, $createRecordingPayload['PhoneId'], $createdRecording['fileName']);
        $this->assertFileDoesNotExist($createdRecordingFile);


        # assert updated recording data
        $updateRecording = $this->getRecording($recordingId);
        $this->assertEquals(self::TEXT_BACK_TRANSLATION_CONTENT_UPDATED, $updateRecording['textBackTranslation']);
        
        // assert recording file name is updated
        $this->assertNotEquals($updateRecording['fileName'], $createdRecording['fileName']);

        // verify new recording file is uploaded successfully
        $updatedRecordingFile = sprintf("%s/%s/WordLinks/%s", self::$uploadedProjectDir, $updateRecordingPayload['PhoneId'], $updateRecording['fileName']);
        $this->assertEquals(self::RECORDING_FILE_EXTENSION, pathinfo($updateRecording['fileName'], PATHINFO_EXTENSION));
        $this->assertFileExists($updatedRecordingFile);
        $this->assertEquals(self::AUDIO_FILE_CONTENT_UPDATED, file_get_contents($updatedRecordingFile));
    }

    /**
     * @test
     * @throws GuzzleException
     * @throws \Exception
     */
    public function testShouldNotUpdateGivenRecordingIfTermIsChanged() {
        $createRecordingPayload = [
            'PhoneId' => self::PHONE_ID_3,
            'term' => self::TERM_1,
            'wordLinkRecording' => [
                'audioRecordingFilename' =>self::RECORDING_FILE_NAME_1,
                'textBackTranslation' => self::TEXT_BACK_TRANSLATION_CONTENT
            ],
            'Data' => base64_encode(self::AUDIO_FILE_CONTENT),
        ];

        $recordingId = $this->sendRequestAndReturnRecordingId($createRecordingPayload);
        $createdRecording = $this->getRecording($recordingId);

        // request payload
        $updateRecordingPayload = [
            'PhoneId' => self::PHONE_ID_3,
            'term' => self::TERM_2,
            'wordLinkRecording' => [
                'audioRecordingFilename' => self::RECORDING_FILE_NAME_2,
                'textBackTranslation' => self::TEXT_BACK_TRANSLATION_CONTENT_UPDATED
            ],
            'Data' => base64_encode(self::AUDIO_FILE_CONTENT_UPDATED),
            'RecordingId' => $recordingId
        ];

        // update should not create a new recording
        $this->sendRequestAndExpect400Response($updateRecordingPayload);


        # verify exiting recording data isn't changed
        $this->assertEquals($createRecordingPayload['term'], $createdRecording['term']);
        $this->assertNotEmpty($createdRecording['fileName']);
        $this->assertEquals(self::RECORDING_FILE_EXTENSION, pathinfo($createdRecording['fileName'], PATHINFO_EXTENSION));
        $this->assertEquals(self::TEXT_BACK_TRANSLATION_CONTENT, $createdRecording['textBackTranslation']);
        $this->assertEquals($this->getProjectId($createRecordingPayload['PhoneId']), $createdRecording['projectId']);

        // verify auto recording is uploaded successfully
        $uploadedRecordingFile = sprintf("%s/%s/WordLinks/%s", self::$uploadedProjectDir, $createRecordingPayload['PhoneId'], $createdRecording['fileName']);
        $this->assertFileExists($uploadedRecordingFile);
        $this->assertEquals(self::AUDIO_FILE_CONTENT, file_get_contents($uploadedRecordingFile));
    }

    /**
     * @return void
     * @throws GuzzleException
     */
    public function testShouldNotUpdateGivenRecordingIfPhoneIdIsChanged() {
        $createRecordingPayload = [
            'PhoneId' => self::PHONE_ID_1,
            'term' => self::TERM_2,
            'wordLinkRecording' => [
                'audioRecordingFilename' =>self::RECORDING_FILE_NAME_1,
                'textBackTranslation' => self::TEXT_BACK_TRANSLATION_CONTENT
            ],
            'Data' => base64_encode(self::AUDIO_FILE_CONTENT),
        ];

        $recordingId = $this->sendRequestAndReturnRecordingId($createRecordingPayload);
        $createdRecording = $this->getRecording($recordingId);

        // request payload
        $updateRecordingPayload = [
            'PhoneId' => self::PHONE_ID_2,
            'term' => self::TERM_2,
            'wordLinkRecording' => [
                'audioRecordingFilename' => self::RECORDING_FILE_NAME_2,
                'textBackTranslation' => self::TEXT_BACK_TRANSLATION_CONTENT_UPDATED
            ],
            'Data' => base64_encode(self::AUDIO_FILE_CONTENT_UPDATED),
            'RecordingId' => $recordingId
        ];

        // update should not create a new recording
        $this->sendRequestAndExpect400Response($updateRecordingPayload);


        # verify exiting recording data isn't changed
        $this->assertEquals($createRecordingPayload['term'], $createdRecording['term']);
        $this->assertNotEmpty($createdRecording['fileName']);
        $this->assertEquals(self::RECORDING_FILE_EXTENSION, pathinfo($createdRecording['fileName'], PATHINFO_EXTENSION));
        $this->assertEquals(self::TEXT_BACK_TRANSLATION_CONTENT, $createdRecording['textBackTranslation']);
        $this->assertEquals($this->getProjectId($createRecordingPayload['PhoneId']), $createdRecording['projectId']);

        // verify auto recording is uploaded successfully
        $uploadedRecordingFile = sprintf("%s/%s/WordLinks/%s", self::$uploadedProjectDir, $createRecordingPayload['PhoneId'], $createdRecording['fileName']);
        $this->assertFileExists($uploadedRecordingFile);
        $this->assertEquals(self::AUDIO_FILE_CONTENT, file_get_contents($uploadedRecordingFile));
    }

    /**
     * @param array $payload
     * @return int
     * @throws GuzzleException
     */
    private function sendRequestAndReturnRecordingId(array $payload): int
    {
        $response = $this->httpClient->request("POST", self::HOST . self::URI, ['form_params' => $payload]);
        $responseJson = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode());
        $responseArr = json_decode($responseJson, true);
        $this->assertArrayHasKey('RecordingId', $responseArr);
        return $responseArr['RecordingId'];
    }


    /**
     * @throws GuzzleException
     */
    private function sendRequestAndExpect400Response(array $payload): void
    {
        $response = $this->httpClient->request("POST", self::HOST . self::URI, ['form_params' => $payload]);
        $this->assertEquals(400, $response->getStatusCode());
    }

    private function getRecording(int $recordingId): array
    {
        $q = self::$db->prepare('SELECT * FROM WordLinkRecordings where id = ?');
        $q->execute([$recordingId]);
        $recordings = $q->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(1, $recordings);
        return $recordings[0];
    }
}