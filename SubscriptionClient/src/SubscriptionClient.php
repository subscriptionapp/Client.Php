<?php
/**
 * Created by PhpStorm.
 * User: Jon
 * Date: 9/28/2015
 * Time: 10:16 PM
 */

namespace SubscriptionClient;
use DateTime;
use DateInterval;
use mysqli;
class SubscriptionClient
{
    private $webClientService;
    private $conn;
    private $configTable = 'subscription_app_config';
    private $subscribersTable = 'subscription_app_records';
    private $db;

    function __construct($endpoint, $authToken, $mySqlConnection){
        date_default_timezone_set('UTC');
        $this->webClientService = new WebClientService($endpoint,$authToken);
        $this->db = $mySqlConnection['db'];
        $this->conn = new mysqli($mySqlConnection['host'],$mySqlConnection['user'],$mySqlConnection['password'],$mySqlConnection['db'],$mySqlConnection['port'],$mySqlConnection['socket']);
        //create config table if it doesn't exist
        if(false == $this->tableExists($this->configTable)){
            $sql = "CREATE TABLE $this->configTable (ID INT NOT NULL AUTO_INCREMENT PRIMARY KEY,ConfigJson TEXT)";
            $this->conn->query($sql);
        }
        //create subscribers table if it doesn't exist
        if(false == $this->tableExists($this->subscribersTable)){
            $sql = "CREATE TABLE $this->subscribersTable (SubscriberKey VARCHAR(40)NOT NULL PRIMARY KEY, AppID VARCHAR(100), SubscriberJson TEXT)";
            $this->conn->query($sql);
        }
    }

    public function getConfiguration(){
        return $this->getConfig();
    }

    public function subscriptionUpdated($subscriber){
        $this->insertOrUpdateSubscriber($subscriber);
    }

    public function subscriptionRemoved($subscriber){
        $this->conn->query("DELETE FROM $this->subscribersTable WHERE SubscriberKey = '" . $subscriber['Key'] . "'") ;
    }

    public function updateSubscription($subscriber){
        $original = $this->getSubscriptionModelForKey($subscriber['Key']);
        if(!isset($original)){
            throw new \Exception('Cannot update subscription,cannot find original subscriber with key:' . $subscriber['key']);
        }
        foreach($subscriber as $key => $value){
            if(array_key_exists($key, $original)){
                $original[$key] = $value;
                continue;
            }

            foreach($original['Features'] as $index => $feature){
                if($feature['PropertyName'] == $key){
                    if($value instanceof DateTime){
                        $original['Features'][$index]['Value'] = $value->format('Y-m-d H:i:s');
                    }
                    else if(is_double($value) || is_int($value)){
                        $original['Features'][$index]['Value'] = "$value";
                    }
                    else if(is_bool($value)){
                        if($value){
                            $original['Features'][$index]['Value'] = 'true';
                        } else{
                            $original['Features'][$index]['Value'] = 'false';
                        }
                    } else{
                        $original['Features'][$index]['Value'] = $value;
                    }
                }
            }
        }
        $original['ExpirationDate'] = $original['ExpirationDate']->format('Y-m-d H:i:s');
        $json = $this->webClientService->updateSubscription(json_encode($original));
        $updatedSubscriber = json_decode($json, true);
        $this->insertOrUpdateSubscriber($updatedSubscriber);
        return $this->toDynamic($updatedSubscriber);
    }

    public function createSubscription($args){
        $config = $this->getConfig();
        $subscriptionTypes = $config['SubscriptionTypes'];
        foreach($subscriptionTypes as $subscriptionType){
            if($args['SubscriptionTypeId'] == $subscriptionType['Id']){
                $st = $subscriptionType;
            }
        }

        if(!isset($st)){
            throw new \Exception('No matching subscription type for SubscriptionTypeId' . $args['SubscriptionTypeId']);
        }
        $expires = new DateTime();
        if(isset($st['TimeToExpireTicks']) && $st['TimeToExpireTicks'] > 0){
            $seconds = $st['TimeToExpireTicks'] / 10000000;
            $expires->add(new DateInterval("PT" . $seconds ."S"));
        }
        $model = [
            'ApplicationId'=> isset($args['ApplicationId']) ? $args['ApplicationId'] : '',
            'BillingSystemType'=> isset($args['BillingSystemType']) ? $args['BillingSystemType'] : '',
            'CompanyId'=> $config['Company']['Id'],
            'DefaultGracePeriod'=> $st['DefaultGracePeriod'],
            'DefaultNeverExpire'=> $st['DefaultNeverExpire'],
            'DefaultResetFeaturesOnRenewal'=> $st['DefaultResetFeaturesOnRenewal'],
            'DefaultRevertOnExpiration'=> $st['DefaultRevertOnExpiration'],
            'DefaultRevertTo'=> $st['DefaultRevertTo'],
            'Name'=> isset($args['Name']) ? $args['Name'] : '',
            'SubscriptionTypeId'=> $st['Id'],
            'Features'=> $st['Features'],
            'ExpirationDate'=> $expires->format("Y-m-d H:i:s"),
        ];
        $json = $this->webClientService->createSubscription(json_encode($model));
        $subscriber = json_decode($json, true);
        $this->insertOrUpdateSubscriber($subscriber);
        return $this->toDynamic($subscriber);
    }

    public function getSubscriptions(){
        $this->getConfig();
        $res = $this->conn->query("SELECT SubscriberJson FROM $this->subscribersTable");
        if($res && mysqli_num_rows($res) > 0){
            $subscribers = [];
            while ($row = $res->fetch_row()){
                $json = $row[0];
                $subscriber = json_decode($json, true);
                array_push($subscribers, $subscriber);
            }
            return $this->toDynamics($subscribers);
        } else{
            $json = $this->webClientService->getSubscribers();
            $subscribers = json_decode($json, true);
            foreach($subscribers as $subscriber){
                $this->insertOrUpdateSubscriber($subscriber);
            }
            return $this->toDynamics($subscribers);
        }
    }

    public function getSubscriptionForKey($key){
        $this->getConfig();
        //attempt get record from mysql
        $res = $this->conn->query("SELECT SubscriberJson FROM $this->subscribersTable WHERE SubscriberKey = '$key'");
        if(false == $res || mysqli_num_rows($res) == 0){
            //failed to get from my sql attempt to get it from webservice
            $json = $this->webClientService->getSubscriberByKey($key);
            if('' == $json){
                return null;
            }
            $subscriber = json_decode($json, true);
            if(array_key_exists('Error', $subscriber)){
                return null;
            }
            $this->insertOrUpdateSubscriber($subscriber);
            return $this->toDynamic($subscriber);
        } else{
            $record = $res->fetch_row();
            $subscriber = json_decode($record[0], true);
            return $this->toDynamic($subscriber);
        }
    }

    private function getSubscriptionModelForKey($key){
        $this->getConfig();
        //attempt get record from mysql
        $res = $this->conn->query("SELECT SubscriberJson FROM $this->subscribersTable WHERE SubscriberKey = '$key'");
        if(false == $res || mysqli_num_rows($res) == 0){
            //failed to get from my sql attempt to get it from webservice
            $json = $this->webClientService->getSubscriberByKey($key);
            if('' == $json){
                return null;
            }
            $subscriber = json_decode($json, true);
            if(array_key_exists('Error', $subscriber)){
                return null;
            }
            $this->insertOrUpdateSubscriber($subscriber);
            return $subscriber;
        } else{
            $record = $res->fetch_row();
            $subscriber = json_decode($record[0], true);
            return $subscriber;
        }
    }

    public function getSubscriptionForAppId($appId){
        $this->getConfig();
        //attempt get record from mysql
        $res = $this->conn->query("SELECT SubscriberJson FROM $this->subscribersTable WHERE AppID = '$appId'");
        if(false == $res || mysqli_num_rows($res) == 0){
            //failed to get from my sql attempt to get it from webservice
            $json = $this->webClientService->getSubscriberByAppId($appId);
            $subscriber = json_decode($json, true);
            $this->insertOrUpdateSubscriber($subscriber);
            return $this->toDynamic($subscriber);
        } else{
            $record = $res->fetch_row();
            $subscriber = json_decode($record[0], true);
            return $this->toDynamic($subscriber);
        }
    }

    private function toDynamics($subscribers){
        $result = [];
        foreach($subscribers as $subscriber){
            array_push($result, $this->toDynamic($subscriber));
        }

        return $result;
    }

    private function toDynamic($subscriber){
        $model = [];
        foreach($subscriber['Features'] as $feature){
            $derivedValue = $this->getDerivedValue($feature);
            $model[$feature['PropertyName']] = $derivedValue;
        }
        $model['SubscriptionTypeId'] = $subscriber['SubscriptionTypeId'];
        $model['Id'] = $subscriber['Id'];
        $model['Name'] = $subscriber['Name'];
        $model['ApplicationId'] = $subscriber['ApplicationId'];
        $model['Key'] = $subscriber['Key'];
        $model['ExpirationDate'] = new DateTime($subscriber['ExpirationDate']);
        $expiration = new DateTime($subscriber['ExpirationDate']);
        if($subscriber['DefaultGracePeriod'] > 0){
            $expiration->add(new DateInterval("P" . $subscriber['DefaultGracePeriod'] . "D"));
        }
        $model['IsExpired'] = !$subscriber['DefaultNeverExpire'] && ($expiration < new DateTime());
        return $model;
    }

    public function getDerivedValue($feature){
        $value = $feature['Value'];
        if(0 == $feature['DataType']){
            return filter_var($value, FILTER_VALIDATE_BOOLEAN);
        }
        if(1 == $feature['DataType']){
            return intval($value);
        }
        if(2 == $feature['DataType']){
            return new DateTime($value);
        }
        if(3 == $feature['DataType']){
            return $value;
        }
        if(4 == $feature['DataType']){
            return floatval($value);
        }
        return null;
    }

    private function tableExists($table) {
        $res = $this->conn->query("SHOW TABLES LIKE $table");
        return($res && mysqli_num_rows($res) > 0);
    }

    private function getConfig(){
        $sql = "SELECT ConfigJson from $this->configTable";
        $res = $this->conn->query($sql);
        if(false == $res || mysqli_num_rows($res) == 0){
            //not there get from webclient
            $configJson = $this->webClientService->getConfig();
            $config = json_decode($configJson, true);
            $this->insertConfig($config);
            $this->clearSubscribers();
        } else{
            $configJson = $res->fetch_row();
            $config = json_decode($configJson[0], true);
            if(new DateTime($config['Expires']) < new DateTime()){
                //expired update from webclient and clear subscribers
                $configJson = $this->webClientService->getConfig();
                $config = json_decode($configJson, true);
                $this->insertConfig($config);
                $this->clearSubscribers();
            }
        }
        return $config;
    }

    private function insertConfig($config){
        $sql = "DELETE FROM $this->configTable";
        $this->conn->query($sql);
        $expires = new DateTime();
        if(isset($config['Company']['CacheTimeoutTicks']) && $config['Company']['CacheTimeoutTicks'] > 0){
            $seconds = $config['Company']['CacheTimeoutTicks'] / 10000000;
            $expires->add(new DateInterval("PT" . $seconds ."S"));
        }
        $config['Expires'] = $expires->format("Y-m-d H:i:s");
        $sql = "INSERT INTO $this->configTable (ConfigJson) VALUES ('" . json_encode($config) ."')";
        $this->conn->query($sql);
    }

    private function insertOrUpdateSubscriber($subscriber){
        $res = $this->conn->query("SELECT FROM $this->subscribersTable WHERE SubscriberKey = " . $subscriber['Key']);
        if($res && mysqli_num_rows($res) > 0){
            $this->conn->query("UPDATE $this->subscribersTable SET AppID = '" . $subscriber['ApplicationId'] . "', SET SubscriberJson = '" . json_encode($subscriber) . "' WHERE SubscriberKey = '" . $subscriber['Key'] . "'");
        } else {
            $this->conn->query("INSERT INTO $this->subscribersTable (SubscriberKey, AppID, SubscriberJson) VALUES('" . $subscriber['Key'] . "', '" . $subscriber['ApplicationId'] . "', '" . json_encode($subscriber) . "')");
        }
    }

    private function clearSubscribers(){
        $sql = "DELETE FROM $this->subscribersTable";
        $this->conn->query($sql);
    }
}