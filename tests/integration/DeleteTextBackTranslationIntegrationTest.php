<?php

namespace integration;


use GuzzleHttp\Exception\GuzzleException;

class DeleteTextBackTranslationIntegrationTest extends BaseIntegrationTest
{
    const UPLOAD_URI = '/API/UploadTextBackTranslation.php';
    const DELETE_URI = '/API/DeleteTextBackTranslation.php';
    const PHONE_ID_1 = "rstuvw";

    const TERM_1 = "Naomi";
    const TEXT_BACK_TRANSLATION_CONTENT_1 = "Test data for text back translation 1";

    /**
     * @throws \Exception
     * @throws GuzzleException
     */
    public function testDeleteTranslation(): void
    {
        $createTranslationPayload = [
            'PhoneId' => self::PHONE_ID_1,
            'term' => self::TERM_1,
            'textBackTranslation' => [
                self::TEXT_BACK_TRANSLATION_CONTENT_1,
            ]
        ];

        $createTranslationResponse = $this->sendRequestAndReturnResponse(self::UPLOAD_URI, $createTranslationPayload);
        $this->assertArrayHasKey('TranslationId', $createTranslationResponse);
        $createdTranslationId  = $createTranslationResponse['TranslationId'];


        // delete translation
        $deleteTranslationPayload = [
            'PhoneId' => self::PHONE_ID_1,
            'term' => self::TERM_1,
        ];

        $this->assertNull($this->sendRequestAndReturnResponse(self::DELETE_URI, $deleteTranslationPayload));

        # verify in DB that WordLinkRecordings is deleted
        $q = self::$db->prepare('SELECT * FROM WordLinkRecordings where id = ?');
        $q->execute([$createdTranslationId]);
        $translations = $q->fetchAll(\PDO::FETCH_ASSOC);
        $this->assertCount(0, $translations);
    }

    function sendRequestAndReturnResponse(string $uri, array $payload): array|null
    {
        $response = $this->httpClient->request("POST", self::HOST . $uri, ['form_params' => $payload]);
        $responseJson = $response->getBody()->getContents();
        $this->assertEquals(200, $response->getStatusCode());
        return json_decode($responseJson, true);
    }
}