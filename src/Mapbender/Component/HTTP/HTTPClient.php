<?php

namespace Mapbender\Component\HTTP;

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
    protected $container = "";
    protected $ch = null;
    

    public function __construct($container = null){
        $this->ch = curl_init();

        $proxyConf = null;
        if($this->container){
            try {
                $proxyConf = $this->container->getParameter('proxy');
            }catch(\InvalidArgumentException $E){
                // thrown when the parameter is not set
                // maybe some logging ?
                $proxyConf = array();
                $this->container->get('logging')->debug('Not using Proxy Configuuration');
            }
            if($proxyConf && isset($proxyConf['host']) && $proxyConf['host'] != ""){
                $this->setProxyHost($proxyConf['host']);
                $this->setProxyPort($proxyConf['port']?:null);
                $this->container->get('logging')
                ->debug(sprintf('Making Request via Proxy: %s:%s',
                $this->getProxyHost(),
                $this->getProxyPort()));
            }
        }

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

    public function getUsername (){
        return $this->username ;
    }
    
    public function setUsername ($username ){
        $this->username  = $username ;
    }

    public function getPassword (){
        return $this->password ;
    }
    
    public function setPassword ($password ){
        $this->password  = $password ;
    }

    /**
     * Shortcut Method 
    */
    public function open($url,$query = array(),$method='GET', $data=''){
        curl_setopt($this->ch,CURLOPT_URL,$url);
        curl_setopt($this->ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($this->ch, CURLINFO_HEADER_OUT, true);
        
        if($this->getUsername()){
            curl_setopt($this->ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
            curl_setopt($this->ch, CURLOPT_USERPWD, $this->getUsername().":".$this->getPassword());
        }
        if($this->getProxyHost()){
            curl_setopt($this->ch, CURLOPT_PROXY, $this->getProxyHost());
        }
        if($this->getProxyPort()){
            curl_setopt($this->ch, CURLOPT_PROXYPORT, $this->getProxyport());
        }


        $data = curl_exec($this->ch);

        if(($error = curl_error($this->ch)) != ""){
            throw new \Exception("Curl says: '$error'");
        }
        $statusCode = curl_getInfo($this->ch,CURLINFO_HTTP_CODE);

        $result = new HTTPResult();
        $result->setData($data);
        $result->setStatusCode($statusCode);
        return $result;
    }

  static function parseQueryString($str) {
    $op = array();
    $pairs = explode("&", $str);
    foreach ($pairs as $pair) {
        $arr = explode("=",$pair);
        $k = isset($arr[0])? $arr[0]:null;
        $v = isset($arr[1])? $arr[1]:null;
        if($k !== null){
          $op[$k] = $v;
        }
    }
    return $op;
  } 
  
  static function buildQueryString($parsedQuery) {
    $result = array();
    foreach($parsedQuery as $key => $value){
      if($key || $value ) {
        $result[] = "$key=$value";
      }
    }
    return implode("&",$result);
  } 

  static function parseUrl($url){
      $defaults = array(
        "scheme"   => "http",
        "host"     => null, 
        "port"     => null,
        "user"     => null,
        "pass"     => null, 
        "path"     => "/",  
        "query"    => null,
        "fragment" => null
      );  

      $parsedUrl = parse_url($url);

      $mergedUrl = array_merge($defaults,$parsedUrl);
    return $mergedUrl;
  }
  static function buildUrl(array $parsedUrl){
      $defaults = array(
        "scheme"   => "http",
        "host"     => null, 
        "port"     => null,
        "user"     => null,
        "pass"     => null, 
        "path"     => "/",  
        "query"    => null,
        "fragment" => null
      );  

      $mergedUrl = array_merge($defaults,$parsedUrl);

      $result = $mergedUrl['scheme'] ."://";
      
      $authString = $mergedUrl['user'] ;
      $authString .= $mergedUrl['pass'] ? ":" .$mergedUrl['pass'] : ""; 
      $authString .= $authString ? "@":"";
      $result .= $authString;

      $result .= $mergedUrl['host'];
      $result .= $mergedUrl['port'] ? ':'.$mergedUrl['port']:"";
      $result .= $mergedUrl['path'];
      $result .= $mergedUrl['query'] ? '?'.$mergedUrl['query']:"";
      return $result;
   
  }
}
