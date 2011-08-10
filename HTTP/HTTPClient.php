<?php

namespace MB\CoreBundle\HTTP;

class HTTPClient {


    protected $method = "GET";
    protected $headers = array();
    protected $host = "";
    protected $port = "";
    protected $path = "";
    protected $proxyHost = "";
    protected $proxyPort = "";
    protected $username = "";
    protected $password = "";

    protected $ch = null;
    

    public function __construct(){
        $this->ch = curl_init();
    }
    public function __destruct(){
        $this->ch = curl_close($this->ch);
    }


    public function setProxyHost($host){
        $this->proxyHost = $host;
    }
    public function setProxyPort($port){
        $this->proxyPort = $port;
    }


    /**
     * Shortcut Method 
    */
    public function open($url,$query = array(),$method='GET', $data=''){
        curl_setopt($this->ch,CURLOPT_URL,$url);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLINFO_HEADER_OUT, true);

        $data = curl_exec($this->ch);
        $statusCode = curl_getInfo($this->ch,CURLINFO_HTTP_CODE);

        $result = new HTTPResult();
        $result->setData($data);
        $result->setStatusCode($statusCode);
        return $result;
    }
}
