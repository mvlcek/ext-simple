<?php
#header('Content-Type: text/plain');
require_once('common.php');
require_once(ES_ADMINPATH.'inc/link.class.php');

class ESLinkTests extends ESTestCase {
  
  function testParse() {
    $pattern = '/{$language}/{$parent/}{$slug}{;$page}';
    addListener('get-link-patterns', 'ESLinkTests::langPattern');
    $result = Link::parse($pattern, '/en/products/bike;2');
    $this->assertEqual($result, array('language'=>'en', 'parent'=>'products', 'slug'=>'bike', 'page'=>'2'));
    $result = Link::parse($pattern, '/en/bike;2');
    $this->assertEqual($result, array('language'=>'en', 'slug'=>'bike', 'page'=>'2'));
    $result = Link::parse($pattern, '/en/bike');
    $this->assertEqual($result, array('language'=>'en', 'slug'=>'bike'));
    $result = Link::parse($pattern, '/en/cat/subcat/bike');
    $this->assertEqual($result, false); # multiple parents not allowed
    $result = Link::parse($pattern, '/da/bike');
    $this->assertEqual($result, false); # da is not a valid language
    $result = Link::parse('/', '/');
    $this->assertEqual($result, array());
  }
  
  function testFormat() {
    $pattern = '/{$parent/}{$slug}';
    $uri = Link::format($pattern, array('parent'=>'products','slug'=>'bike'));
    $this->assertEqual($uri, '/products/bike');
    addListener('get-link-values', 'ESLinkTests::linkValues');
    $pattern = '/{$language}/{$parent/}{$slug}{;$page}';
    $uri = Link::format($pattern, array('parent'=>'products','slug'=>'bike'));
    $this->assertEqual($uri, '/en/products/bike;2');    
    $uri = Link::format($pattern, array('slug'=>'bike'));
    $this->assertEqual($uri, '/en/bike;2');    
    $uri = Link::format($pattern, array('parent'=>'products'));
    $this->assertEqual($uri, null); # slug is not an optional parameter    
    $uri = Link::format('/');
    $this->assertEqual($uri, '/');
  }
  
  static function langPattern() {
    return array('language'=>'en|de');
  }
  
  static function linkValues() {
    return array('language'=>'en', 'page'=>2);
  }
  
}
