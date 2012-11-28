<?php

class DataFile {
  
  static public function getBackupFilename($filename) {
    if (!substr($filename,0,strlen(ES_DATAPATH)) == ES_DATAPATH) return false;
    return ES_BACKUPPATH.substr($filename,strlen(ES_DATAPATH));
  }
  
  static public function getFilename($backupFilename) {
    if (!substr($backupFilename,0,strlen(ES_BACKUPPATH)) == ES_BACKUPPATH) return false;
    return ES_DATAPATH.substr($backupFilename,strlen(ES_BACKUPPATH));
  }
  
  /* on success returns the backup filename */
  static public function backup($filename) {
    if (!($backupFilename = self::getBackupFilename($filename))) return false;
    if (copy($filename, $backupFilename)) return $backupFilename;
    return false;
  }
  
  /* on success returns true or the backup filename */
  static public function delete($filename, $backup=true) {
    if ($backup && !($backupFilename = self::backup($filename))) return false;
    if (unlink($filename)) return $backup ? $backupFilename : true;
    return false;
  }
  
  /* on success returns true or the backup filename */
  static public function restore($backupFilename) {
    if (!($filename = self::getFilename($backupFilename))) return false;
    if (!file_exists($backupFilename)) return false;
    if (!file_exists($filename)) {
      return rename($backupFilename, $filename);
    } else if (rename($filename, $backupFilename.'.tmp')) {
      // we created a temporary backup of the original file...
      if (rename($backupFilename, $filename)) {
        // ... and rename it to the backup file
        if (rename($backupFilename.'.tmp', $backupFilename)) {
          return $backupFilename;
        } else {
          @unlink($backupFilename.'.tmp');
          return true;
        }
      } else {
        // ... and rename back
        @rename($backupFilename.'.tmp', $filename);
        return false;
      }
    } else {
      @unlink($filename);
      // backup of existing file failed, just overwrite:
      return rename($backupFilename, $filename);
    }
  }
  
  static public function setAttribs($filename) {
    if (defined('ES_CHMOD')) chmod($filename, ES_CHMOD);
    if (defined('ES_CHOWN')) chown($filename, ES_CHOWN);
  }
  
}

class XmlFile extends DataFile {

  public $root = null;
	
  public function __construct($filenameOrRootElement) {
    if (substr($filenameOrRootElement,0,1) == '<') {
      if (substr($filenameOrRootElement,0,5) != '<?xml') {
        $filenameOrRootElement = '<?xml version="1.0" encoding="UTF-8"?>'.$filenameOrRootElement;
      }
      $this->root = @new SimpleXMLExtended($filenameOrRootElement);
		} else {
      $this->root = simplexml_load_file($filenameOrRootElement, 'SimpleXMLExtended', LIBXML_NOCDATA);
		}
	}
  
  /* on success returns true or the backup filename */
  public function save($filename, $backup=true) {
    $backupFilename = $backup ? self::backup($filename) : true;
    $success = $this->root->asXML($filename) === TRUE;
    self::setAttribs($filename);
    return $success ? $backupFilename : false;
  }
  
}


class SimpleXMLExtended extends SimpleXMLElement{   

  public function addCData($cdata_text){   
    $node= dom_import_simplexml($this);   
    $no = $node->ownerDocument;   
    $node->appendChild($no->createCDATASection($cdata_text));   
  } 

} 
