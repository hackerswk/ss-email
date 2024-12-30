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
    /**
     * Sends emails using AWS SES based on the mail pool data.
     *
     * This method retrieves a batch of emails from the mail pool and processes them.
     * It ensures that emails are sent only if they meet validation checks for the recipient,
     * and updates the status of the email batch accordingly.
     *
     * @param string $profile AWS SES client profile name.
     * @param string $version AWS SES client version.
     * @param string $region AWS SES client region.
     * @param int $emailType The type of email to retrieve from the mail pool. Default is 1.
     *
     * @return bool Returns `true` if the process completes without critical errors, otherwise `false`.
     *
     * @throws \Exception If unexpected errors occur during email processing.
     */
    public function send($profile, $version, $region, $emailType = 1)
    {
        try {
            $SendMail = new SendMail();
            $MailPool = new MailPool($this->database);
            $MailBlock = new MailBlock($this->database);
            $returnData = $MailPool->getMailPool($emailType);

            if ($returnData->status != 'success' || count($returnData->batch) == 0) {
                echo 'No batch to process!';
                return false;
            }

            $SendMail->setProfile($profile);
            $SendMail->setVersion($version);
            $SendMail->setRegion($region);

            foreach ($returnData->batch as $val) {
                if ($val['status'] === 'processing') {
                    echo 'Batch ID already in processing state: ' . $val['id'] . PHP_EOL;
                    continue;
                }

                $MailPool->updateMailStatus($val['id'], 'processing');
                $SendMail->setSender($val['_from']);

                foreach ($val['_to'] as $recipient) {
                    if ($MailBlock->getBlockEmail($recipient)) {
                        echo "Blocked email: $recipient" . PHP_EOL;
                        $this->handleFailedRecipient($val, $recipient, $MailPool);
                        continue;
                    }

                    if (!$this->filterEmail($recipient)) {
                        echo "Invalid email: $recipient" . PHP_EOL;
                        $MailBlock->setMailBlock($recipient, "invalid");
                        $this->handleFailedRecipient($val, $recipient, $MailPool);
                        continue;
                    }
                }

                if (count($val['_to']) === 0) {
                    echo 'No valid recipients for batch ID: ' . $val['id'] . PHP_EOL;
                    $MailPool->updateMailStatus($val['id'], 'failure', 'F-000000');
                    continue;
                }

                $SendMail->setRecipient($val['_to']);
                if (!empty($val['cc'])) {
                    $SendMail->setCc($val['cc']);
                }

                if (!empty($val['bcc'])) {
                    $filteredBcc = array_filter($val['bcc'], function ($email) use ($MailBlock) {
                        return $this->filterEmail($email) && !$MailBlock->getBlockEmail($email);
                    });

                    if (!empty($filteredBcc)) {
                        $SendMail->setBcc($filteredBcc);
                    } else {
                        $SendMail->clearBcc(); // 自定義方法，確保清除之前的 BCC
                    }
                } else {
                    $SendMail->clearBcc(); // 清除上一批次的 BCC 設置
                }

                $SendMail->setSubject($val['subject']);
                $SendMail->setHtmlBody($val['body']);
                if (!empty($val['attachments'])) {
                    $SendMail->setAttachments($val['attachments']);
                }

                try {
                    $messageId = $SendMail->sendEmail();
                    $status = !empty($messageId) ? 'success' : 'failure';
                    $MailPool->updateMailStatus($val['id'], $status, $messageId ?? 'F-000000');
                } catch (\Aws\Exception\AwsException $e) {
                    $this->logError("AWS Exception during email sending for batch ID: {$val['id']}. Error: " . $e->getMessage());
                    $MailPool->updateMailStatus($val['id'], 'failure', 'AWS-ERROR');
                    continue;
                } catch (\Exception $e) {
                    $this->logError("General Exception during email sending for batch ID: {$val['id']}. Error: " . $e->getMessage());
                    $MailPool->updateMailStatus($val['id'], 'failure', 'GEN-ERROR');
                    continue;
                }

                if (!empty($val['bcc'])) {
                    sleep(3); // Delay 3 seconds for BCC handling
                }
            }
        } catch (\Exception $e) {
            $this->logError("Unexpected Exception in email sending process: " . $e->getMessage());
            return false;
        }

        echo 'Send email process completed.';
        return true;
    }

    /**
     * Log error messages dynamically based on the framework.
     *
     * @param string $message The error message to log.
     * @return void
     */
    private function logError(string $message): void
    {
        if (function_exists('app')) {
            // Assume it's a Laravel project
            echo $message . PHP_EOL;
            \Log::error($message);
        } else {
            // Fallback to native PHP error log
            echo $message . PHP_EOL;
            error_log($message);
        }
    }

    /**
     * Clears expired email batches from the pool.
     *
     * This function removes email batches that have been in the pool for the specified number of days.
     *
     * @param int $day The number of days to determine expiration.
     * @return bool Returns true if the operation is successful, false otherwise.
     * @throws \Exception Throws an exception if there is an error during the operation.
     */
    public function clearBatch(int $day): bool
    {
        try {
            $MailPool = new MailPool($this->database);
            if (!$MailPool->delMailPool($day)) {
                echo 'Delete expired email pool failed!';
                return false;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        echo 'Clear expired email pool done.';
        return true;
    }

    /**
     * Validates an email address.
     *
     * This function checks whether the provided email address is valid according to standard email format rules.
     *
     * @param string $email The email address to validate.
     * @return bool Returns true if the email is valid, false otherwise.
     * @throws \Exception Throws an exception if there is an error during validation.
     */
    public function filterEmail(string $email): bool
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
     * Handles invalid or blocked email recipients in the batch.
     *
     * This function updates the batch's status to "failure" if the recipient list contains only the invalid email
     * and removes the invalid email from the recipient list.
     *
     * @param array $batch The email batch data, including recipient information.
     * @param string $email The invalid or blocked email address to handle.
     * @param object $MailPool The MailPool instance used to update the mail status.
     *
     * @return void
     */
    private function handleFailedRecipient(array $batch, string $email, object $MailPool): void
    {
        if (count($batch['_to']) === 1) {
            $MailPool->updateMailStatus($batch['id'], 'failure', 'F-000000');
        }

        if (($key = array_search($email, $batch['_to'])) !== false) {
            unset($batch['_to'][$key]);
        }
    }
}
