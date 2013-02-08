<?php
require_once('common.php');
require_once(ES_ADMINPATH.'inc/plugins.php');

class ESPluginTests extends ESTestCase {

  function testLoad() {
    $this->mkdir(ES_SETTINGSPATH);
    Plugins::enablePlugin('plugin1');
    Plugins::enablePlugin('plugin2');
    $pluginIds = Plugins::getEnabledPluginIds();
    $this->assertEqual(count($pluginIds), 2);
    $this->assertEqual($pluginIds, array('plugin1', 'plugin2'));
  }

  function testLoadPlugins() {
    $this->mkdir(ES_SETTINGSPATH);
    Plugins::enablePlugin('plugin1');
    Plugins::enablePlugin('plugin2');
    Plugins::loadPlugins();
    foreach (Plugins::getEnabledPluginIds() as $pluginId) {
      $p = Plugins::getPlugin($pluginId);
      $this->assertNotNull($p);
    }
  }
  
  function testExecAction() {
    $this->mkdir(ES_SETTINGSPATH);
    Plugins::enablePlugin('plugin1');
    Plugins::enablePlugin('plugin2');
    Plugins::loadPlugins();
    ob_start();
    execAction('do-something');
    $r = ob_get_clean();
    $this->assertEqual($r, 'one, two, threeuno, due, tre');
  }
  
}