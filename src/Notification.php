<?php
/**
 * Notification class
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\SsEmail;

use Aws\Sns\SnsClient; 
use Aws\Exception\AwsException;

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
     * set sns client profile
     * 
     * @param $profile
     */
    public function setProfile($profile)
    {
        if (empty($profile)) {
            throw new Exception ("profile is empty!");
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
            throw new Exception ("version is empty!");
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
            throw new Exception ("region is empty!");
        }   
        $this->region = $region;
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
     * initialize
     */
    public function __construct()
    {
        $this->SnsClient = new SnsClient([
            'profile' => $this->profile,
            'version' => $this->version,
            'region' => $this->region
        ]);
    }

    /**
     * setup subscribe use aws sns
     *
     * @param
     * @return Array
     */
    public function setSubscribe($topic);
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
            throw new Exception ($e->getMessage());
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
                throw new Exception ("Data is not array!");
            }
            
        } catch (AwsException $e) {
            throw new Exception ($e->getMessage());
        }

        return $data;
    }

    /**
     * get topic attr use aws sns
     *
     * @param
     * @return Array
     */
    public function getTopicAttr($topic);
    {
        try {
            $result = $this->SnSclient->getTopicAttributes([
                'TopicArn' => $topic,
            ]);
            //var_dump($result);
        } catch (AwsException $e) {
            // output error message if fails
            throw new Exception ($e->getMessage());
        } 
        
        return $result;
    }

}