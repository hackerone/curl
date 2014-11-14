<?php

/**
 * Curl wrapper PHP v2
 * @author hackerone
 */
class Curl
{

    // Default options from config.php
    public $options = array();

    // request specific options - valid only for single request
    public $request_options = array();


    private $_header, $_headerMap, $_error, $_status, $_info;

    // default config
    private $_config = array(
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER         => false,
        CURLOPT_VERBOSE        => true,
        CURLOPT_AUTOREFERER    => true,         
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)'
    );

    public static function mergeArray()
    {
        $args=func_get_args();
        $res=array_shift($args);
        while(!empty($args))
        {
            $next=array_shift($args);
            foreach($next as $k => $v)
            {
                if(is_array($v) && isset($res[$k]) && is_array($res[$k]))
                    $res[$k]=self::mergeArray($res[$k],$v);
                elseif(is_numeric($k))
                    isset($res[$k]) ? $res[]=$v : $res[$k]=$v;
                else
                    $res[$k]=$v;
            }
        }
        return $res;
    }

    public function getOptions()
    {
        $options = self::mergeArray($this->request_options, $this->options, $this->_config);
        return $options;
    }

    public function setOption($key, $value, $default = false)
    {
        if($default)
            $this->options[$key] = $value;
        else
            $this->request_options[$key] = $value;

        return $this;
    }

    /**
     * Clears Options
     * This will clear only the request specific options. Default options cannot be cleared.
     */

    public function resetOptions()
    {
        $this->request_options = array();
        return $this;
    }

    /**
     * Resets the Option to Default option
     */
    public function resetOption($key)
    {
        if(isset($this->request_options[$key]))
            unset($this->request_options[$key]);
        return $this;
    }

    public function setOptions($options, $default = false)
    {
        if($default)
            $this->options = $options + $this->request_options;
        else
            $this->request_options = $options + $this->request_options;

        return $this;
    }

    public function buildUrl($url, $data = array())
    {
        $parsed = parse_url($url);
        
        isset($parsed['query']) ? parse_str($parsed['query'], $parsed['query']) : $parsed['query'] = array();

        $params = isset($parsed['query']) ? $data + $parsed['query'] : $data;
        $parsed['query'] = ($params) ? '?' . http_build_query($params) : '';
        if (!isset($parsed['path'])) {
            $parsed['path']='/';
        }

        $parsed['port'] = isset($parsed['port'])?':'.$parsed['port']:'';

        return $parsed['scheme'].'://'.$parsed['host'].$parsed['port'].$parsed['path'].$parsed['query'];
    }

    public function exec($url, $options, $debug = false)
    {
        $this->_error = null;
        $this->_header = null;
        $this->_headerMap = null;
        $this->_info = null;
        $this->_status = null;

        $ch = curl_init($url);
        curl_setopt_array($ch, $options);

        $output = curl_exec($ch);

        $this->_status = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if(!$output){
            $this->_error = curl_error($ch);
            $this->_info = curl_getinfo($ch);
        }
        else if($debug)
            $this->_info = curl_getinfo($ch);

        if(@$options[CURLOPT_HEADER] == true){
            list($header, $output) = $this->_processHeader($output, curl_getinfo($ch, CURLINFO_HEADER_SIZE));
            $this->_header = $header;
        }
        curl_close($ch);

        return $output;
    }

    public function _processHeader($response, $header_size)
    {
        return array(substr($response, 0, $header_size), substr($response, $header_size));
    }

    public function get($url, $params = array(), $debug = false)
    {
        $exec_url = $this->buildUrl($url, $params);
        $options = $this->getOptions();
        return $this->exec($exec_url,  $options, $debug);
    }

    public function post($url, $data, $params = array(), $debug = false)
    {
        $url = $this->buildUrl($url, $params);

        $options = $this->getOptions();
        $options[CURLOPT_POST] = true;
        $options[CURLOPT_POSTFIELDS] = $data;

        return $this->exec($url, $options, $debug);
    }

    public function put($url, $data = null, $params = array(), $debug = false)
    {
        $url = $this->buildUrl($url, $params);
        
        $f = fopen('php://temp', 'rw+');
        fwrite($f, $data);
        rewind($f);

        $options =  $this->getOptions();
        $options[CURLOPT_PUT] = true;
        $options[CURLOPT_INFILE] = $f;
        $options[CURLOPT_INFILESIZE] = strlen($data);

        return $this->exec($url, $options, $debug);
    }

    public function patch($url, $data = array(), $params = array(), $debug = false)
    {
        $url = $this->buildUrl($url, $params);
        
        $options = $this->getOptions();
        $options[CURLOPT_CUSTOMREQUEST] = 'PATCH';
        $options[CURLOPT_POSTFIELDS] = $data;

        return $this->exec($url, $options, $debug);
    }

    public function delete($url, $params = array(), $debug = false)
    {
        $url = $this->buildUrl($url, $params);
        
        $options = $this->getOptions();
        $options[CURLOPT_CUSTOMREQUEST] = 'DELETE';

        return $this->exec($url, $options, $debug);
    }

    /**
     * Gets header of the last curl call if header was enabled
     */
    public function getHeaders()
    {
        if(!$this->_header)
            return array();

        if(!$this->_headerMap){

            $headers = explode("\r\n", trim($this->_header));
            $output = array();
            $output['http_status'] = array_shift($headers);

            foreach($headers as $line){
                $params = explode(':', $line, 2);

                if(!isset($params[1]))
                    $output['http_status'] = $params[0];
                else
                    $output[trim($params[0])] = trim($params[1]);
            }

            $this->_headerMap = $output;
        }
        return $this->_headerMap;
    }

    public function addHeader($header = array())
    {
        $h = isset($this->request_options[CURLOPT_HTTPHEADER]) ? $this->request_options[CURLOPT_HTTPHEADER] : array();
        foreach($header as $k => $v){
            $h[] = $k.': '.$v;
        }

        $this->setHeaders($h);
        return $this;
    }

    public function getHeader($key)
    {
        $headers = array_change_key_case($this->getHeaders(), CASE_LOWER);
        $key = strtolower($key);

        return @$headers[$key];
    }

    public function setHeaders($header = array(), $default = false)
    {
        if ($this->_isAssoc($header)) {
            $out = array();
            foreach ($header as $k => $v) {
                $out[] = $k .': '.$v;
            }
            $header = $out;
        }

        $this->setOption(CURLOPT_HTTPHEADER, $header, $default);
        return $this;
    }

    private function _isAssoc($arr)
    {
        return array_keys($arr) !== range(0, count($arr) - 1);
    }

    public function getError()
    {
        return $this->_error;
    }

    public function getInfo()
    {
        return $this->_info;
    }

    public function getStatus()
    {
        return $this->_status;
    }

    public function init()
    {
        return;
    }

}
