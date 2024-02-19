<?php
/**
 * Send batch class
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\SsEmail;

use Stanleysie\SsEmail\MailPool;
use Stanleysie\SsEmail\MailBlock;
use Stanleysie\SsEmail\SendMail;
use \Exception as Exception;

class SendBatch
{
    /**
     * initialize
     */
    public function __construct($db = null)
    {
        $this->database = $db;
    }

    /**
     * send email batch
     * 
     * @return bool
     */
    public function send($profile, $version, $region)
    {
        try {
            $SendMail = new SendMail();
            $MailPool = new MailPool($this->database);
            $MailBlock = new MailBlock($this->database);
            $returnData = $MailPool->getMailPool();
            if ($returnData->status != 'success' || count($returnData->batch) == 0) {
                //echo 'no batch!';
                $err = 'no batch';
                $this->setLog($err);
                return false;
            }
            /*
            $profile = 'default';
            $version = '2010-12-01';
            $region = 'us-west-2';
            */
            $SendMail->setProfile($profile);
            $SendMail->setVersion($version);
            $SendMail->setRegion($region);
            foreach ($returnData->batch as $val) {
                $SendMail->setSender($val['_from']);

                foreach ($val['_to'] as $val2) {

                    $err = '_to: ' . $val['_to'][0];
                    $this->setLog($err);

                    if ($MailBlock->getBlockEmail($val2)) {
                        //echo $val2 . ' is block! \n';
                        if (($key = array_search($val2, $val['_to'])) !== false) {
                            unset($val['_to'][$key]);
                        }
                        continue;
                    }

                    if (!$this->filterEmail($val2)) {
                        if (!$MailBlock->getBlockEmail($val2)) {
                            $MailBlock->setMailBlock($val2, "invalid");
                        }
                        
                        if (($key = array_search($val2, $val['_to'])) !== false) {
                            unset($val['_to'][$key]);
                        }
                        continue;
                    }
                }
                
                if (count($val['_to']) == 0) {
                    continue;
                } 

                $err = 'to: ' . $val['_to'][0];
                $this->setLog($err);

                $SendMail->setRecipient($val['_to']);
                if (!empty($val['cc'])) $SendMail->setCc($val['cc']);
                if (!empty($val['bcc'])) {
                    $bcc_array = [];
                    foreach ($val['bcc'] as $val3) {
                        if (!$this->filterEmail($val3)) {
                            $MailBlock->setMailBlock($val3, "invalid");
                            continue;
                        }
                        array_push($bcc_array, $val3);
                    }
                    $SendMail->setBcc($bcc_array);
                }
                $SendMail->setSubject($val['subject']);
                $SendMail->setHtmlBody($val['body']);
                if (!empty($val['attachments'])) $SendMail->setAttachments($val['attachments']);
                $messageId = $SendMail->sendEmail();
                if (!empty($messageId)) {
                    $status = 'success';
                } else {
                    $status = 'failure';
                }
                if (!$MailPool->updateMailPool($val['id'], date("Y-m-d H:i:s", time()), $status, $messageId)) {
                    //echo 'update mail pool failure!';
                    $err = 'update mail pool failure!';
                    $this->setLog($err);
                    return false;
                }
            }
        } catch (Exception $e) {
            //throw new Exception($e->getMessage());
            $this->setLog($e->getMessage());
            return false; 
        }

        echo 'Send email done.';
        return true;
    }

    /**
     * clear email batch
     *
     * @param $day 
     * @return bool
     */
    public function clearBatch($day)
    {
        try {
            $MailPool = new MailPool($this->database);
            if (!$MailPool->delMailPool($day)) {
                echo 'Delete expire email pool faild!';
                return false;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        echo 'Clear expire email pool done.';
        return true;
    }

    /**
     * filter email
     *
     * @param $email 
     * @return bool
     */
    public function filterEmail($email)
    {
        try {
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                return false;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return true;
    }

    /**
     * Set error log
     *
     * @param $err
     * @return void
     */
    public function setLog($err)
    {
        $myfile = fopen(__DIR__ . '/../../public/upload/ses.txt', "a") or die("Unable to open file!");
        $txt = date("Y-m-d H:i:s") . "--err: " . $err . "\n";
        fwrite($myfile, $txt);
        fclose($myfile);
    }
}