<?php

namespace OwsProxy3\CoreBundle\Component;

/**
 * URL class.
 * 
 * @author A.R.Pour
 * @version 0.1
 */
class Url {
    private $url;
    private $query;
    
    /**
     * Parse the url and return if valid.
     * 
     * @param string $url
     * @return mixed URL
     */
    public function __construct($url) {
        $this->url = parse_url($url);
        $this->query = array();
        
        if(isset($this->url["query"])) {
            foreach(explode("&", $this->url["query"]) as $x) {
                $y = explode("=", $x);
                if(!empty($y[0])) {
                    $this->query[$y[0]] = $y[1];
                }
            }
        }
    }
    
    /**
     * Query parameter exists?
     * 
     * @param string $name
     * @return boolean 
     */
    public function hasParam($name, $ignoreCase = false) {
        if($ignoreCase) {
            foreach($this->query as $key => $val) {
                if(strtolower($key) == strtolower($name)) {
                    return true;
                }
            }
        }
        
        return isset($this->query[$name]);
    }
    
    /**
     * Get query parameter.
     * 
     * @param string $name
     * @return string 
     */
    public function getParam($name, $ignoreCase = true) {
        if($ignoreCase) {
            foreach($this->query as $key => $val) {
                if(strtolower($key) == strtolower($name)) {
                    return $val;
                }
            }
        }
        
        return isset($this->query[$name]) ? $this->query[$name] : null;
    }

    /**
     * Add to query.
     * 
     * @param string $name
     * @param string $value
     * @return string 
     */
    public function addParam($name, $value, $ignoreCase = true) {
        if($ignoreCase) {
            foreach($this->query as $key => $val) {
                if(strtolower($key) == strtolower($name)) {
                    return $this->query[$key] = $value;
                }
            }
        }
        
        return $this->query[$name] = $value;
    }
    
    /**
     * Returns the url or false.
     * 
     * @return mixed
     */
    public function toString() {
        if(empty($this->url["host"])) {
            return false;
        }
        
        $scheme = empty($this->url["scheme"]) ? "http://" : $this->url["scheme"]."://";
        
        $user = empty($this->url["user"]) ? "" : $this->url["user"];
        $pass = empty($this->url["pass"]) ? "" : $this->url["pass"];
        
        if(!empty($pass)) $user .= ":";
        if(!empty($user) || !empty($pass)) $pass .= "@";
        
        $host = $this->url["host"];
        $port = empty($this->url["port"]) ? "" : ":".$this->url["port"];
        
        $path = empty($this->url["path"]) ? "" : $this->url["path"];
        $query = http_build_query($this->query);
        
        if(!empty($query)) $query = "?".$query;
        
        return $scheme.$user.$pass.$host.$port.$path.$query;
    }
}
