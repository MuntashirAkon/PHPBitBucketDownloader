<?php

/**
 * Created by PhpStorm.
 * User: muntashir
 * Date: 1/25/17
 * Time: 2:52 PM
 *
 * @version 1.0.0
 * @author Muntashir Al-Islam <muntashir.islam96@gmail.com>
 * @copyright 2016 (c) All rights reserved
 * @license MIT license
 */

/**
 * Class BitBucketDownloader
 *
 * NOTE: This Class uses cURL to support PHP 5.0.0 and later
 *
 */
class BitBucketDownloader
{
    /** @var \DOMDocument */
    protected $HTMLFile;
    protected $url;

    /**
     * BitBucketDownloader constructor.
     *
     * @param string $user BitBucket username
     * @param string $repo Repository name
     */
    public function __construct($user, $repo){
        date_default_timezone_set("UTC");
        $this->url = "https://bitbucket.org/{$user}/{$repo}/downloads/";
        $this->load();
    }

    /**
     * Downloads the requested file from BitBucket
     *
     * @param string $search Should contain the filename syntax (ie. Demo-1209 may have Demo)
     * @param string $target Path to save the downloaded file
     * @return array|bool False on failure and an array containing file location and filename
     */
    public function getFile($search, $target = '/tmp'){
        $files = $this->loadFiles();
        $latest_time  = null;
        $desired_url = null;
        if($files == null) return false;
        foreach($files as $file){
            if(preg_match("/^(.*)({$search})(.*)$/", $file["name"])){
                if($latest_time < $file['time']){
                    $latest_time = $file['time'];
                    $desired_url = $file['name'];
                }
            }
        }
        if($desired_url != null){
            $target = $target . '/' . basename($desired_url);
            $file   = $desired_url;
            return $this->download($file, $target) ? ["file" => $target, "filename" => $desired_url] : false;
        }
        return false;
    }

    /**
     * Downloads file using cURL
     *
     * NOTE: this is done to support PHP >= 5.0.0
     *
     * @param string $file File url
     * @param string $to   File saving location (along with the filename)
     * @return bool True on success and False on failure
     */
    protected function download($file, $to){
        set_time_limit(0);
        $curl = curl_init();
        $to = fopen($to, "w+");
        $options = [
            CURLOPT_FILE        => $to,
            //CURLOPT_TIMEOUT     =>  28800, // set to 8 hours to stop being timeout on big files
            CURLOPT_URL         => $file,
            CURLOPT_FOLLOWLOCATION => true
        ];
        curl_setopt_array($curl, $options);
        $result = curl_exec($curl);
        fclose($to);
        curl_close($curl);
        return ($result !== false) ? true : false;
    }

    /**
     * Generates a available file list
     *
     * @return array|null A list of files (files[][name] and files[][time]) or null
     */
    protected function loadFiles(){
        $files = array();
        $table = $this->HTMLFile->getElementById("uploaded-files");
        if($table == null OR !$table->hasChildNodes()) return null;
        /** @var \DOMElement $innerTable */
        foreach($table->childNodes as $innerTable){
            if($innerTable->nodeName == "tbody"){
                if(!$innerTable->hasChildNodes()) return null;
                $table = $innerTable;
                break;
            }

        }
        $i = 0;
        /** @var \DOMElement $row */
        foreach($table->childNodes as $row){
            if(get_class($row) == "DOMElement" && $row->getAttribute("class") == "iterable-item" && $row->hasChildNodes()){
                /** @var \DOMElement $col */
                foreach($row->childNodes as $col){
                    if(get_class($col) == "DOMElement" && $col->hasChildNodes()){
                        if($col->getAttribute("class") == "name"){
                            /** @var \DOMElement $item */
                            foreach($col->childNodes as $item){
                                if(get_class($item) == "DOMElement"){
                                    $url = "https://bitbucket.org".$item->getAttribute("href");
                                    $files[$i]["name"] = str_replace($this->url, "", $url);
                                }
                            }
                        }
                        if($col->getAttribute("class") == "date"){
                            /** @var \DOMElement $item */
                            foreach($col->childNodes as $item){
                                if($item->hasChildNodes()){
                                    /** @noinspection PhpForeachNestedOuterKeyValueVariablesConflictInspection */
                                    /** @var \DOMElement $item */
                                    foreach($item->childNodes as $item){
                                        if(get_class($item) == "DOMElement") $files[$i]["time"] = (int)strtotime($item->getAttribute("datetime"));
                                    }
                                }
                            }
                        }
                    }
                }
                $i++;
            }
        }
        return $files;
    }

    /**
     * Downloads and loads the HTML file from BitBucket
     *
     * @return bool
     */
    protected function load(){
        $this->HTMLFile = new \DOMDocument("1.0", "UTF-8");
        $this->HTMLFile->preserveWhiteSpace = false;
        $this->HTMLFile->formatOutput       = true;
        return @$this->HTMLFile->loadHTMLFile($this->url);
//        return @$this->HTMLFile->loadHTMLFile(__DIR__."/Examples/ok"); // For testing purpose
    }
}