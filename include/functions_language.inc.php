<?php
// +-----------------------------------------------------------------------+
// | PEM - a PHP based Extension Manager                                   |
// | Copyright (C) 2005-2013 PEM Team - http://piwigo.org                  |
// +-----------------------------------------------------------------------+
// | This program is free software; you can redistribute it and/or modify  |
// | it under the terms of the GNU General Public License as published by  |
// | the Free Software Foundation                                          |
// |                                                                       |
// | This program is distributed in the hope that it will be useful, but   |
// | WITHOUT ANY WARRANTY; without even the implied warranty of            |
// | MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the GNU      |
// | General Public License for more details.                              |
// |                                                                       |
// | You should have received a copy of the GNU General Public License     |
// | along with this program; if not, write to the Free Software           |
// | Foundation, Inc., 59 Temple Place - Suite 330, Boston, MA 02111-1307, |
// | USA.                                                                  |
// +-----------------------------------------------------------------------+

/**
 * tries to determine the visitor language from SESSION, SERVER and config
 */
function get_current_language()
{
  global $db, $conf, $user;
  
  $language = null;
  
  $interface_languages = get_interface_languages();
  
  if (isset($_GET['lang']))
  {
    $language = @$interface_languages[$_GET['lang']];
  }
  else if (isset($_SESSION['language']))
  {
    $language = $_SESSION['language'];
  }
  
  if (empty($language) or !is_array($language))
  {
    $language = @$interface_languages[ [$user['language']]];

    $get_browser_language = conf_get_param('get_browser_language',true);
    
    if ($get_browser_language)
    {
      $browser_language = @substr($_SERVER["HTTP_ACCEPT_LANGUAGE"],0,2);
      foreach ($interface_languages as $interface_language)
      {
        if (substr($interface_language['code'], 0, 2) == $browser_language)
        {
          $language = $interface_languages[$interface_language['code']];
          break;
        }
      }
    }
  }

  // we needed the name and ID for tha language, in an array
  // However we weren't getting that before. At some point we were sending a sting but we required an array.
  // This next step forces an array if need be

  if (empty($language))
  {
    $language = $interface_languages['en_UK'];
  }

  return $language;
}

/**
 * same as above but only returns the id
 */
function get_current_language_id()
{
  $language = null;
  
  if (isset($_SESSION['language']))
  {
    $language = $_SESSION['language'];
  }
  else
  {
    $language = get_current_language();
  }
  
  return $language['id'];
}

/**
 * returns available languages
 */
function get_interface_languages()
{
  global $db, $conf, $cache;

  if (isset($cache['interface_languages']))
  {
    return $cache['interface_languages'];
  }
  
  $query = '
SELECT id_language AS id,
       code,
       name
  FROM '.PEM_LANG_TABLE.'
  WHERE interface = "true"
  ORDER BY name
;';
  $result = pwg_query($query);
  $interface_languages = array();
  while ($row = pwg_db_fetch_assoc($result))
  {
    $interface_languages[$row['code']] = $row;
  }

  $cache['interface_languages'] = $interface_languages;

  return $cache['interface_languages'];
}

/**
 * returns available languages names for a set of revisions
 */
function get_all_ext_languages()
{
  $query = '
SELECT 
    id_language, name 
  FROM '.PEM_LANG_TABLE.'
  WHERE extensions = true
;';
  $languages = query2array($query,'id_language');

  return $languages;
}


?>