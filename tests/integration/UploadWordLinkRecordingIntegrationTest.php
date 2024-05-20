<?php

namespace integration;


use GuzzleHttp\Exception\GuzzleException;

class UploadWordLinkRecordingIntegrationTest extends BaseIntegrationTest
{
    const URI = '/API/UploadWordLinkRecording.php';
    const PHONE_ID_1 = "rstuvw";
    const PHONE_ID_2 = "lmnopq";
    const PHONE_ID_3 = "ghijkl";
    const TERM_1 = "prayed";
    const TERM_2 = "pray";
    const TEXT_BACK_TRANSLATION_CONTENT_1 = "Test data for text back translation 1";
    const TEXT_BACK_TRANSLATION_CONTENT_2 = "Test data for text back translation 2";
    const TEXT_BACK_TRANSLATION_CONTENT_3 = "Test data for text back translation 3";
    const TEXT_BACK_TRANSLATION_CONTENT_UPDATED = "<b>Updated test data for text back translation</b>";

    /**
     * @throws \Exception
     * @throws GuzzleException
     */
    public function testCreateNewRecording(): void
    {
        // request payload
        $payload = [
            'PhoneId' => self::PHONE_ID_1,
            'term' => self::TERM_1,
            'textBackTranslation' => [
                self::TEXT_BACK_TRANSLATION_CONTENT_1,
                self::TEXT_BACK_TRANSLATION_CONTENT_2,
                "",
                self::TEXT_BACK_TRANSLATION_CONTENT_3,
                null
            ]
        ];

        $recordingId = $this->sendRequestAndReturnRecordingId($payload);

        # verify audio recording is created correctly in the database
        $recording = $this->getRecording($recordingId);
        $this->assertEquals($payload['term'], $recording['term']);
        $this->assertEquals(
            [
                self::TEXT_BACK_TRANSLATION_CONTENT_1,
                self::TEXT_BACK_TRANSLATION_CONTENT_2,
                self::TEXT_BACK_TRANSLATION_CONTENT_3
            ], json_decode($recording['textBackTranslation']));
        $this->assertEquals(self::$model->GetProjectId($payload['PhoneId']), $recording['projectId']);
    }

    /**
     * @throws GuzzleException
     */
    public function testUpdateExistingRecording()
    {
        $createRecordingPayload = [
            'PhoneId' => self::PHONE_ID_2,
            'term' => self::TERM_1,
            'textBackTranslation' => [
                self::TEXT_BACK_TRANSLATION_CONTENT_1,
                self::TEXT_BACK_TRANSLATION_CONTENT_2,
            ]
        ];

        $recordingId = $this->sendRequestAndReturnRecordingId($createRecordingPayload);

        // request payload
        $updateRecordingPayload = [
            'PhoneId' => self::PHONE_ID_2,
            'term' => self::TERM_1,
            'textBackTranslation' => [self::TEXT_BACK_TRANSLATION_CONTENT_UPDATED],
        ];


        // update should not create a new recording
        $this->assertEquals($recordingId, $this->sendRequestAndReturnRecordingId($updateRecordingPayload));

        # assert updated recording data
        $updateRecording = $this->getRecording($recordingId);
        $this->assertEquals([self::TEXT_BACK_TRANSLATION_CONTENT_UPDATED], json_decode($updateRecording['textBackTranslation']));
    }

    /**
     * @return void
     * @throws GuzzleException
     */
    public function testShouldDeleteGivenRecordingWhenEmptyTextBackTranslationIsGiven()
    {
        $createRecordingPayload = [
            'PhoneId' => self::PHONE_ID_2,
            'term' => self::TERM_1,
            'textBackTranslation' => [
                self::TEXT_BACK_TRANSLATION_CONTENT_1,
                self::TEXT_BACK_TRANSLATION_CONTENT_2,
            ]
        ];

        $recordingId = $this->sendRequestAndReturnRecordingId($createRecordingPayload);

        $curl = curl_init();
        $data = [
            'PhoneId' => self::PHONE_ID_2,
            'term' => self::TERM_1,
        ];

        curl_setopt_array($curl, [
            CURLOPT_URL => self::HOST . self::URI,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query($data) . '&textBackTranslation[]=',
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/x-www-form-urlencoded'
            ],
        ]);

        curl_exec($curl);
        $httpStatusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        $this->assertEquals(200, $httpStatusCode);

        $q = self::$db->prepare('SELECT * FROM WordLinkRecordings where id = ?');
        $q->execute([$recordingId]);
        $recordings = $q->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(0, $recordings);

    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function testShouldNotUpdateGivenRecordingIfTermIsChanged()
    {
        $createRecordingPayload = [
            'PhoneId' => self::PHONE_ID_3,
            'term' => self::TERM_1,
            'textBackTranslation' => [self::TEXT_BACK_TRANSLATION_CONTENT_1]
        ];

        $createdRecordingId = $this->sendRequestAndReturnRecordingId($createRecordingPayload);

        // request payload
        $updateRecordingPayload = [
            'PhoneId' => self::PHONE_ID_3,
            'term' => self::TERM_2,
            'textBackTranslation' => [self::TEXT_BACK_TRANSLATION_CONTENT_UPDATED],
        ];

        // should create a new recording
        $updatedRecordingId = $this->sendRequestAndReturnRecordingId($updateRecordingPayload);
        $this->assertNotEquals($createdRecordingId, $updatedRecordingId);

        # verify exiting recording data isn't changed
        $createdRecording = $this->getRecording($createdRecordingId);
        $this->assertEquals($createRecordingPayload['term'], $createdRecording['term']);
        $this->assertEquals([self::TEXT_BACK_TRANSLATION_CONTENT_1], json_decode($createdRecording['textBackTranslation']));
        $this->assertEquals(self::$model->GetProjectId($createRecordingPayload['PhoneId']), $createdRecording['projectId']);
    }

    /**
     * @return void
     * @throws GuzzleException
     */
    public function testShouldNotUpdateGivenRecordingIfPhoneIdIsChanged()
    {
        $createRecordingPayload = [
            'PhoneId' => self::PHONE_ID_1,
            'term' => self::TERM_2,
            'textBackTranslation' => [self::TEXT_BACK_TRANSLATION_CONTENT_1],
        ];

        $createdRecordingId = $this->sendRequestAndReturnRecordingId($createRecordingPayload);
        // request payload
        $updateRecordingPayload = [
            'PhoneId' => self::PHONE_ID_2,
            'term' => self::TERM_2,
            'textBackTranslation' => [self::TEXT_BACK_TRANSLATION_CONTENT_UPDATED],
        ];

        // should create a new recording
        $updatedRecordingId = $this->sendRequestAndReturnRecordingId($updateRecordingPayload);
        $this->assertNotEquals($createdRecordingId, $updatedRecordingId);

        # verify exiting recording data isn't changed
        $createdRecording = $this->getRecording($createdRecordingId);
        $this->assertEquals($createRecordingPayload['term'], $createdRecording['term']);
        $this->assertEquals([self::TEXT_BACK_TRANSLATION_CONTENT_1], json_decode($createdRecording['textBackTranslation']));
        $this->assertEquals(self::$model->GetProjectId($createRecordingPayload['PhoneId']), $createdRecording['projectId']);
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

    private function getRecording(int $recordingId): array
    {
        $q = self::$db->prepare('SELECT * FROM WordLinkRecordings where id = ?');
        $q->execute([$recordingId]);
        $recordings = $q->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(1, $recordings);
        return $recordings[0];
    }
}