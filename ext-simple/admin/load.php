<?php
# +--------------------------------------------------------------------+
# | ExtSimple                                                          |
# | The simple extensible XML based CMS                                |
# +--------------------------------------------------------------------+
# | Copyright (c) 2013 Martin Vlcek                                    |
# | License: GPLv3 (http://www.gnu.org/licenses/gpl-3.0.html)          |
# +--------------------------------------------------------------------+

require_once('inc/common.php');
#Common::ensureLoggedIn();
Common::loadPlugins();
require_once('inc/template.php');

$pluginName = $_REQUEST['id'];
execAction('process-plugin-'.$pluginName);


execAction('display-plugin-'.$pluginName);