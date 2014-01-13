<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

interface User {

  public function getUserName();

  public function getDisplayName();
  
  public function getTimezone();
  
  function getLanguage();
  
}

class SimpleUser extends XmlSlugFile implements User {
  
  
  
}
