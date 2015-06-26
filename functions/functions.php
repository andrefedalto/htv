<?php
/**
 * Created by PhpStorm.
 * User: André
 * Date: 01/04/2015
 * Time: 13:57
 */

function buildOutput($data, $debug = false)
{
    $log = Log::getInstance();
    $db = MysqliDb::getInstance();

    $output = array();

    if ($log->countErrors() > 0)
        $errors = $log->getErrors();

    $output = $data;

    if (isset($errors) && sizeof($errors) > 0)
        $output['_ERROR_'] = $errors;

    if ($debug == 'true')
        $output['_DEBUG_'] = $log->getLogs();



    echo json_encode($output, JSON_PRETTY_PRINT);
}

