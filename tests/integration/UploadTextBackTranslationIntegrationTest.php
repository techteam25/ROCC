<?php

namespace integration;


use GuzzleHttp\Exception\GuzzleException;

class UploadTextBackTranslationIntegrationTest extends BaseIntegrationTest
{
    const URI = '/API/UploadTextBackTranslation.php';
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
    public function testCreateNewTranslation(): void
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

        $translationId = $this->sendRequestAndReturnTranslationId($payload);

        # verify audio translation is created correctly in the database
        $translation = $this->getTranslation($translationId);
        $this->assertEquals($payload['term'], $translation['term']);
        $this->assertEquals(
            [
                self::TEXT_BACK_TRANSLATION_CONTENT_1,
                self::TEXT_BACK_TRANSLATION_CONTENT_2,
                self::TEXT_BACK_TRANSLATION_CONTENT_3
            ], json_decode($translation['textBackTranslation']));
        $this->assertEquals(self::$model->GetProjectId($payload['PhoneId']), $translation['projectId']);
    }

    /**
     * @throws GuzzleException
     */
    public function testUpdateExistingTranslation()
    {
        $createTranslationPayload = [
            'PhoneId' => self::PHONE_ID_2,
            'term' => self::TERM_1,
            'textBackTranslation' => [
                self::TEXT_BACK_TRANSLATION_CONTENT_1,
                self::TEXT_BACK_TRANSLATION_CONTENT_2,
            ]
        ];

        $translationId = $this->sendRequestAndReturnTranslationId($createTranslationPayload);

        // request payload
        $updateTranslationPayload = [
            'PhoneId' => self::PHONE_ID_2,
            'term' => self::TERM_1,
            'textBackTranslation' => [self::TEXT_BACK_TRANSLATION_CONTENT_UPDATED],
        ];


        // update should not create a new translation
        $this->assertEquals($translationId, $this->sendRequestAndReturnTranslationId($updateTranslationPayload));

        # assert updated translation data
        $updateTranslation = $this->getTranslation($translationId);
        $this->assertEquals([self::TEXT_BACK_TRANSLATION_CONTENT_UPDATED], json_decode($updateTranslation['textBackTranslation']));
    }

    /**
     * @throws GuzzleException
     * @throws \Exception
     */
    public function testShouldNotUpdateGivenTranslationIfTermIsChanged()
    {
        $createTranslationPayload = [
            'PhoneId' => self::PHONE_ID_3,
            'term' => self::TERM_1,
            'textBackTranslation' => [self::TEXT_BACK_TRANSLATION_CONTENT_1]
        ];

        $createdTranslationId = $this->sendRequestAndReturnTranslationId($createTranslationPayload);

        // request payload
        $updateTranslationPayload = [
            'PhoneId' => self::PHONE_ID_3,
            'term' => self::TERM_2,
            'textBackTranslation' => [self::TEXT_BACK_TRANSLATION_CONTENT_UPDATED],
        ];

        // should create a new translation
        $updatedTranslationId = $this->sendRequestAndReturnTranslationId($updateTranslationPayload);
        $this->assertNotEquals($createdTranslationId, $updatedTranslationId);

        # verify exiting translation data isn't changed
        $createdTranslation = $this->getTranslation($createdTranslationId);
        $this->assertEquals($createTranslationPayload['term'], $createdTranslation['term']);
        $this->assertEquals([self::TEXT_BACK_TRANSLATION_CONTENT_1], json_decode($createdTranslation['textBackTranslation']));
        $this->assertEquals(self::$model->GetProjectId($createTranslationPayload['PhoneId']), $createdTranslation['projectId']);
    }

    /**
     * @return void
     * @throws GuzzleException
     */
    public function testShouldNotUpdateGivenTranslationIfPhoneIdIsChanged()
    {
        $createTranslationPayload = [
            'PhoneId' => self::PHONE_ID_1,
            'term' => self::TERM_2,
            'textBackTranslation' => [self::TEXT_BACK_TRANSLATION_CONTENT_1],
        ];

        $createdTranslationId = $this->sendRequestAndReturnTranslationId($createTranslationPayload);
        // request payload
        $updateTranslationPayload = [
            'PhoneId' => self::PHONE_ID_2,
            'term' => self::TERM_2,
            'textBackTranslation' => [self::TEXT_BACK_TRANSLATION_CONTENT_UPDATED],
        ];

        // should create a new translation
        $updatedTranslationId = $this->sendRequestAndReturnTranslationId($updateTranslationPayload);
        $this->assertNotEquals($createdTranslationId, $updatedTranslationId);

        # verify exiting translation data isn't changed
        $createdTranslation = $this->getTranslation($createdTranslationId);
        $this->assertEquals($createTranslationPayload['term'], $createdTranslation['term']);
        $this->assertEquals([self::TEXT_BACK_TRANSLATION_CONTENT_1], json_decode($createdTranslation['textBackTranslation']));
        $this->assertEquals(self::$model->GetProjectId($createTranslationPayload['PhoneId']), $createdTranslation['projectId']);
    }

    /**
     * @param array $payload
     * @return int
     * @throws GuzzleException
     */
    private function sendRequestAndReturnTranslationId(array $payload): int
    {
        $response = $this->httpClient->request("POST", self::HOST . self::URI, ['form_params' => $payload]);
        $responseJson = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode());
        $responseArr = json_decode($responseJson, true);
        $this->assertArrayHasKey('TranslationId', $responseArr);
        return $responseArr['TranslationId'];
    }

    private function getTranslation(int $translationId): array
    {
        $q = self::$db->prepare('SELECT * FROM WordLinkRecordings where id = ?');
        $q->execute([$translationId]);
        $translations = $q->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(1, $translations);
        return $translations[0];
    }
}