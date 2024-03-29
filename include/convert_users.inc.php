<?php
/**
 * This script get users from and exisiting pem databse and converts them into piwigo users
 * We get all their information and insert it into the users & users_info table
 * We update the guest and webmaster id to avoid conflicts and remove those created with piwigo installation
 */
define('PEM_ID', basename(dirname(__FILE__)));
define('PEM_PATH' , PHPWG_PLUGINS_PATH . PEM_ID . '/');
include_once(PHPWG_ROOT_PATH.'plugins/piwigo_pem/include/constants.inc.php');

global $conf;

//Remove current guest user and webmaster user
$query = '
DELETE FROM '.USERS_TABLE.'
  WHERE id IN (1,2)';

pwg_query($query);

$query = '
DELETE FROM '.USER_INFOS__TABLE.'
  WHERE id IN (1,2)';

pwg_query($query);

//Recreate guest user with different id
$inserts = array(
  array(
    'id'           => 1,
    'username'     => 'guest',
    ),
  );
mass_inserts(USERS_TABLE, array_keys($inserts[0]), $inserts);

create_user_infos(array(1), array('language' => 'en_GB'));

/**
 * Select existing users from PEM
 */

$query ='
SELECT 
  *
FROM '.PEM_USER_TABLE.'
';

$result = query2array($query);

$inserts = array();
$user_ids = array();

//For each user add to piwigo users with original ids
foreach($result as $user)
{
  $temp_user = array();
  $temp_user['id'] = $user['id_user'];
  $temp_user['username'] = $user['username'];
  $temp_user['password'] = $user['password'];
  $temp_user['mail_address'] = $user['email'];

  $inserts[$temp_user['id']] = $temp_user;

  array_push($user_ids, $temp_user['id']);
}

//Insert all pem users into piwigo users table
mass_inserts(USERS_TABLE, array_keys($inserts[2]),$inserts);

/**
 * Select existing users info from PEM
 */
$admin_ids = conf_get_param('admin_users',array());
$translator_ids = array_keys(conf_get_param('translator_users',array()));
$guest_id = conf_get_param('guest_id',1);
$webmaster_id = conf_get_param('webmaster_id',2);

$query ='
SELECT 
  *
FROM '.PEM_USER_INFOS_TABLE.'
';

$result = query2array($query);

foreach($result as $user)
{
  $temp_user = array();
  $temp_user['user_id'] = $user['idx_user'];

  //check user Id and set status accordingly
  if ($user['idx_user'] == $webmaster_id)
  {
    $temp_user['status'] = 'webmaster';
    $level = max( $conf['available_permission_levels'] );
  }
  elseif (in_array($user['idx_user'], $admin_ids))
  {
    $temp_user['status'] = 'admin';
  }
  elseif (($user['idx_user'] == $guest_id) or ($user['idx_user'] == $conf['default_user_id']))
  {
    $temp_user['status'] = 'guest';
  }
  else
  {
    $temp_user['status'] = 'normal';
  }

  //Set all language to same format for piwigo
  if("en" == $user['language'])
  {
    $temp_user['language'] = "en_GB";
  }
  else if("fr" == $user['language'])
  {
    $temp_user['language'] = "fr_FR";
  }
  else
  {
    $temp_user['language'] = $user['language'];
  }

  // Use exsiting user infos to add to piwigo user info
  $temp_user['registration_date'] = $user['registration_date'];
  $temp_user['remind_every'] = $user['remind_every'];
  $temp_user['last_reminder'] = $user['last_reminder'];

  //Insert pem user info piwigo user info table, override with the information we already have in temp_user
  create_user_infos($temp_user['user_id'], $temp_user);

  single_update(
    USER_INFOS_TABLE,
    array(
      'registration_date' => $temp_user['registration_date'],
      ),
    array('user_id' => $temp_user['user_id'])
  );

}

