<?php
/**
 * Send mail class
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\SsEmail;

use Aws\Ses\SesClient;
use Aws\Exception\AwsException;

class SendMail
{
    /**
     * aws ses object
     *
     * @var Object
     */
    private $SesClient;

    /**
     * sender email
     *
     * @var String
     */
    private $sender_email;

    /**
     * recipient eamils
     *
     * @var Array
     */
    private $recipient_emails = array();

    /**
     * subject
     *
     * @var String
     */
    private $subject;

    /**
     * html body
     *
     * @var String
     */
    private $html_body;

    /**
     * plaintext body
     *
     * @var String
     */
    private $plaintext_body;

    /**
     * email cc
     *
     * @var Array
     */
    private $cc = array();
    
    /**
     * email bcc
     *
     * @var Array
     */
    private $bcc = array();

    /**
     * email attachments
     *
     * @var Array
     */
    private $attachments = array();

    /**
     * ses client profile
     *
     * @var String
     */
    private $profile;

    /**
     * ses client version
     *
     * @var String
     */
    private $version;
    
    /**
     * ses client region
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
     * set sender email
     * 
     * @param $sender_email
     */
    public function setSender($sender_email)
    {
        if (empty($sender_email)) {
            throw new Exception ("sender email is empty!");
        }
        $this->sender_email = $sender_email;
    }

    /**
     * set recipient email
     * 
     * @param $recipient_emails
     */
    public function setRecipient(Array $recipient_emails)
    {
        if (empty($recipient_emails)) {
            throw new Exception ("recipient emails is empty!");
        }
        $this->recipient_emails = $recipient_emails;
    }

    /**
     * set email cc
     * 
     * @param $cc
     */
    public function setCc(Array $cc)
    {
        if (empty($cc)) {
            throw new Exception ("email cc is empty!");
        }
        $this->cc = $cc;
    }

    /**
     * set email bcc
     * 
     * @param $bcc
     */
    public function setBcc(Array $bcc)
    {
        if (empty($bcc)) {
            throw new Exception ("email bcc is empty!");
        }
        $this->bcc = $bcc;
    }

    /**
     * set email subject
     * 
     * @param $subject
     */
    public function setSubject($subject)
    {
        if (empty($subject)) {
            throw new Exception ("subject is empty!");
        }
        $this->subject = $subject;
    }

    /**
     * set html body
     * 
     * @param $html_body
     */
    public function setHtmlBody($html_body)
    {
        if (empty($html_body)) {
            throw new Exception ("html body is empty!");
        }
        $this->html_body = $html_body;
    }

    /**
     * set plaintext body
     * 
     * @param $plaintext_body
     */
    public function setPlaintextBody($plaintext_body)
    {
        if (empty($plaintext_body)) {
            throw new Exception ("plaintext body is empty!");
        }
        $this->plaintext_body = $plaintext_body;
    }

    /**
     * set mail attachments
     * 
     * @param $attachments
     */
    public function setAttachments(Array $attachments)
    {
        if (empty($attachments)) {
            throw new Exception ("attachments is empty!");
        }   
        $this->attachments = $attachments;
    }

    /**
     * set ses client profile
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
     * set ses client version
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
     * set ses client region
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
     * set aws key
     * 
     * @param $key
     */
    public function setKey($key)
    {
        if (empty($key)) {
            throw new Exception ("key is empty!");
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
            throw new Exception ("secret is empty!");
        }   
        $this->secret = $secret;
    }

    /**
     * get sender email
     */
    public function getSender()
    {
        return $this->sender_email;
    }

    /**
     * get recipient emails
     */
    public function getRecipient()
    {
        return $this->recipient_emails;
    }

    /**
     * get email cc
     */
    public function getCc()
    {
        return $this->cc;
    }

    /**
     * get email bcc
     */
    public function getBcc()
    {
        return $this->bcc;
    }

    /**
     * get email subject
     */
    public function getSubject()
    {
        return $this->subject;
    }

    /**
     * get html body
     */
    public function getHtmlBody()
    {
        return $this->html_body;
    }
    
    /**
     * get plaintext body
     */
    public function getPlaintextBody()
    {
        return $this->plaintext_body;
    }

    /**
     * get email attachments
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * get ses profile
     */
    public function getProfile()
    {
        return $this->profile;
    }

    /**
     * get ses version
     */
    public function getVersion()
    {
        return $this->version;
    }

    /**
     * get ses region
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
        
    }

    /**
     * send email use aws ses
     *
     * @param
     * @return Bool
     */
    public function sendEmail()
    {
        /*
        $sender_email = 'sender@example.com';
        $recipient_emails = ['recipient_1@example.com', 'recipient_2@example.com'];
        $configuration_set = 'ConfigSet';
        $subject = 'Test Email';
        $plaintext_body = 'The email was sent with Amazon SES using the AWS SDK for PHP.';
        $html_body = '<h1>Amazon Simple Email Service Test Email</h1>';
        */

        $char_set = 'UTF-8';

        try {
            $SesClient = new SesClient([
                'profile' => $this->profile,
                'version' => $this->version,
                'region' => $this->region
            ]);
            $result = $SesClient->sendEmail([
                'Destination' => [
                    'ToAddresses' => $this->recipient_emails,
                    'CcAddresses' => $this->cc,
                    'BccAddresses' => $this->bcc,
                ],
                'ReplyToAddresses' => [$this->sender_email],
                'Source' => $this->sender_email,
                'Message' => [
                    'Body' => [
                        'Html' => [
                            'Charset' => $char_set,
                            'Data' => $this->html_body,
                        ],
                        /*
                        'Text' => [
                            'Charset' => $char_set,
                            'Data' => $plaintext_body,
                        ],
                        */
                    ],
                    'Subject' => [
                        'Charset' => $char_set,
                        'Data' => $this->subject,
                    ],
                ],
                'Attachments' => !empty($this->attachments) ? $this->createAttachments() : "",

                // And if you aren't using a configuration set, comment or delete the
                // following line
                //'ConfigurationSetName' => $configuration_set,
            ]);
            $messageId = "";
            if (!empty($result['MessageId'])) {
                $messageId = $result['MessageId'];
            }
        } catch (AwsException $e) {
            throw new Exception ($e->getMessage());
        }

        return $messageId;
    }

    /**
     * get send quota use aws ses
     *
     * @return int
     */
    public function checkSendQuota()
    {
        try {
            $SesClient = new SesClient([
                'profile' => $this->profile,
                'version' => $this->version,
                'region' => $this->region
            ]);
            $result = $SesClient->getSendQuota([]);
            $send_limit = $result["Max24HourSend"];
            $sent = $result["SentLast24Hours"];
            $available = $send_limit - $sent;
            //print("<p>You can send " . $available . " more messages in the next 24 hours.</p>");
            //var_dump($result);
        } catch (AwsException $e) {
            throw new Exception ($e->getMessage());
        }

        return $available;
    }

    /**
     * create attachments from array
     *
     * @return Array
     */
    public function createAttachments()
    {
        $_attachment = [];
        $_attachments = [];
        foreach($this->getAttachments as $attachment) {
            if (!file_exists($attachment)) {
                throw new Exception ("file is not exist!");
            }
            if (!is_file($attachment)) {
                throw new Exception ("it is not file!");
            }
            $file_path_array = explode("/", $attachment);
            $file = end($file_path_array);
            $_attachment['Filename'] = $file;
            $_attachment['Content'] = base64_encode(file_get_contents($file));
            $_attachment['ContentType'] = mime_content_type($file);
            array_push($_attachments, $_attachment);
        }

        return $_attachments;
    }

}
