<?php
/**
 * Send mail class
 *
 * @author      Stanley Sie <swookon@gmail.com>
 * @access      public
 * @version     Release: 1.0
 */

namespace Stanleysie\SsEmail;

use \Exception as Exception;

class SendMail
{
    /**
     * initialize
     */
    public function __construct()
    {
    
    }

    /**
     * 
     * 
     * @param 
     * @return Bool
     */
    public function setMailBlock()
    {
        try {
                
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }

        return true;
    }

    /**
     * 
     * 
     * @param 
     * @return Bool
     */
    public function getBlockEmail()
    {
        try {
            
        } catch (Exception $e) {
            throw new Exception($e->getMessage());
        }
        return false;
    }
    
}