<?php
/**
 * Created by PhpStorm.
 * User: André
 * Date: 01/04/2015
 * Time: 14:38
 */

class Log
{
    private $_logs = array();


    public function pushLog($class, $code, $description, $error = false)
    {
        $log = array(
            'class'         => get_class($class),
            'code'          => $code,
            'description'   => $description,
            'error'         => $error
        );

        array_push($this->_logs, $log);
    }



    public function getLogs()
    {
        return $this->_logs;
    }


    public function countErrors()
    {
        $errors = 0;

        foreach($this->_logs as $log)
            if ($log['error'])
                $errors++;

        return $errors;
    }


    public function setErrorCode($code)
    {
        if ($this->countErrors() > 0)
        {
            for ($i = sizeof($this->_logs) - 1; $i >= 0; $i--)
            {
                if ($this->_logs[$i]['error'])
                {
                    $this->_logs[$i]['code'] = $code;
                    break;
                }
            }
        }
    }

    public function getLastCode()
    {
        if (sizeof($this->_logs) > 0)
            return $this->_logs[sizeof($this->_logs) - 1]['code'];
        else
            return 0;
    }


    public function getErrors()
    {
        if ($this->countErrors() > 0)
        {
            $errors = array();

            foreach($this->_logs as $log)
                if ($log['error'])
                    array_push($errors, $log);

            return $errors;
        }
        else
        {
            return null;
        }
    }


    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }

        return $instance;
    }

    /**
     * Protected constructor to prevent creating a new instance of the
     * *Singleton* via the `new` operator from outside of this class.
     */
    protected function __construct()
    {
    }


}