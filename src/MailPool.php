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
     * email from
     *
     * @var String
     */
    private $from;

    /**
     * email to
     *
     * @var String 
     */
    private $to;

    /**
     * email cc
     *
     * @var Array
     */
    private $cc;
    
    /**
     * email bcc
     *
     * @var Array
     */
    private $bcc;

    /**
     * email subject
     *
     * @var String 
     */
    private $subject;

    /**
     * email body
     *
     * @var String
     */
    private $body;

    /**
     * email attachments
     *
     * @var Array
     */
    private $attachments;

    /**
     * aws ses response status
     *
     * @var String
     */
    private $status;

    /**
     * aws task id
     *
     * @var String
     */
    private $aws_task_id;

    /**
     * initialize
     */
    public function __construct($database)
    {
        $this->database = $database; 
    }

    /**
     * set email from
     * 
     * @param $from
     */
    public function setFrom($from)
    {
        if (empty($from)) {
            throw new Exception ("email from is empty!");
        }
        $this->from = $from;
    }

    /**
     * set email to
     * 
     * @param $to
     */
    public function setTo($to)
    {
        if (empty($to)) {
            throw new Exception ("email to is empty!");
        }
        $this->to = $to;
    }

    /**
     * set email cc
     * 
     * @param $cc
     */
    public function setCc($cc)
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
    public function setBcc($bcc)
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
     * set email body
     * 
     * @param $body
     */
    public function setBody($body)
    {
        if (empty($body)) {
            throw new Exception ("body is empty!");
        }
        $this->body = $body;
    }

    /**
     * set mail attachments
     * 
     * @param $attachments
     */
    public function setAttachments($attachments)
    {
        if (empty($attachments)) {
            throw new Exception ("attachments is empty!");
        }   
        $this->attachments = $attachments;
    }

    /**
     * set status
     * 
     * @param $status
     */
    public function setStatus($status)
    {
        if (empty($status)) {
            throw new Exception ("status is empty!");
        }   
        $this->status = $status;
    }

    /**
     * set aws task id
     * 
     * @param $aws_task_id
     */
    public function setAwsTaskId($aws_task_id)
    {
        if (empty($aws_task_id)) {
            throw new Exception ("aws task id is empty!");
        }   
        $this->aws_task_id = $aws_task_id;
    }

    /**
     * get email to
     */
    public function getTo()
    {
        return $this->to;
    }

    /**
     * get email from
     */
    public function getFrom()
    {
        return $this->from;
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
     * get email body
     */
    public function getBody()
    {
        return $this->body;
    }

    /**
     * get email attachments
     */
    public function getAttachments()
    {
        return $this->attachments;
    }

    /**
     * get status
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * get aws task id
     */
    public function getAwsTaskId()
    {
        return $this->aws_task_id;
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
    public function setMailPool($subject, $body, array $to, $from, array $cc = null, array $bcc = null, array $attachments = null)
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
        $returnData = new stdClass();
        $sendBatches = [];
        try {
            // 檢查每日寄送限制是否超標 (24000 封/天)
            $sql = 'SELECT COUNT(id) AS batch FROM emailing_pool WHERE sending_time = :date ';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':date' => date('Y-m-d'),
            ]);
            $b = $query->fetch(PDO::FETCH_ASSOC);
            $quota = $b['batch'] * 30;
            if ($quota < 24000) {
                // 若寄送限制未超標, 每次最多回傳50個待處理批次
                $sql = 'SELECT * FROM emailing_pool ';
                $sql .= 'WHERE id > :id AND status = null LIMIT 50';
                $query = $this->database->prepare($sql);
                $query->execute([
                    ':id' => 0,
                ]);
                $rows = $query->fetchAll(PDO::FETCH_ASSOC);
                foreach ($rows as $row) {
                    $row['_to'] = json_decode($row['_to'], JSON_PRETTY_PRINT);
                    $row['cc'] = json_decode($row['cc'], JSON_PRETTY_PRINT);
                    $row['bcc'] = json_decode($row['bcc'], JSON_PRETTY_PRINT);
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
     * set block mail
     * 
     * @param $email
     * @param $block_reason
     * @return Bool
     */
    public function setBlockMail($email, $block_reason)
    {
        try {
            if (!isset($email) && empty($email)) {
                throw new Exception("email is empty!");
            }

            if (!isset($block_reason) && empty($block_reason)) {
                throw new Exception("block reason is empty!");
            }

            $sql = 'INSERT INTO email_block ';
            $sql .= 'SET email = :email, block_reason = :block_reason';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':email' => $email,
                ':block_reason' => $block_reason,
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
     * get block mail
     * 
     * @param $email
     * @return Bool
     */
    public function getBlockEmail($email)
    {
        try {
            $sql = 'SELECT * FROM email_block WHERE email = :email';
            $query = $this->database->prepare($sql);
            $query->execute([
                ':email' => $email,
            ]);

            if ($query->rowCount() > 0) {
                return true;
            }
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }
    
}