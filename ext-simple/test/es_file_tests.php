<?php
require_once('../admin/inc/file.class.php');

class ESFileTests extends ESTestCase {

  private $attrs = array(
      'basic'=>'Test!',
      'name'=>'Ärö Hüber', 
      'quote'=>'\'"42" is the answer\'', 
      'html'=>'<div class="test">Do & Co</div>'
  );


  function testCreate() {
    $xml = '<content>';
    foreach ($this->attrs as $key => $value) $xml .= "<$key>".htmlspecialchars($value)."</$key>";
    $xml .= '</content>';
    $file = new XmlFile($xml);
    foreach ($this->attrs as $key => $value) {
      $this->assertEqual((string) $file->root->$key, $value);
    }
  }
  
  function testCreateSaveAndLoad() {
    $this->mkdir(ES_DATAPATH);
    $file = new XmlFile('<content></content>');
    foreach ($this->attrs as $key => $value) {
      $file->root->$key = $value;
    }
    foreach ($this->attrs as $key => $value) {
      $this->assertEqual((string) $file->root->$key, $value);
    }
    $success = $file->save(ES_DATAPATH.'test.xml', false);
    $this->assertTrue($success);
    $file2 = new XmlFile(ES_DATAPATH.'test.xml');
    foreach ($this->attrs as $key => $value) {
      $this->assertEqual((string) $file2->root->$key, $value);
    }
  }
  
  function testCreateSaveLoadAndRestore() {
    $this->mkdir(ES_DATAPATH);
    $this->mkdir(ES_BACKUPPATH);
    # create a new file
    $file = new XmlFile('<content></content>');
    foreach ($this->attrs as $key => $value) {
      $key2 = $key;
      $file->root->$key = $value;
    }
    foreach ($this->attrs as $key => $value) {
      $this->assertEqual((string) $file->root->$key, $value);
    }
    # save it without backup
    $success = $file->save(ES_DATAPATH.'test.xml', false);
    $this->assertTrue($success);
    # load it again...
    $file2 = new XmlFile(ES_DATAPATH.'test.xml');
    # ... and check it
    foreach ($this->attrs as $key => $value) {
      $this->assertEqual((string) $file2->root->$key, $value);
    }
    # add another field to the original file and delete a field...
    $file->root->another = '<[!CDATA[Test]]>';
    unset($file->root->$key2);
    # ... and save it again with backup
    $backupFile = $file->save(ES_DATAPATH.'test.xml', true);
    $this->assertEqual($backupFile, ES_BACKUPPATH.'test.xml');
    $this->assertTrue(file_exists($backupFile));
    # load the file...
    $file3 = new XmlFile(ES_DATAPATH.'test.xml');
    # ... and check it
    foreach ($this->attrs as $key => $value) {
      if ($key != $key2) $this->assertEqual((string) $file3->root->$key, $value);
    }
    $this->assertEqual((string) $file3->root->another, '<[!CDATA[Test]]>');
    $this->assertTrue(!isset($file3->root->$key2));
    # restore the file
    XmlFile::restore($backupFile);
    $this->assertEqual($backupFile, ES_BACKUPPATH.'test.xml');
    $this->assertTrue(file_exists($backupFile));
    # load the restored file...
    $file4 = new XmlFile(ES_DATAPATH.'test.xml');
    # and check that it has the original content
    foreach ($this->attrs as $key => $value) {
      $this->assertEqual((string) $file4->root->$key, $value);
    }
    $this->assertTrue(!isset($file4->root->another));
  }
  
  function testDelete() {
    
  }
  
  function testDeleteAndRestore() {
    
  }
  
}