<?php

/**
* CurlTest
*/
//require_once './vendor/autoload.php';

require_once '../Curl.php';

class CurlTest extends PHPUnit_Framework_TestCase
{

  protected $curl;

  protected $mockUrl = 'http://demo1875040.mockable.io/';

  private $_testUrls = [
    [
      'url' => 'http://www.codevu.com/test',
      'data' => ["name" => "curl", "type" => "extension"],
      'output' => 'http://www.codevu.com/test?name=curl&type=extension'
    ],
    [
      'url' => 'https://www.codevu.com/test',
      'data' => ["name" => "curl", "type" => "extension"],
      'output' => 'https://www.codevu.com/test?name=curl&type=extension'
    ],
    [
      'url' => 'http://www.codevu.com:8080/test',
      'data' => ["name" => "curl", "type" => "extension"],
      'output' => 'http://www.codevu.com:8080/test?name=curl&type=extension'
    ],
  ];

  public function setUp()
  {
    $this->curl = new Curl;
  }

  public function testGetOptions()
  {
    $result = $this->curl->getOptions();
    $this->assertEquals($result, [ CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HEADER         => false,
        CURLOPT_VERBOSE        => true,
        CURLOPT_AUTOREFERER    => true,         
        CURLOPT_CONNECTTIMEOUT => 30,
        CURLOPT_TIMEOUT        => 30,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; Googlebot/2.1; +http://www.google.com/bot.html)']);

  }

  public function testSetOption()
  {
    
    $result = $this->curl->setOption(CURLOPT_HEADER, true)->getOptions();

    $this->assertEquals($result[CURLOPT_HEADER], true);
  }

  public function testResetOptions()
  {
    $this->curl->setOption(CURLOPT_HEADER, true);
    $result = $this->curl->resetOptions()->getOptions();
    $this->assertEquals($result[CURLOPT_HEADER],false);

  }

  public function testResetOption()
  {
    $result = $this->curl->setOption(CURLOPT_FAILONERROR, true)->getOptions();
    $this->assertEquals($result[CURLOPT_FAILONERROR], true);

    $result = $this->curl->resetOption(CURLOPT_FAILONERROR)->getOptions();
    $this->assertArrayNotHasKey(CURLOPT_FAILONERROR, $result);
  }

  public function testSetOptions()
  {
    $this->curl->setOptions([
        CURLOPT_CRLF => true,
        CURLOPT_FRESH_CONNECT => true,
      ]);

    $options = $this->curl->getOptions();

    $this->assertEquals($options[CURLOPT_CRLF], true);
    $this->assertEquals($options[CURLOPT_FRESH_CONNECT], true);
  }

  public function testBuildUrl()
  {
    foreach($this->_testUrls as $data){
      $url = $this->curl->buildUrl($data['url'], $data['data']);
      $this->assertEquals($url, $data['output']);
    }
  }

  public function testGet()
  {
    $result = $this->curl->get($this->mockUrl . '/get-test');
    $this->assertEquals($result, 'get-success');
  }

  public function testGetWithHeader()
  {
    $result = $this->curl->setOption(CURLOPT_HEADER, true)->get($this->mockUrl . '/get-test');
    $this->assertEquals($result, 'get-success');
  }

  public function testPost()
  {
    $result = $this->curl->post($this->mockUrl.'/post-test', ['data'=> 'post']);
    $this->assertEquals($result, 'post-success');
  }

  public function testPut()
  {
    $result = $this->curl->put($this->mockUrl.'/put-test', "put");
    $this->assertEquals($result, 'put-success');
  }

  public function testPatch()
  {
    $result = $this->curl->patch($this->mockUrl.'/patch-test', ['data'=> 'patch']);
    $this->assertEquals($result, 'patch-success');
  }


  public function testDelete()
  {
    $result = $this->curl->delete($this->mockUrl.'/delete-test');
    $this->assertEquals($result, 'delete-success');
  }

  public function testGetHeaders()
  {
    $result = $this->curl->setOption(CURLOPT_HEADER, true)->get($this->mockUrl . '/get-test');
    $this->assertEquals($result, 'get-success');
    
    $headers = $this->curl->getHeaders();

    $this->assertArrayHasKey('Server', $headers);

  }

  public function testGetHeader()
  {
    $result = $this->curl->setOption(CURLOPT_HEADER, true)->get($this->mockUrl . '/get-test');
    $code = $this->curl->getHeader('Server');
    $this->assertEquals($code, 'Google Frontend');
  }

  public function testGetStatus()
  {
    $result = $this->curl->setOption(CURLOPT_HEADER, true)->get($this->mockUrl . '/get-test');
    $code = $this->curl->getStatus();
    $this->assertEquals($code, 200);
  }

  public function testAddHeader()
  {
    $this->curl->setHeaders(['first_header' => 'first']);
    $this->curl->addHeader(['second_header' => 'added']);
    $options = $this->curl->getOptions();
    $header = $options[CURLOPT_HTTPHEADER];
    $this->assertEquals(2, count($header));
  }

}
