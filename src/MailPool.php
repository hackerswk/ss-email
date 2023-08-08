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
     * set mail pool
     * 
     * @param $subject
     * @param $body
     * @param $to
     * @param $from
     * @param $cc
     * @param $bcc
     * @param $attachments
     * @return Bool
     */
    public function setMailPool($subject, $body, array $_to, $_from, array $cc = null, array $bcc = null, array $attachments = null)
    {
        try {
            if (!isset($subject) && empty($subject)) {
                throw new Exception("subject is empty!");
            }

            if (!isset($body) && empty($body)) {
                throw new Exception("body is empty!");
            }

            if (!isset($_to) && empty($_to)) {
                throw new Exception("email to is empty!");
            }

            if (!isset($_from) && empty($_from)) {
                throw new Exception("email from is empty!");
            }

            $sql = 'INSERT INTO emailing_pool ';
            $sql .= 'SET _from = :_from, _to = :_to, cc = :cc, bcc = :bcc, ';
            $sql .= 'subject =:subject, body = :body, attachments = :attachments';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':_from' => $_from,
                ':_to' => json_encode($_to),
                ':cc' => isset($cc) ? json_encode($cc) : '',
                ':bcc' => isset($bcc) ? json_encode($bcc) : '',
                ':subject' => $subject,
                ':body' => $body,
                ':attachments' => isset($attachments) ? json_encode($attachments) : ''
            ]);

            if ($query->rowCount() === 0 || !$this->database->lastInsertId()) {
                return false;
            }

        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return true;
    }

    /**
     * get mail pool
     * 
     * @return Array
     */
    public function getMailPool()
    {
        $returnData = new \stdClass();
        $sendBatches = [];
        try {
            // 檢查每日寄送限制是否超標 (24000 封/天)
            $sql = 'SELECT COUNT(id) AS batch FROM emailing_pool WHERE sending_time = :date ';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':date' => date('Y-m-d'),
            ]);
            $b = $query->fetch(\PDO::FETCH_ASSOC);
            $quota = $b['batch'] * 50;
            if ($quota < 24000) {
                // 若寄送限制未超標, 每次最多回傳50個待處理批次
                $sql = 'SELECT * FROM emailing_pool ';
                $sql .= 'WHERE id > :id AND status IS NULL LIMIT 50';
                $query = $this->database->prepare($sql);
                $query->execute([
                    ':id' => 0,
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
        $bcc_send_num = intval(ceil(count($recipients) / 50));

        $bcc_array = [];
        $bcc_arrays = [];
        $star = 0;
        $end = 50;
        for ($i=0; $i < $bcc_send_num; $i++) { 
            $recipients_array = array_slice($recipients, $star, $end);
            $bcc_array['to'] = [$recipients_array[0]];
            $bcc_array['bcc'] = array_slice($recipients_array, 1);
            array_push($bcc_arrays, $bcc_array);
            $star += 50;
            $end += 50;
        }

        return $bcc_arrays;
    }

}