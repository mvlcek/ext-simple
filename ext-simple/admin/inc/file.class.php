<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

class DataFile {

  static public function inDataPath($filename) {
    $dirname = dirname($filename);
    return substr($dirname,0,strlen(ES_DATAPATH)) == ES_DATAPATH;
  }
  
  static public function inBackupPath($filename) {
    $dirname = dirname($filename);
    return substr($dirname,0,strlen(ES_BACKUPSPATH)) == ES_BACKUPSPATH;
  }
  
  static public function exists($filename) {
    return file_exists($filename);
  }
  
  static public function getBackupFilename($filename) {
    if (!substr($filename,0,strlen(ES_DATAPATH)) == ES_DATAPATH) return false;
    return ES_BACKUPSPATH.substr($filename,strlen(ES_DATAPATH));
  }
  
  static public function getFilename($backupFilename) {
    if (!substr($backupFilename,0,strlen(ES_BACKUPSPATH)) == ES_BACKUPSPATH) return false;
    return ES_DATAPATH.substr($backupFilename,strlen(ES_BACKUPSPATH));
  }
  
  /* on success returns the backup filename */
  static public function backup($filename) {
    if (!($backupFilename = self::getBackupFilename($filename))) return false;
    if (copy($filename, $backupFilename)) return $backupFilename;
    return false;
  }
  
  /* on success returns true or the backup filename */
  static public function delete($filename, $backup=true) {
    if (!self::inDataPath($filename)) return false;
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
  
  static public function setAttributes($filename) {
    if (defined('ES_FILE_MOD')) chmod($filename, ES_FILE_MOD);
    if (defined('ES_FILE_OWNER')) chown($filename, ES_FILE_OWNER);
  }
  
}


class DataDir {
  
  static public function setAttributes($dirname) {
    if (defined('ES_DIR_MOD')) chmod($dirname, ES_DIR_MOD);
    if (defined('ES_DIR_OWNER')) chown($dirname, ES_DIR_OWNER);
  }
  
  static public function create($dirname) {
    $parent = dirname($dirname);
    if ($parent != '.' && !file_exists($parent)) {
      if (!self::create($parent)) return false;
    }
    if (mkdir($dirname)) {
      self::setAttributes($dirname);
      return true;
    } else {
      return false;
    }
  }
  
  static public function delete($dirname, $recursive=false) {
    if (file_exists($dirname) && is_dir($dirname)) {
      if ($recursive) {
        $dir = opendir($dirname);
        while (($name = readdir($dir)) !== false) {
          if (!is_dir($dirname.'/'.$name)) {
            unlink($dirname.'/'.$name);
          } else if ($name != '.' && $name != '..') {
            self::delete($dirname.'/'.$name);
          }
        }
        closedir($dir);
      }
      return rmdir($dirname);    
    }
  }
  
}


class XmlFile extends DataFile {

  const FIELDTYPE_ENUM  = 'enum';   # enumeration
  const FIELDTYPE_TEXT  = 'text';
  const FIELDTYPE_HTML  = 'html';
  const FIELDTYPE_DATE  = 'date';
  const FIELDTYPE_INT   = 'int';
  const FIELDTYPE_FLOAT = 'float';
  const FIELDTYPE_LIST  = 'list';   # comma separated list
  const FIELDTYPE_REF   = 'ref';    # reference to another object (slug)
  const FIELDTYPE_USER  = 'user';   # user name

  private static $fieldTypes = array();

  public $root = null;
  public $new = false;
	
  public function __construct($filename, $rootElement='<root></root>') {
    if ($filename && file_exists($filename)) {
      $this->root = simplexml_load_file($filename, 'SimpleXMLExtended', LIBXML_NOCDATA);
    } else {
      if (!$rootElement) $rootElement = '<root></root>';
      if (substr($rootElement,0,5) != '<?xml') {
        $rootElement = '<?xml version="1.0" encoding="UTF-8"?>'.$rootElement;
      }
      $this->root = @new SimpleXMLExtended($rootElement);
      $this->new = true;
		}
	}
  
  public function isNew() {
    return $this->new;
  }
  
  /* on success returns true or the backup filename */
  public function save($filename, $backup=true) {
    if (!self::inDataPath($filename)) return false;
    $backupFilename = $backup ? self::backup($filename) : true;
    if ($this->root->asXML($filename) === TRUE) {
      $this->new = false;
      self::setAttributes($filename);
      return $backupFilename;
    } else {
      return false;
    }
  }
  
  public function getString($name, $variant=null) {
    $default = null;
    foreach ($this->root->$name as $field) {
      $fieldVariant = @$field['variant'];
      if ($fieldVariant == $variant) return (string) $field;
      if (!$fieldVariant) $default = (string) $field;
    }
    return $default;
  }
  
  public function getTime($name, $variant=null) {
    $value = $this->getString($name, $variant);
    return is_numeric($value) ? (int) $value : strtotime($value);
  }
  
  public static function listSlugs($path) {
    $slugs = array();
    $dir = opendir($path);
    if ($dir) while (($filename = readdir($dir)) !== false) {
      if (substr($filename,-4) == '.xml') $slugs[] = substr($filename,0,-4);
    }
    closedir($dir);
    return $slugs;
  }
  
  public static function getFieldTypes($objClass, $objType) {
    $fullType = $objClass . ($objType ? '-'.$objType : '');
    if (!isset(self::$fieldTypes[$fullType])) {
      self::$fieldTypes[$fullType] = execForInfo('get-fieldtypes-'.$objClass, $objType);
    }
    return self::$fieldTypes[$fullType];
  }
  
  public static function getFieldType($objClass, $objType, $name) {
    $fullType = $objClass . ($objType ? '-'.$objType : '');
    $types = self::getFieldTypes($fullType);
    return @$types[$name];
  }
  
  /**
   * The object class is the part of the path after ES_DATAPATH, e.g. 'pages'
   */
  public static function getObjectClassFromPath($path) {
    if (substr($path,-1) == '/') $path = substr($path,0,-1);
    if (substr($path,0,strlen(ES_DATAPATH)) == ES_DATAPATH) {
      return substr($path, strlen(ES_DATAPATH)+1);
    }
    return $path;
  }
  
}


class SimpleXMLExtended extends SimpleXMLElement{   

  public function addCData($cdata_text){   
    $node= dom_import_simplexml($this);   
    $no = $node->ownerDocument;   
    $node->appendChild($no->createCDATASection($cdata_text));   
  } 

} 
