<?php
/**
 * Created by PhpStorm.
 * User: Jon
 * Date: 9/28/2015
 * Time: 10:18 PM
 */

namespace SubscriptionClient;

class WebClientService
{
    private $endpoint;
    private $authToken;
    private $byKeyUrl = "api/client/getsubscriptionbykey/";
    private $byAppIdUrl = "api/client/getsubscriptionbyapplicationid/";
    private $allSubscribersUrl = "api/client/getsubscriptions";
    private $configUrl = "api/client/getconfiguration";
    private $createSubscriberUrl = "api/client/createsubscription";
    private $updateSubscriberUrl = "api/client/updatesubscription";

    function __construct($url, $token){
        $this->endpoint = $url;
        $this->authToken = $token;
        if(false == function_exists('curl_init')){
            throw new \Exception('Curl required for SubscriptionClient');
        }
    }

    public function getSubscriberByKey($key){
        $curl = curl_init($this->endpoint . $this->byKeyUrl . $key);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: $this->authToken",
            "Content-Type: application/json"
        ));

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $res = curl_exec($curl);
        curl_close($curl);
        if(false == $res) throw new \Exception("Unable to get susbcriber from endpoint: $this->endpoint");
        return $res;
    }

    public function getSubscriberByAppId($appId){
        $curl = curl_init($this->endpoint . $this->byAppIdUrl . $appId);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: $this->authToken",
            "Content-Type: application/json"
        ));

        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

        $res = curl_exec($curl);
        curl_close($curl);
        if(false == $res) throw new \Exception("Unable to get susbcriber from endpoint: $this->endpoint");
        return $res;
    }

    public function getConfig(){
        $curl = curl_init($this->endpoint . $this->configUrl );
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: $this->authToken",
            "Content-Type: application/json"
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($curl);
        curl_close($curl);
        if(false == $res) throw new \Exception("Unable to get configuration from endpoint: $this->endpoint");
        return $res;
    }

    public function getSubscribers(){
        $curl = curl_init($this->endpoint . $this->allSubscribersUrl );
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: $this->authToken",
            "Content-Type: application/json"
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        $res = curl_exec($curl);
        curl_close($curl);
        if(false == $res) throw new \Exception("Unable to get susbcribers from endpoint: $this->endpoint");
        return $res;
    }

    public function createSubscription($json){
        $curl = curl_init($this->endpoint . $this->createSubscriberUrl );
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: $this->authToken",
            "Content-Type: application/json",
            "Content-Length: " . strlen($json)
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($curl, CURLOPT_POSTFIELDS,  $json);
        $res = curl_exec($curl);
        curl_close($curl);
        if(false == $res) throw new \Exception("Unable to create susbcription for endpoint: $this->endpoint");
        return $res;
    }

    public function updateSubscription($json){
        $curl = curl_init($this->endpoint . $this->updateSubscriberUrl );
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Authorization: $this->authToken",
            "Content-Type: application/json",
            "Content-Length: " . strlen($json)
        ));
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($curl, CURLOPT_POSTFIELDS,  $json);
        $res = curl_exec($curl);
        curl_close($curl);
        if(false == $res) throw new \Exception("Unable to update susbcription for endpoint: $this->endpoint");
        return $res;
    }
}