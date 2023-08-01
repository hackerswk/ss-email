<?php
/**
 * Mail block class
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\SsEmail;

use \Exception as Exception;

class MailBlock
{
    /**
     * database
     *
     * @var object
     */
    private $database;

    /**
     * set mail block
     * 
     * @param $email
     * @return Bool
     */
    public function setMailBlock($email, $block_reason)
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
                ':block_reason' => $block_reason
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
     * get block eamil
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