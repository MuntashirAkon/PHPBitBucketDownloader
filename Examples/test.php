<?php
/**
 * Created by PhpStorm.
 * User: muntashir
 * Date: 1/25/17
 * Time: 3:02 PM
 */

require_once __DIR__."/../BitBucketDownloader.php";

$BBD = new BitBucketDownloader("RehabMan", "os-x-maciasl-patchmatic");

var_dump($BBD->getFile("patchmatic"));
