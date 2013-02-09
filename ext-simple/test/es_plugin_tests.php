<?php
require_once('common.php');
require_once(ES_ADMINPATH.'inc/plugins.php');

class ESPluginTests extends ESTestCase {

  function testEnableDisable() {
    $this->mkdir(ES_SETTINGSPATH);
    Plugins::enablePlugin('plugin1');
    Plugins::enablePlugin('plugin3');
    Plugins::enablePlugin('plugin2');
    Plugins::disablePlugin('plugin3');
    $pluginIds = Plugins::getEnabledPluginIds();
    $this->assertEqual(count($pluginIds), 2);
    $this->assertEqual($pluginIds, array('plugin1', 'plugin2'));
  }

  function testLoadPlugins() {
    $this->mkdir(ES_SETTINGSPATH);
    Plugins::enablePlugin('plugin1');
    Plugins::enablePlugin('plugin2');
    Plugins::loadPlugins();
    $pluginIds = Plugins::getEnabledPluginIds();
    $this->assertEqual(count($pluginIds), 2);
    foreach ($pluginIds as $pluginId) {
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
    $this->assertEqual($r, 'zero, one, twouno, due, tre');
  }
  
  function testExecActionWithParams() {
    $this->mkdir(ES_SETTINGSPATH);
    Plugins::enablePlugin('plugin1');
    Plugins::enablePlugin('plugin2');
    Plugins::loadPlugins();
    ob_start();
    execAction('do-something',' - ');
    $r = ob_get_clean();
    $this->assertEqual($r, ' - , zero, one, two - , uno, due, tre');
  }
  
  function testExecFilter() {
    $this->mkdir(ES_SETTINGSPATH);
    Plugins::enablePlugin('plugin1');
    Plugins::enablePlugin('plugin2');
    Plugins::loadPlugins();
    $content = 'one to rule them all!';
    $filtered =execFilter('filter-something', array($content));    
    $this->assertEqual($filtered, 'uno to rule them all!');
  }
  
  function testExecUntil() {
    addListener('exec-until', 'return_null', array('x'));
    addListener('exec-until', 'return_1');
    addListener('exec-until', 'return_0');
    ob_start();
    $result = execUntil('exec-until', array('-'), 1);
    $this->assertEqual(ob_get_clean(), 'null-x1-');
    $this->assertIdentical($result, 1);
    addListener('exec-until2', 'return_null', array('x'));
    addListener('exec-until2', 'return_0');
    ob_start();
    $result = execUntil('exec-until2', array('-'), 1);
    $this->assertEqual(ob_get_clean(), 'null-x0-');
    $this->assertIdentical($result, null);
  }
  
  function testExecWhile() {
    addListener('exec-while', 'return_null', array('x'));
    addListener('exec-while', 'return_0');
    addListener('exec-while', 'return_1');
    ob_start();
    $result = execWhile('exec-while', array('-'), null);
    $this->assertEqual(ob_get_clean(), 'null-x0-');
    $this->assertIdentical($result, 0);
    addListener('exec-while2', 'return_null', array('x'));
    addListener('exec-while2', 'return_1');
    ob_start();
    $result = execWhile('exec-while2', array('-'), null);
    $this->assertEqual(ob_get_clean(), 'null-x1-');
    $this->assertIdentical($result, 1);
  }
  
  function testExecForInfo() {
    # listeners returning value
    addListener('info1', 'return_it', array(5));
    addListener('info1', 'return_it', array('a'));
    addListener('info1', 'return_it', array(3));
    addListener('info1', 'return_it', array(array('a','b','c')));
    $result = execForInfo('info1');
    $this->assertEqual($result, array(5,'a',3,'a','b','c'));
    # listeners returning associative arrays
    addListener('info2', 'return_it', array(array('a'=>'add', 'i'=>'insert')));
    addListener('info2', 'return_it', array(array('a'=>'attach')));
    addListener('info2', 'return_it', array(array('e'=>'edit')));
    $result = execForInfo('info2');
    $this->assertEqual($result, array('a'=>'attach','i'=>'insert','e'=>'edit'));
  }
  
}

function return_null($args) { echo "null".join('',func_get_args()); return null; }

function return_0($arg) { echo "0".$arg; return 0; }

function return_1($arg) { echo "1".$arg; return 1; }

function return_it($value) { return $value; }