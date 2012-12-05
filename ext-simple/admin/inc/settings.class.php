<?php

class Settings extends XmlFile {

  private static $settings = null;

  public static function getSettings() {
    if (self::$settings) return $settings;
    return new Settings();
  }

  private function __construct() {
    parent::__construct(ES_SETTINGSPATH.'website.xml');
  }
  
  public function saveSettings() {
    return $this->save(ES_SETTINGSPATH.'website.xml', true);
  }
  
  public static function get($name, $default='') {
    $settings = self::getSettings();
    return $settings && isset($settings->root->$name) ? (string) $settings->root->$name : $default;
  }
  
}
