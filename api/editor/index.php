<?php
/**
 * Created by PhpStorm.
 * User: André
 * Date: 10/05/2015
 * Time: 16:52
 */

$path = explode("editor", $_SERVER['PHP_SELF']);
$url = "http://editor.swagger.io/#/?import=http://" . $_SERVER['HTTP_HOST'].$path[0]."htv.json";
header("Location: $url");