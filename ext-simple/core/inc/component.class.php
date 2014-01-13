<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

require_once(ES_COREPATH.'inc/file.class.php');

class Component extends XmlSlugFile {

  function __construct($name) {
    parent::__construct($name, 'components', '<component></component>');
  }
    
  public static function existsComponent($slug) {
    return self::existsSlugFile($slug, 'components');
  }
  
  public static function deleteComponent($slug) {
    return self::deleteSlugFile($slug, 'components');
  }
  
}