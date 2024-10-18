<?php
/**
 * Send batch class
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\SsEmail;

use Stanleysie\SsEmail\MailBlock;
use Stanleysie\SsEmail\MailPool;
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
    public function send($profile, $version, $region, $emailType = 1)
    {
        try {
            $SendMail = new SendMail();
            $MailPool = new MailPool($this->database);
            $MailBlock = new MailBlock($this->database);
            $returnData = $MailPool->getMailPool($emailType);
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
                        //echo $val2 . ' is block!' . PHP_EOL;
                        if (count($val['_to']) == 1) {
                            $status = 'failure';
                            $messageId = 'F-000000';
                            if (!$MailPool->updateMailPool($val['id'], date("Y-m-d H:i:s", time()), $status, $messageId)) {
                                echo 'update mail pool failure!' . PHP_EOL;
                                //return false;
                            }
                        }

                        if (($key = array_search($val2, $val['_to'])) !== false) {
                            unset($val['_to'][$key]);
                        }
                        continue;
                    }

                    if (!$this->filterEmail($val2)) {
                        //echo $val2 . ' is invalid!' . PHP_EOL;
                        if (!$MailBlock->getBlockEmail($val2)) {
                            $MailBlock->setMailBlock($val2, "invalid");
                        }

                        if (count($val['_to']) == 1) {
                            $status = 'failure';
                            $messageId = 'F-000000';
                            if (!$MailPool->updateMailPool($val['id'], date("Y-m-d H:i:s", time()), $status, $messageId)) {
                                echo 'update mail pool failure!' . PHP_EOL;
                                //return false;
                            }
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
                //echo $val['_to'][0] . PHP_EOL;

                $SendMail->setRecipient($val['_to']);
                if (!empty($val['cc'])) {
                    $SendMail->setCc($val['cc']);
                }

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
                if (!empty($val['attachments'])) {
                    $SendMail->setAttachments($val['attachments']);
                }

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
                if (!empty($val['bcc'])) {
                    sleep(3); // 延遲 2 秒
                }
            }
        } catch (Exception $e) {
            //throw new Exception($e->getMessage());
            echo $e->getMessage();
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
}
