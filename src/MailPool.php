<?php
/**
 * Mail pool class
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\SsEmail;

use \Exception as Exception;

class MailPool
{
    /**
     * database
     *
     * @var object
     */
    private $database;

    /**
     * initialize
     */
    public function __construct($db = null)
    {
        $this->database = $db;
    }

    /**
     * Inserts a new email record into the emailing pool.
     *
     * @param string $subject The subject of the email.
     * @param string $body The body of the email.
     * @param array $to The recipient email addresses.
     * @param string $from The sender's email address.
     * @param array|null $cc Optional CC email addresses.
     * @param array|null $bcc Optional BCC email addresses.
     * @param array|null $attachments Optional email attachments.
     * @param int $emailType The type of email (default is 1, normal).
     *
     * @return int|bool The ID of the newly inserted record, or false on failure.
     *
     * @throws Exception If any required parameters are missing or if the database operation fails.
     */
    public function setMailPool(
        string $subject,
        string $body,
        array $_to,
        string $_from,
        array $cc = null,
        array $bcc = null,
        array $attachments = null,
        int $emailType = 1
    ) {
        try {
            // Validate inputs
            if (empty($subject)) {
                throw new Exception("Subject is empty!");
            }

            if (empty($body)) {
                throw new Exception("Body is empty!");
            }

            if (empty($_to)) {
                throw new Exception("Email 'to' is empty!");
            }

            if (empty($_from)) {
                throw new Exception("Email 'from' is empty!");
            }

            // Prepare SQL statement
            $sql = 'INSERT INTO emailing_pool ';
            $sql .= '(_from, _to, cc, bcc, subject, body, attachments, email_type) ';
            $sql .= 'VALUES (:_from, :_to, :cc, :bcc, :subject, :body, :attachments, :email_type)';

            // Prepare and execute the query
            $query = $this->database->prepare($sql);
            $query->execute([
                ':_from' => $_from,
                ':_to' => json_encode($_to),
                ':cc' => isset($cc) ? json_encode($cc) : '',
                ':bcc' => isset($bcc) ? json_encode($bcc) : '',
                ':subject' => $subject,
                ':body' => $body,
                ':attachments' => isset($attachments) ? json_encode($attachments) : '',
                ':email_type' => $emailType, // Include the email type in the query
            ]);

            // Check if insert was successful
            if ($query->rowCount() === 0 || !$this->database->lastInsertId()) {
                return false;
            }

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return $this->database->lastInsertId();
    }

    /**
     * Retrieves email batches from the pool that are scheduled to be sent.
     * Checks the daily sending limit and returns a maximum of 50 batches that are pending.
     *
     * @param int $emailType The type of email to retrieve (1 for normal, 2 for blackcard edm). Default is 1.
     *
     * @return \stdClass An object containing the status of the operation and the list of email batches.
     *                  - status: 'success' or 'failure'
     *                  - batch: Array of email batches (if successful)
     *                  - message: Error message (if failure)
     *
     * @throws Exception If an error occurs during database operations.
     */
    public function getMailPool(int $emailType = 1)
    {
        $returnData = new \stdClass();
        $sendBatches = [];

        try {
            // Check the daily sending limit (24,000 emails/day)
            $sql = 'SELECT COUNT(id) AS batch FROM emailing_pool WHERE sending_time LIKE :date AND email_type = :emailType';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':date' => date('Y-m-d') . '%',
                ':emailType' => $emailType,
            ]);
            $b = $query->fetch(\PDO::FETCH_ASSOC);
            $quota = $b['batch'] * 50;
            if ($quota < 24000) {
                // If sending limit is not exceeded, return up to 50 pending batches
                $sql = 'SELECT * FROM emailing_pool ';
                $sql .= 'WHERE id > :id AND status IS NULL AND sending_time LIKE :date AND email_type = :emailType LIMIT 0, 50';
                $query = $this->database->prepare($sql);
                $query->execute([
                    ':id' => 0,
                    ':date' => date('Y-m-d') . '%',
                    ':emailType' => $emailType,
                ]);
                $rows = $query->fetchAll(\PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $row['_to'] = json_decode($row['_to'], JSON_PRETTY_PRINT);
                    $row['cc'] = json_decode($row['cc'], JSON_PRETTY_PRINT);
                    $row['bcc'] = json_decode($row['bcc'], JSON_PRETTY_PRINT);
                    $row['attachments'] = json_decode($row['attachments'], JSON_PRETTY_PRINT);
                    array_push($sendBatches, $row);
                }
            }
            $returnData->status = 'success';
            $returnData->batch = $sendBatches;
        } catch (Exception $e) {
            $returnData->status = 'failure';
            $returnData->message = $e->getMessage();
        }

        return $returnData;
    }

    /**
     * update mail pool
     *
     * @param $id
     * @param $sent_time
     * @param $status
     * @param $aws_task_id
     * @return Bool
     */
    public function updateMailPool($id, $sent_time, $status, $aws_task_id)
    {
        try {
            if (!isset($id) && empty($id)) {
                throw new Exception("id is empty!");
            }

            if (!isset($sent_time) && empty($sent_time)) {
                throw new Exception("sent time is empty!");
            }

            if (!isset($status) && empty($status)) {
                throw new Exception("status is empty!");
            }

            if (!isset($aws_task_id) && empty($aws_task_id)) {
                throw new Exception("aws task id is empty!");
            }

            $sql = 'UPDATE emailing_pool SET status = :status, sent_time = :sent_time, aws_task_id = :aws_task_id ';
            $sql .= 'WHERE id = :id';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':id' => $id,
                ':status' => $status,
                ':sent_time' => $sent_time,
                ':aws_task_id' => $aws_task_id,
            ]);
            if ($query->rowCount() == 0 && $query->errorCode() != '00000') {
                return false;
            }

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return true;
    }

    /**
     * create recipient and bcc from recipients
     *
     * @return Array
     */
    public function createToAndBcc($recipients)
    {
        // 每個批次的最大收件人數（包括 `to` 和 `bcc`）
        $maxRecipientsPerBatch = 50;
        // 每個 `bcc` 批次的最大數量
        $maxBccPerBatch = 45;

        // 計算批次數量
        $totalBatches = ceil(count($recipients) / $maxRecipientsPerBatch);

        $bcc_arrays = [];
        $start = 0;

        for ($i = 0; $i < $totalBatches; $i++) {
            // 定義每批次的收件人範圍
            $recipientsArray = array_slice($recipients, $start, $maxRecipientsPerBatch);

            // 設置 `to` 為批次中的第一個收件人
            $to = [array_shift($recipientsArray)];

            // 剩餘的收件人設為 `bcc`
            $bcc = $recipientsArray;

            // 構建批次數據
            $bcc_arrays[] = [
                'to' => $to,
                'bcc' => $bcc,
            ];

            // 更新下一批次的起始位置
            $start += $maxRecipientsPerBatch;
        }

        return $bcc_arrays;
    }

    /**
     * delete mail pool for sent time < param day
     *
     * @param $day
     * @return bool
     */
    public function delMailPool($day)
    {
        try {
            $del_time = date("Y-m-d", strtotime("- $day day", strtotime(date("Y-m-d"))));
            $sql = 'DELETE FROM emailing_pool ';
            $sql .= 'WHERE  sent_time < :del_time';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':del_time' => $del_time,
            ]);
            if ($query->rowCount() == 0 && $query->errorCode() != '00000') {
                return false;
            }

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return true;
    }

}
