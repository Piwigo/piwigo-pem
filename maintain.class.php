<?php
defined('PHPWG_ROOT_PATH') or die('Hacking attempt!');

class piwigo_pem_maintain extends PluginMaintain
{
  private $installed = false;
 
  function __construct($plugin_id)
  {
    parent::__construct($plugin_id);
  }
    /**
   * plugin installation
   */
  function install($plugin_version, &$errors=array())
  {
    include(PHPWG_ROOT_PATH.'admin/include/functions_install.inc.php');
    global $prefixeTable;

    // Create tables
    execute_sqlfile(
      PHPWG_ROOT_PATH.'plugins/piwigo_pem/install/pem_structure-mysql.sql',
      'pem_',
      $prefixeTable.'pem_',
      'mysql'
    );

  }

  /**
   * Plugin activation
   */
  function activate($plugin_version, &$errors=array())
  {
  }

  /**
   * Plugin deactivation
   */
  function deactivate()
  {
  }

  /**
   * Plugin (auto)update
   */
  function update($old_version, $new_version, &$errors=array())
  {
    $this->install($new_version, $errors);
  }

    /**
   * Plugin uninstallation
   */
  function uninstall() 
  {
    include(PHPWG_ROOT_PATH.'admin/include/functions_install.inc.php');
    global $prefixeTable, $logger;

    $logger->debug(__FUNCTION__.' Salut les frites ');

    $tables_to_drop = array(
      'authors',
      'categories',
      'categories_translations',
      'download_log',
      'extensions',
      'extensions_categories',
      'extensions_tags',
      'extensions_translations',
      'hosting_details',
      'languages',
      'links',
      'rates',
      'reviews',
      'revisions',
      'revisions_compatibilities',
      'revisions_languages',
      'revisions_translations',
      'tags',
      'tags_translations',
      'user_infos',
      'users',
      'versions',
    );

    foreach ($tables_to_drop as $table)
    {
      $query = 'DROP TABLE '.$prefixeTable.'pem_'.$table.';';
      pwg_query($query);
    }
  }

}