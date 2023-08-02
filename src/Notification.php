<?php
/**
 * Notification class
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\SsEmail;

use Aws\Exception\AwsException;
use Aws\Sns\SnsClient;

class Notification
{
    /**
     * aws sns object
     *
     * @var Object
     */
    private $SnsClient;

    /**
     * sns client profile
     *
     * @var String
     */
    private $profile;

    /**
     * sns client version
     *
     * @var String
     */
    private $version;

    /**
     * sns client region
     *
     * @var String
     */
    private $region;

    /**
     * aws key
     *
     * @var String
     */
    private $key;

    /**
     * aws secret
     *
     * @var String
     */
    private $secret;

    /**
     * set sns client profile
     *
     * @param $profile
     */
    public function setProfile($profile)
    {
        if (empty($profile)) {
            throw new Exception("profile is empty!");
        }
        $this->profile = $profile;
    }

    /**
     * set sns client version
     *
     * @param $version
     */
    public function setVersion($version)
    {
        if (empty($version)) {
            throw new Exception("version is empty!");
        }
        $this->version = $version;
    }

    /**
     * set sns client region
     *
     * @param $region
     */
    public function setRegion($region)
    {
        if (empty($region)) {
            throw new Exception("region is empty!");
        }
        $this->region = $region;
    }

    /**
     * set aws key
     *
     * @param $key
     */
    public function setKey($key)
    {
        if (empty($key)) {
            throw new Exception("key is empty!");
        }
        $this->key = $key;
    }

    /**
     * set aws secret
     *
     * @param $secret
     */
    public function setSecret($secret)
    {
        if (empty($secret)) {
            throw new Exception("secret is empty!");
        }
        $this->secret = $secret;
    }

    /**
     * get sns profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * get sns version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * get sns region
     */
    public function getRegion()
    {
        return $this->region;
    }

    /**
     * get key
     */
    public function getKey()
    {
        return $this->key;
    }

    /**
     * get secret
     */
    public function getSecret()
    {
        return $this->secret;
    }

    /**
     * initialize
     */
    public function __construct()
    {
        $this->SnsClient = new SnsClient([
            'version' => $this->version,
            'region' => $this->region,
            'credentials' => [
                'key' => $this->key,
                'secret' => $this->secret,
            ],
        ]);
    }

    /**
     * setup subscribe use aws sns
     *
     * @param
     * @return Array
     */
    public function setSubscribe($topic)
    {
        $protocol = 'https';
        $endpoint = 'https://';
        //$topic = 'arn:aws:sns:us-east-1:111122223333:MyTopic';

        try {
            $result = $this->SnSclient->subscribe([
                'Protocol' => $protocol,
                'Endpoint' => $endpoint,
                'ReturnSubscriptionArn' => true,
                'TopicArn' => $topic,
            ]);
            //var_dump($result);
        } catch (AwsException $e) {
            // output error message if fails
            throw new Exception($e->getMessage());
        }

        return json_decode($result, true);
    }

    /**
     * recive aws sns
     *
     * @return Array
     */
    public function getSns()
    {
        try {
            $json = file_get_contents('php://input');
            $data = json_decode($json, true);
            if (!is_array($data)) {
                throw new Exception("Data is not array!");
            }

        } catch (AwsException $e) {
            throw new Exception($e->getMessage());
        }

        return $data;
    }

    /**
     * get topic attr use aws sns
     *
     * @param
     * @return Array
     */
    public function getTopicAttr($topic)
    {
        try {
            $result = $this->SnSclient->getTopicAttributes([
                'TopicArn' => $topic,
            ]);
            //var_dump($result);
        } catch (AwsException $e) {
            // output error message if fails
            throw new Exception($e->getMessage());
        }

        return json_decode($result, true);
    }

    /**
     * confirm subscription use aws sns
     *
     * @param
     * @return Array
     */
    public function confirmSubscription($subscription_token, $topic)
    {
        //$subscription_token = 'arn:aws:sns:us-east-1:111122223333:MyTopic:123456-abcd-12ab-1234-12ba3dc1234a';
        //$topic = 'arn:aws:sns:us-east-1:111122223333:MyTopic';

        try {
            $result = $SnSclient->confirmSubscription([
                'Token' => $subscription_token,
                'TopicArn' => $topic,
            ]);
            //var_dump($result);
        } catch (AwsException $e) {
            // output error message if fails
            throw new Exception($e->getMessage());
        }
        
        return json_decode($result, true);
    }
}
