<?php

/**
* CurlTest
*/

require_once '../../framework/yii.php'; // framework path

require_once '../Curl.php';

class CurlTest extends PHPUnit_Framework_TestCase
{

  private $_testUrls = array(
    array(
      'url' => 'http://www.codevu.com/test',
      'data' => array("name" => "curl", "type" => "extension"),
      'output' => 'http://www.codevu.com/test?name=curl&type=extension'
    ),
    array(
      'url' => 'https://www.codevu.com/test',
      'data' => array("name" => "curl", "type" => "extension"),
      'output' => 'https://www.codevu.com/test?name=curl&type=extension'
    ),
    array(
      'url' => 'http://www.codevu.com:8080/test',
      'data' => array("name" => "curl", "type" => "extension"),
      'output' => 'http://www.codevu.com:8080/test?name=curl&type=extension'
    ),
  );
  public function testBuildUrl()
  {
    
    $c = new Curl;

    foreach($this->_testUrls as $test){
      $this->assertEquals(
        $test['output'],
        $c->buildUrl($test['url'], $test['data'])
        );
    }
  }

  public function testHeader()
  {
    $c = new Curl();
    $c->init(); // testing out of app, so have to initialize manually

    $result = $c->get('http://echo.jsontest.com/key/value/one/two');

    $json = json_decode($result);
    $this->assertEquals($json->key, 'value');

    $headers = $c->getHeaders();
    $this->assertContains('HTTP/1.1 200 OK',$headers);

  }
}
