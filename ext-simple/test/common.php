<?php
require_once('simpletest/autorun.php');

error_reporting(E_ALL | E_STRICT);
ini_set("display_errors", 1);

define('TMP_DIR', '/tmp');
define('ES_ADMINPATH', dirname(dirname(__FILE__)).'/admin/');
define('ES_ROOTPATH', dirname(__FILE__).'/data/');
define('ES_DATAPATH', ES_ROOTPATH.'data/');
define('ES_SETTINGSPATH', ES_DATAPATH.'settings/');
define('ES_USERSPATH', ES_DATAPATH.'users/');
define('ES_PAGESPATH', ES_DATAPATH.'pages/');
define('ES_UPLOADSPATH', ES_DATAPATH.'uploads/');
define('ES_THUMBNAILSPATH', ES_DATAPATH.'thumbs/');
define('ES_BACKUPPATH', ES_ROOTPATH.'backups/');
define('ES_THEMESPATH', dirname(__FILE__).'themes/');
define('ES_PLUGINSPATH', dirname(__FILE__).'/plugins/');

abstract class ESTestCase extends UnitTestCase {
  
  private $tmpFiles = array();
  
  function mkdir($dir) {
    if (substr($dir,-1) == '/') $dir = substr($dir,0,strlen($dir)-1);
    if (!file_exists(dirname($dir))) $this->mkdir(dirname($dir));
    @mkdir($dir);
  }
  
  function setUp() {
    $this->mkdir(ES_ROOTPATH);
    $this->deleteContent(ES_ROOTPATH);
  }
  
  function tearDown() {
    #$this->deleteContent(ES_ROOTPATH);
    foreach ($this->tmpFiles as $tmpFile) @unlink($tmpFile);
    $this->tmpFiles = array();
  }
  
  function deleteContent($dir) {
    $dh = opendir($dir);
    while ($filename = readdir($dh)) {
      if ($filename != '.' && $filename != '..') {
        $path = $dir.'/'.$filename;
        if (is_dir($path)) {
          $this->deleteContent($path);
          rmdir($path);
        } else {
          unlink($path);
        }
      }
    }
    closedir($dh);
  }
  
  function getFile($content) {
    $tmpFile = tempnam(TMP_DIR, 'tes');
    file_put_contents($tmpFile, $content);
    $this->tmpFiles[] = $tmpFile;
    return $tmpFile;
  }
  
}