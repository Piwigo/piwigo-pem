<?php
// +-----------------------------------------------------------------------+
// |                           Initialization                              |
// +-----------------------------------------------------------------------+

global $user, $logger;

$current_user_page_id = $_GET['uid'];

// +-----------------------------------------------------------------------+
// |                           Form submission                             |
// +-----------------------------------------------------------------------+

if (isset($_POST['pem_action']) and isset($_POST['submit']) and "edit_user_info" == $_POST['pem_action'])
{

  check_status(ACCESS_CLASSIC);
  
  if (isset($user['id']) and $user['id'] == $current_user_page_id)
  {

    $query = '
SELECT '.$conf['user_fields']['id'].' AS id
  FROM '.USERS_TABLE.'
  WHERE '.$conf['user_fields']['id'].' = '. pwg_db_real_escape_string($_POST['user_id']) .'
;';

    list($author_id) = pwg_db_fetch_array(pwg_query($query));

    if (empty($author_id))
    {
      $template->assign(
        array(
          'MESSAGE' => l10n('This user does not exist.'),
          'MESSAGE_TYPE' => 'error'
        )
      );
      $page['errors'][] = l10n('This user does not exist in database.');
    }
    else
    {
      $data = array(
        'username'         => pwg_db_real_escape_string($_POST['user_name']),
        'mail_address'     => pwg_db_real_escape_string($_POST['user_email']),
        'id'               => pwg_db_real_escape_string($_POST['user_id']),
      );

      if(is_numeric($data['id']))
      {   
        single_update(
          USERS_TABLE,
          $data,
          array('id' => $data['id'])
        );

        // $country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
        // $country_name = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);

        $country_code = 'unkown';
        $country_name = 'unkown';

        notify_mattermost('['.$conf['mattermost_notif_type'].'] user #'.$user['id'].' ('.$user['username'].') updated their information , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);
        pwg_activity('pem_user', $user['id'], 'edit', array());

        $template->assign(
          array(
            'MESSAGE' => l10n('User succesfully updated.'),
            'MESSAGE_TYPE' => 'success'
          )
        );
      }
    }
  }
  else
  {
    $template->assign(
      array(
        'MESSAGE' => l10n('You must the current user to modify the information.'),
        'MESSAGE_TYPE' => 'error'
      )
    );

    $logger->info('not author on '.$_POST['pem_action'].' in FILE = '.__FILE__.', LINE = '.__LINE__);
    set_status_header(489);
    return;
  }
}
