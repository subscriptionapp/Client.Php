<?php

/**
 * Created by PhpStorm.
 * User: Jon
 * Date: 9/29/2015
 * Time: 9:40 PM
 */
require('src/SubscriptionClient.php');
class SubscriptionClientTest_IsNotNull extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $mySqlConnection = array(
            'host'=> '127.0.0.1',
            'user' => 'root',
            'password' => 'dar87land',
            'db'=> 'php_client_test',
            'port' => 3306,
            'socket'=> null
        );
        $client = new \SubscriptionClient\SubscriptionClient("","",$mySqlConnection);
        $this->assertNotNull($client);
    }
}
class SubscriptionClientTest_ShouldCreateTables extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $mySqlConnection = [
            'host'=> '127.0.0.1',
            'user' => 'root',
            'password' => 'dar87land',
            'db'=> 'php_client_test',
            'port' => 3306,
            'socket'=> null
        ];
        //drop the table
        $this->conn = new mysqli($mySqlConnection['host'],$mySqlConnection['user'],$mySqlConnection['password'],$mySqlConnection['db'],$mySqlConnection['port'],$mySqlConnection['socket']);
        //$this->conn->query('drop table subscription_app_config');
        //$this->conn->query('drop table subscription_app_records');
        //creating the client checks for and creates the tables, so verify tables exists
        $client = new \SubscriptionClient\SubscriptionClient('','',$mySqlConnection);
        $res = $this->conn->query("SHOW TABLES LIKE 'subscription_app_config'");
        $exist = ($res && mysqli_num_rows($res) > 0);
        $this->assertEquals(true,$exist);
        $res = $this->conn->query("SHOW TABLES LIKE 'subscription_app_records'");
        $exist = ($res && mysqli_num_rows($res) > 0);
        $this->assertEquals(true,$exist);
    }

}
class SubscriptionClientTest_ShouldGetSubscriberByKey extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $mySqlConnection = [
            'host'=> '127.0.0.1',
            'user' => 'root',
            'password' => 'dar87land',
            'db'=> 'php_client_test',
            'port' => 3306,
            'socket'=> null
        ];
        $client = new \SubscriptionClient\SubscriptionClient('http://localhost:65386/','wcd1ikGGf0yNqxvl7R9RBg',$mySqlConnection);
        $subscriber = $client->getSubscriptionForKey('FyKHM9Y5T0izExHZ8zz90w');
        $this->assertEquals('FyKHM9Y5T0izExHZ8zz90w',$subscriber['Key']);
    }

}
class SubscriptionClientTest_ShouldGetSubscriberByAppId extends PHPUnit_Framework_TestCase
{
    public function test()
    {
        $mySqlConnection = [
            'host'=> '127.0.0.1',
            'user' => 'root',
            'password' => 'dar87land',
            'db'=> 'php_client_test',
            'port' => 3306,
            'socket'=> null
        ];
        $client = new \SubscriptionClient\SubscriptionClient('http://localhost:65386/','wcd1ikGGf0yNqxvl7R9RBg',$mySqlConnection);
        $subscriber = $client->getSubscriptionForAppId('newappid');
        $this->assertEquals('FyKHM9Y5T0izExHZ8zz90w',$subscriber['Key']);
        $this->assertEquals(22,$subscriber['EnterpriseChatLimit']);
        $this->assertEquals(true,$subscriber['RoutingEnabled']);
        $this->assertEquals(new DateTime('2015-09-05'),$subscriber['NewVersionDate']);
        $this->assertEquals('basic',$subscriber['PlanName']);
        $this->assertEquals(55.55,$subscriber['DailyRate']);
        $this->assertEquals(false,$subscriber['AllowConversionTracking']);
        //second get will come from db
        $subscriber = $client->getSubscriptionForAppId('newappid');
        $this->assertEquals('FyKHM9Y5T0izExHZ8zz90w',$subscriber['Key']);
        $this->assertEquals(22,$subscriber['EnterpriseChatLimit']);
        $this->assertEquals(true,$subscriber['RoutingEnabled']);
        $this->assertEquals(new DateTime('2015-09-05'),$subscriber['NewVersionDate']);
        $this->assertEquals('basic',$subscriber['PlanName']);
        $this->assertEquals(55.55,$subscriber['DailyRate']);
        $this->assertEquals(false,$subscriber['AllowConversionTracking']);
    }

}
class SubscriptionClientTest_ShouldGetClearSubscribersWhenConfigExpires extends PHPUnit_Framework_TestCase
{
    private $conn;
    private $configTable = 'subscription_app_config';
    private $subscribersTable = 'subscription_app_records';
    public function test()
    {
        $mySqlConnection = [
            'host'=> '127.0.0.1',
            'user' => 'root',
            'password' => 'dar87land',
            'db'=> 'php_client_test',
            'port' => 3306,
            'socket'=> null
        ];
        $client = new \SubscriptionClient\SubscriptionClient('http://localhost:65386/','wcd1ikGGf0yNqxvl7R9RBg',$mySqlConnection);
        $this->conn = new mysqli($mySqlConnection['host'],$mySqlConnection['user'],$mySqlConnection['password'],$mySqlConnection['db'],$mySqlConnection['port'],$mySqlConnection['socket']);
        // getting the subscription will create a record in the db
        $subscriber = $client->getSubscriptionForAppId('newappid');

        $config = $client->getConfiguration();

        $sql = "DELETE FROM $this->configTable";
        $this->conn->query($sql);
        $expires = new DateTime('2015-10-02 00:40:54');
        $config['Expires'] = $expires->format("Y-m-d H:i:s");
        $sql = "INSERT INTO $this->configTable (ConfigJson) VALUES ('" . json_encode($config) ."')";
        $this->conn->query($sql);
        $client->getConfiguration();
        $res = $this->conn->query("SELECT SubscriberJson FROM $this->subscribersTable WHERE AppID = 'newappid'");
        $this->assertEquals(false,$res && mysqli_num_rows($res) > 0);
    }

}
class SubscriptionClientTest_ShouldGetAllSubscribers extends PHPUnit_Framework_TestCase
{
    private $conn;
    private $configTable = 'subscription_app_config';
    private $subscribersTable = 'subscription_app_records';
    public function test()
    {
        $mySqlConnection = [
            'host'=> '127.0.0.1',
            'user' => 'root',
            'password' => 'dar87land',
            'db'=> 'php_client_test',
            'port' => 3306,
            'socket'=> null
        ];
        $client = new \SubscriptionClient\SubscriptionClient('http://localhost:65386/','wcd1ikGGf0yNqxvl7R9RBg',$mySqlConnection);
        $this->conn = new mysqli($mySqlConnection['host'],$mySqlConnection['user'],$mySqlConnection['password'],$mySqlConnection['db'],$mySqlConnection['port'],$mySqlConnection['socket']);
        //clear table
        $sql = "DELETE FROM $this->subscribersTable";
        $this->conn->query($sql);

        $allSubscribers = $client->GetSubscriptions();
        $this->assertEquals(31, count($allSubscribers));
        //second call comes from db
        $allSubscribers = $client->GetSubscriptions();
        $this->assertEquals(31, count($allSubscribers));
    }
}
class SubscriptionClientTest_ShouldCreateSusbcriber extends PHPUnit_Framework_TestCase
{
    private $conn;
    private $subscribersTable = 'subscription_app_records';
    public function test()
    {
        $mySqlConnection = [
            'host'=> '127.0.0.1',
            'user' => 'root',
            'password' => 'dar87land',
            'db'=> 'php_client_test',
            'port' => 3306,
            'socket'=> null
        ];
        $client = new \SubscriptionClient\SubscriptionClient('http://localhost:65386/','wcd1ikGGf0yNqxvl7R9RBg',$mySqlConnection);
        $this->conn = new mysqli($mySqlConnection['host'],$mySqlConnection['user'],$mySqlConnection['password'],$mySqlConnection['db'],$mySqlConnection['port'],$mySqlConnection['socket']);
        $args = [
            'SubscriptionTypeId' => 1
        ];
        $newSubscriber = $client->createSubscription($args);
        $this->conn->query("DELETE FROM $this->subscribersTable WHERE SubscriberKey = '" . $newSubscriber['Key'] . "'");
    }
}
class SubscriptionClientTest_ShouldRemoveSubscriber extends PHPUnit_Framework_TestCase
{
    private $conn;
    private $subscribersTable = 'subscription_app_records';
    public function test()
    {
        $mySqlConnection = [
            'host'=> '127.0.0.1',
            'user' => 'root',
            'password' => 'dar87land',
            'db'=> 'php_client_test',
            'port' => 3306,
            'socket'=> null
        ];
        $client = new \SubscriptionClient\SubscriptionClient('http://localhost:65386/','wcd1ikGGf0yNqxvl7R9RBg',$mySqlConnection);
        $this->conn = new mysqli($mySqlConnection['host'],$mySqlConnection['user'],$mySqlConnection['password'],$mySqlConnection['db'],$mySqlConnection['port'],$mySqlConnection['socket']);
        $this->conn->query("INSERT INTO $this->subscribersTable (SubscriberKey, AppID, SubscriberJson) VALUES('XXXXX', 'XXXXX', 'XXXXX')");
        $client->subscriptionRemoved(array('Key' => 'XXXXX'));
        $subscriber = $client->getSubscriptionForKey('XXXXX');
        $this->assertNull($subscriber);
    }
}
class SubscriptionClientTest_ShouldUpdateSusbcriber extends PHPUnit_Framework_TestCase
{
    private $conn;
    private $subscribersTable = 'subscription_app_records';
    public function test()
    {
        $mySqlConnection = [
            'host'=> '127.0.0.1',
            'user' => 'root',
            'password' => 'dar87land',
            'db'=> 'php_client_test',
            'port' => 3306,
            'socket'=> null
        ];
        $client = new \SubscriptionClient\SubscriptionClient('http://localhost:65386/','wcd1ikGGf0yNqxvl7R9RBg',$mySqlConnection);
        $subscriber = $client->getSubscriptionForAppId('8e97b5d6-b822-4b4d-b165-811df565ea2d');
        $subscriber['PlanName'] = 'newPlanName';
        $subscriber['EnterpriseChatLimit'] = 23;
        $subscriber['RoutingEnabled'] = false;
        $subscriber['NewVersionDate'] = new DateTime('2015-10-10');
        $subscriber['DailyRate'] = 44.44;
        $subscriber['Name'] = 'newname';
        $now = new DateTime('now');
        $subscriber['ExpirationDate'] = $now;
        $updated = $client->updateSubscription($subscriber);
        $this->assertEquals('newPlanName', $updated['PlanName']);
        $this->assertEquals(23, $updated['EnterpriseChatLimit']);
        $this->assertEquals(false, $updated['RoutingEnabled']);
        $this->assertEquals(new DateTime('2015-10-10'), $updated['NewVersionDate']);
        $this->assertEquals(44.44, $updated['DailyRate']);
        $this->assertEquals('newname', $updated['Name']);
        $this->assertEquals($now, $updated['ExpirationDate']);


    }
}

class SubscriptionClientTest_ShouldParseFeatureValues extends PHPUnit_Framework_TestCase
{

    public function test()
    {
        $mySqlConnection = [
            'host'=> '127.0.0.1',
            'user' => 'root',
            'password' => 'dar87land',
            'db'=> 'php_client_test',
            'port' => 3306,
            'socket'=> null
        ];

        $feature = [
            'DataType' => 0,
            'Value' => 'true'
        ];


        $client = new \SubscriptionClient\SubscriptionClient('','',$mySqlConnection);
        $value = $client->getDerivedValue($feature);
        $this->assertEquals(true,$value);

        $feature['DataType'] = 1;
        $feature['Value'] = "1";
        $value = $client->getDerivedValue($feature);
        $this->assertEquals(1,$value);

        $feature['DataType'] = 2;
        $feature['Value'] = "2013-07-09T04:58:23.075Z";
        $value = $client->getDerivedValue($feature);
        $this->assertEquals(new DateTime("2013-07-09T04:58:23.075Z"),$value);

        $feature['DataType'] = 3;
        $feature['Value'] = "WORD";
        $value = $client->getDerivedValue($feature);
        $this->assertEquals("WORD",$value);

        $feature['DataType'] = 4;
        $feature['Value'] = "22.22";
        $value = $client->getDerivedValue($feature);
        $this->assertEquals(22.22,$value);
    }
}
