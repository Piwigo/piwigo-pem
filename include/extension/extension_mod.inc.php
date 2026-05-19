<?php

// +-----------------------------------------------------------------------+
// |                           Initialization                              |
// +-----------------------------------------------------------------------+

global $page, $user, $logger;

if (isset($_GET['eid']))
{
  // using single_view.tpl
  $current_extension_page_id = $_GET['eid'];
  $authors = get_extension_authors($current_extension_page_id);
}
else if (isset($_GET['uid']))
{
  // Using account.tpl
  $current_user_page_id = $_GET['uid'];
}

// +-----------------------------------------------------------------------+
// |                           Form submission                             |
// +-----------------------------------------------------------------------+
include_once(PEM_PATH . 'include/functions_users.inc.php');

if ($_SERVER['REQUEST_METHOD'] === 'POST' and !isset($_POST['submit']))
{
  $logger->info('no POST submit in FILE = '.__FILE__.', LINE = '.__LINE__);
  set_status_header(489);
  exit();
}

if (isset($_POST['submit']) and !isset($_POST['pem_action']))
{
  $logger->info('no POST pem_action in FILE = '.__FILE__.', LINE = '.__LINE__);
  set_status_header(489);
  exit();
}

if (empty($page['errors']) and isset($_POST['pem_action']) and isset($_POST['submit']))
{
  check_status(ACCESS_CLASSIC);

  // Form submitted for translator
  if ('edit_extension_translation' == $_POST['pem_action'] and isTranslator($user['id']))
  {

    $query = 'SELECT idx_language FROM '.PEM_EXT_TABLE.' WHERE id_extension = '.$current_extension_page_id.';';
    $result = pwg_query($query);
    list($def_language) = mysqli_fetch_array($result);

    $query = '
DELETE
  FROM '.PEM_EXT_TRANS_TABLE.'
  WHERE idx_extension = '.$current_extension_page_id.'
    AND idx_language IN ('.implode(',', $conf['translator_users'][$user['id']]).')
;';
    pwg_query($query);

    $inserts = array();
    $new_default_desc = null;
    foreach ($_POST['descriptions'] as $lang_id => $desc)
    {
      if ($lang_id == $def_language and empty($desc))
      {
        $template->assign(
          array(
            'MESSAGE' => l10n('Default description can not be empty'),
            'MESSAGE_TYPE' => 'error'
          )
        );
        $page['errors'][] = l10n('Default description can not be empty');
        break;
      }
      if (!in_array($lang_id, $conf['translator_users'][$user['id']]) or empty($desc))
      {
        continue;
      }
      if ($lang_id == $def_language)
      {
        $new_default_desc = pwg_db_real_escape_string($desc);
      }
      else
      {
        array_push(
          $inserts,
          array(
            'idx_extension'  => $current_extension_page_id,
            'idx_language'   => $lang_id,
            'description'    => pwg_db_real_escape_string($desc),
            )
          );
      }
    }
    
    if (empty($page['errors']))
    {
      if (!empty($inserts))
      {
        mass_inserts(PEM_EXT_TRANS_TABLE, array_keys($inserts[0]), $inserts);
      }
      if (!empty($new_default_desc))
      {
        $query = '
UPDATE '.PEM_EXT_TABLE.'
  SET description = \''.$new_default_desc.'\'
  WHERE id_extension = '.$current_extension_page_id.'
;';
        pwg_query($query);
      }
      
      // $country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
      // $country_name = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);

      $country_code = 'unkown';
      $country_name = 'unkown';
      
      notify_mattermost('['.$conf['mattermost_notif_type'].'] user #'.$user['id'].' as a translator ('.$user['username'].') updated description for extension #'.$current_extension_page_id.' , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);
      pwg_activity('pem_extension', $current_extension_page_id, 'edit', array('language_id' => $lang_id));

      $message = l10n('Extension translation sucessfully updated');
  
      $template->assign(
        array(
          'MESSAGE' => $message,
          'MESSAGE_TYPE' => 'success'
        )
      );

      unset($_POST);
    }
  }
  else if(in_array($_POST['pem_action'], array('add_ext','edit_general_info')))
  {

    // Checks that all the fields have been well filled
    $required_fields = array(
      'extension_name',
      'extension_category'
    );

    foreach ($required_fields as $field)
    {

      if (empty($_POST[$field]))
      {
        $template->assign(
          array(
            'MESSAGE' => l10n('Some fields are missing'),
            'MESSAGE_TYPE' => 'error'
          )
        );
        $page['errors'][] = l10n('Some fields are missing');
        break;
      }
    }

    //This action comes from the extension page, we need eid
    if ("edit_general_info" == $_POST['pem_action'])
    {
      if (is_admin() or in_array($user['id'], $authors))
      {
        if (empty($_POST['extension_descriptions'][$_POST['default_description']]))
        {
          $template->assign(
            array(
              'MESSAGE' => l10n('Default description can not be empty'),
              'MESSAGE_TYPE' => 'error'
            )
          );
          $page['errors'][] = l10n('Default description can not be empty');
        }

        // Update the extension
        $query = '
UPDATE '.PEM_EXT_TABLE.'
SET name = \''. pwg_db_real_escape_string($_POST['extension_name']) .'\',
    description = \''. pwg_db_real_escape_string($_POST['extension_descriptions'][$_POST['default_description']]) .'\',
    idx_language = '. pwg_db_real_escape_string($_POST['default_description']) .'
WHERE id_extension = '.$current_extension_page_id.'
;';

      pwg_query($query);

        $query = '
DELETE
FROM '.PEM_EXT_TRANS_TABLE.'
WHERE idx_extension = '.$current_extension_page_id.'
;';

      pwg_query($query);

      $query = '
DELETE
FROM '.PEM_EXT_CAT_TABLE.'
WHERE idx_extension = '.$current_extension_page_id.'
;';

      pwg_query($query);

      $query = '
DELETE
FROM '.PEM_EXT_TAG_TABLE.'
WHERE idx_extension = '.$current_extension_page_id.'
;';

      pwg_query($query);

      $post_type ='updated';

      // $country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
      // $country_name = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);

      $country_code = 'unkown';
      $country_name = 'unkown';
  
      notify_mattermost('['.$conf['mattermost_notif_type'].'] user #'.$user['id'].' ('.$user['username'].') updated extension #'.$current_extension_page_id.' ('.$_POST['extension_name'].') , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);
      pwg_activity('pem_extension', $current_extension_page_id, 'edit', array());
    
      }
      else
      {
        $template->assign(
          array(
            'MESSAGE' => 'You must be the extension author or translator to modify it.',
            'MESSAGE_TYPE' => 'error'
          )
        );

        $logger->info('not author on '.$_POST['pem_action'].' in FILE = '.__FILE__.', LINE = '.__LINE__);
        set_status_header(489);
        return;
      }
    }
    // This actions comes from account, we have a uid that is set and not an eid
    else if ("add_ext" == $_POST['pem_action'])
    {
      check_user_activity();

      // Inserts the extension (need to be done before the other includes, to
      // retrieve the insert id
      $insert = array(
        'idx_user'   => $user['id'],
        'name'         => pwg_db_real_escape_string($_POST['extension_name']),
        'description'  => 'Default description',
        'idx_language' => '5',
        );
      mass_inserts(PEM_EXT_TABLE, array_keys($insert), array($insert));
      $current_extension_page_id = pwg_db_insert_id();

      $post_type ='added';
    }

    // Insert translations
    $inserts = array();
    if (isset($_POST['extension_descriptions']))
    {
      foreach ($_POST['extension_descriptions'] as $lang_id => $desc)
      {
        if ($lang_id == $_POST['default_description'] or empty($desc))
        {
          continue;
        }
        array_push(
          $inserts,
          array(
            'idx_extension'  => $current_extension_page_id,
            'idx_language'   => $lang_id,
            'description'    => pwg_db_real_escape_string($desc),
            )
          );
      }
    }

    if (!empty($inserts))
    {
      mass_inserts(PEM_EXT_TRANS_TABLE, array_keys($inserts[0]), $inserts);
    }

    // Inserts the extensions <-> categories link
    $inserts = array();
    foreach ($_POST['extension_category'] as $category)
    {
      array_push(
        $inserts,
        array(
          'idx_category'   => pwg_db_real_escape_string($category),
          'idx_extension'  => $current_extension_page_id,
          )
        );
    }
    mass_inserts(PEM_EXT_CAT_TABLE, array_keys($inserts[0]), $inserts);

    // Inserts the extensions <-> tags link
    if (!empty($_POST['tags']))
    {
      $inserts = array();
      foreach ($_POST['tags'] as $tag)
      {
        array_push(
          $inserts,
          array(
            'idx_tag'   => $tag,
            'idx_extension'  => $current_extension_page_id,
            )
          );
      }
      mass_inserts(PEM_EXT_TAG_TABLE, array_keys($inserts[0]), $inserts);
    }
      
    $message = l10n('Extension successfully '.$post_type.'.');
    if ("add_ext" == $_POST['pem_action'])
    {
      $message .= ' <a href ="'.get_root_url().'index.php?eid='.$current_extension_page_id.'">'.l10n('See it here').'</a>';

      // $country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
      // $country_name = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);

      $country_code = 'unkown';
      $country_name = 'unkown';
  
      notify_mattermost('['.$conf['mattermost_notif_type'].'] user #'.$user['id'].' ('.$user['username'].') added a new extension #'.$current_extension_page_id.' ('.$_POST['extension_name'].') , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);     
      pwg_activity('pem_extension', $current_extension_page_id, 'add', array());

    }

    $template->assign(
      array(
        'MESSAGE' => $message,
        'MESSAGE_TYPE' => 'success'
      )
    );

    unset($_POST);
  
    return;
  }
}

// Gets the available tags
$query = '
SELECT
    id_tag as tid,
    t.name AS default_name,
    tt.name
  FROM '.PEM_TAG_TABLE.' AS t
  LEFT JOIN '.PEM_TAG_TRANS_TABLE.' AS tt
      ON t.id_tag = tt.idx_tag
      AND tt.idx_language = \''.get_current_language_id().'\'
    ORDER BY LOWER(t.name)
;';

$all_tags = query2array($query);

// Gets the available authors
$query = '
SELECT DISTINCT 
    eT.idx_user as uid,
    uT.username
  FROM '.PEM_EXT_TABLE.' as eT
  JOIN '.USERS_TABLE.' as uT on id = eT.idx_user
;';

$owners = query2array($query, 'uid');

$query = '
SELECT DISTINCT
    aT.idx_user as uid,
    uT.username
  FROM '.PEM_AUTHORS_TABLE.' as aT
    JOIN '.USERS_TABLE.' as uT on id = aT.idx_user
;';

$authors= query2array($query, 'uid');

$all_authors = array_merge_recursive($owners, $authors);

$template->assign(
  array(
    'ALL_TAGS' => $all_tags,
    'ALL_AUTHORS' => $all_authors,
  )
);

