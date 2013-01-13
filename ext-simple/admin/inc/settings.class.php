<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

require_once(ES_ADMINPATH.'inc/file.class.php');

class Settings extends XmlFile {

  private static $settings = null;
  private static $websiteUrl = null;

  private function __construct() {
    parent::__construct(ES_SETTINGSPATH.'website.xml');
    if (!isset($this->root->salt)) $this->root->salt = sha1(rand().time()); 
  }
  
  public static function getSettings() {
    if (self::$settings) return $settings;
    return new Settings();
  }

  public function saveSettings() {
    return $this->save(ES_SETTINGSPATH.'website.xml', true);
  }
  
  public static function get($name, $default='') {
    $settings = self::getSettings();
    return $settings && isset($settings->root->$name) ? (string) $settings->root->$name : $default;
  }
  
  public static function getWebsiteName() {
    return self::get('website-name', 'My Website');
  }
  
  public static function suggestWebsiteURL() {
    if (!self::$websiteUrl) {
      $path = dirname($_SERVER['PHP_SELF']);
      if (($pos = strrpos($path, 'index.php')) !== false) {
        $path = substr($path,0,$pos);
      } else if (($pos = strrpos($path, ES_ADMIN.DIRECTORY_SEPARATOR)) !== false) {
        $path = substr($path,0,$pos);
      }
      if (!$_SERVER('HTTPS') || $_SERVER['HTTPS'] == 'off') {
        $port = $_SERVER['SERVER_PORT'] != '80' ? ':'.$_SERVER['SERVER_PORT'] : '';
        $path = 'http://'.$_SERVER['SERVER_NAME'].$port.$path;
      } else {
        $port = $_SERVER['SERVER_PORT'] != '443' ? ':'.$_SERVER['SERVER_PORT'] : '';
        $path = 'https://'.$_SERVER['SERVER_NAME'].$port.$path;
      }
      self::$websiteUrl = $path;
    }
    return self::$websiteUrl;
  }
  
  public static function getWebsiteURL() {
    $url = self::get('website-url', null);
    return $url ? $url : self::suggestWebsiteURL();
  }
  
  public static function getWebsiteLanguage() {
    return self::get('website-language', null);
  }
  
  public static function getSalt() {
    return self::get('salt', 'Salt');
  }
  
  public static function getPrettyURL() {
    return self::get('pretty-url', null);
  }
  
}
