 <?php

// +-----------------------------------------------------------------------+
// |                           Initialization                              |
// +-----------------------------------------------------------------------+

global $template, $user, $logger;

// +-----------------------------------------------------------------------+
// |                           Form submission                             |
// +-----------------------------------------------------------------------+
if (isset($_POST['pem_action']) and isset($_POST['submit']) and "edit_authors" == $_POST['pem_action'])
{
  check_status(ACCESS_CLASSIC);

  //Get list of extension authors
  $authors = get_extension_authors($current_extension_page_id);

  if (is_admin() or in_array($user['id'], $authors))
  {
    // $authors = implode(',',$_POST['authors']);
    $query = '
SELECT '.$conf['user_fields']['id'].' AS id
  FROM '.USERS_TABLE.'
  WHERE '.$conf['user_fields']['id'].' = '. pwg_db_real_escape_string($_POST['author']) .'
;';

    list($author_id) = pwg_db_fetch_array(pwg_query($query));

    if (empty($author_id))
    {
      $page['errors'][] = l10n('This user does not exist in database.');
    }
    else
    {
      $authors = get_extension_authors($_GET['eid']);

      if (!in_array($author_id, $authors))
      {
        $query = '
INSERT INTO '.PEM_AUTHORS_TABLE.' (idx_extension, idx_user)
  VALUES ('.$_GET['eid'].', '.$author_id.')
;';
        pwg_query($query);
      }

      // $country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
      // $country_name = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);

      $country_code = 'unkown';
      $country_name = 'unkown';
      
      notify_mattermost('['.$conf['mattermost_notif_type'].'] user #'.$user['id'].' ('.$user['username'].') updated authors for extension #'.$_GET['eid'].' , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);
      pwg_activity('pem_author', $author_id, 'add', array('extension' => $_GET['eid']));

      $template->assign(
        array(
          'MESSAGE' => l10n('Extension authors successfully updated.'),
          'MESSAGE_TYPE' => 'success'
        )
      );

      unset($_POST);
    }
  }
  else
  {
    $template->assign(
      array(
        'MESSAGE' => l10n('You must be the extension author to modify it.'),
        'MESSAGE_TYPE' => 'error'
      )
    );

    $logger->info('not author on '.$_POST['pem_action'].' in FILE = '.__FILE__.', LINE = '.__LINE__);
    set_status_header(489);
    return;
  }
}

?>