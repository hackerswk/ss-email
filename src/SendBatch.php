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
                echo 'no batch!';
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
                    if ($MailBlock->getBlockEmail($val2)) {
                        echo $val2 . ' is block! \n';
                        continue;
                    }
                }

                $SendMail->setRecipient($val['_to']);
                if (!empty($val['cc'])) $SendMail->setCc($val['cc']);
                if (!empty($val['bcc'])) $SendMail->setBcc($val['bcc']);
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
                    echo 'update mail pool failure!';
                    return false;
                }
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
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
}