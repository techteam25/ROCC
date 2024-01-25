<?php
namespace Tests\Helper;

// here you can define custom actions
// all public methods declared in helper class will be available in $I

class AcceptanceTester extends \Codeception\Module
{

// HOOK: used after configuration is loaded
    public function _initialize()
    {
    }

// HOOK: before each suite
    public function _beforeSuite($settings = array())
    {
    }

// HOOK: after suite
    public function _afterSuite()
    {
    }

// HOOK: before each step
    public function _beforeStep(\Codeception\Step $step)
    {
    }

// HOOK: after each step
    public function _afterStep(\Codeception\Step $step)
    {
    }

// HOOK: before test
    public function _before(\Codeception\TestInterface $test)
    {
    }

// HOOK: after test
    public function _after(\Codeception\TestInterface $test)
    {
    }

// HOOK: on fail
    public function _failed(\Codeception\TestInterface $test, $fail)
    {
//        $br = $this->getModule('PhpBrowser');
//        $response = $br->client->getResponse();
//        $this->debug('Response Code: ' . $response);
    }

}
