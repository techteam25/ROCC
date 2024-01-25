<?php
// https://blog.cloudflare.com/using-guzzle-and-phpunit-for-rest-api-testing
// https://dev.to/icolomina/testing-an-external-api-using-phpunit-m8j
namespace Tests\Acceptance;

use Tests\AcceptanceTester;

class MyTestCest
{
    public function _before(AcceptanceTester $I)
    {
    }

    public function testUploadSlideBackTranslation(AcceptanceTester $I,  \Codeception\Scenario $scenario)
    {

        $url = '/API/uploadSlideBacktranslation.php';

        // request paylaod
        $postData = [
            'Key' => 'value',
            'PhoneId' => 'another_value',
            'Data' => 'another_value',
            'TemplateTitle' => 'another_value',
        ];


        $I->sendAjaxPostRequest($url, $postData);
        $I->seeResponseCodeIs(200);
//        $I->copyDir('vendor','old_vendor');
//        $I->seeInDatabase('users', ['name' => 'Davert', 'email' => 'davert@mail.com']);
    }
}
