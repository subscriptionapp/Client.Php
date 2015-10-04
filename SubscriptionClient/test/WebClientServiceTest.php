<?php

/**
 * Created by PhpStorm.
 * User: Jon
 * Date: 9/29/2015
 * Time: 10:50 PM
 */
require('src/WebClientService.php');
class WebClientServiceTest_IsNotNull extends PHPUnit_Framework_TestCase
{

    public function test()
    {
        $webClient = new \SubscriptionClient\WebClientService('url','token');
        $this->assertNotNull($webClient);
    }
}

class WebClientServiceTest_ShouldGetSubscriberJson extends PHPUnit_Framework_TestCase
{

    public function test()
    {
        $webClient = new \SubscriptionClient\WebClientService('http://localhost:65386/','wcd1ikGGf0yNqxvl7R9RBg');
        $res = $webClient->getSubscriberByKey('FyKHM9Y5T0izExHZ8zz90w');
        $this->assertNotEquals("",$res);
    }
}

class WebClientServiceTest_ShouldGetConfigJson extends PHPUnit_Framework_TestCase
{

    public function test()
    {
        $webClient = new \SubscriptionClient\WebClientService('http://localhost:65386/','wcd1ikGGf0yNqxvl7R9RBg');
        $res = $webClient->getConfig();
        $this->assertNotEquals("",$res);
    }
}
class WebClientServiceTest_ShouldGetAllSubscribers extends PHPUnit_Framework_TestCase
{

    public function test()
    {
        $webClient = new \SubscriptionClient\WebClientService('http://localhost:65386/','wcd1ikGGf0yNqxvl7R9RBg');
        $res = $webClient->getSubscribers();
        $this->assertNotEquals("",$res);
    }
}


