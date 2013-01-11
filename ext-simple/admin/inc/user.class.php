<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

require_once(ES_ADMINPATH.'inc/file.class.php');

class User extends XmlFile {

  function __construct($username) {
    parent::__construct(ES_USERSPATH.$username.'.xml', '<user></user>');
  }
  
  public function getUserName() {
    return (string) $this->root->username;
  }
  
  public function setPassword($password) {
    $this->root->salt = md5(rand(),0,8);
    $this->root->password = sha1($this->root->username.$this->root->salt.$password);
  }
    
  public function checkPassword($password) {
    return $this->root->password == sha1($this->root->username.$this->root->salt.$password);
  }
  
  public function getTimezone() {
    return (string) $this->root->timezone;
  }
  
  public function getLanguage() {
    return (string) $this->root->language;
  }
  
  public function saveUser() {
    return $this->save(ES_USERSPATH.$this->slug.'.xml', true);
  }
  
  public static function deleteUser($username) {
    return self::delete(ES_USERSPATH.$username.'.xml', true);
  }

  public static function getUserNames() {
    return self::listSlugs(ES_USERSPATH);
  }
  
    
}
