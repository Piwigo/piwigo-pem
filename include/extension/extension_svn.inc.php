<?php

// +-----------------------------------------------------------------------+
// |                           Initialization                              |
// +-----------------------------------------------------------------------+

global $user, $logger;

// +-----------------------------------------------------------------------+
// |                           Form submission                             |
// +-----------------------------------------------------------------------+

if (isset($_POST['pem_action']) and isset($_POST['submit']))
{

  check_status(ACCESS_CLASSIC);

  if (is_admin() or in_array($user['id'], $authors))
  {
    $query = '
SELECT svn_url, git_url, archive_root_dir, archive_name
  FROM '.PEM_EXT_TABLE.'
  WHERE id_extension = '.$_GET['eid'].'
;';
  $result = pwg_query($query);

  list($svn_url, $git_url, $root_dir, $archive_name) = pwg_db_fetch_array($result);

    // echo('<pre>');print_r(($_POST));echo('</pre>');
    if ("edit_svn_git" == $_POST['pem_action'])
    {
      if (!in_array($_POST['type'], array('svn', 'git')))
      {
        die("unexpected repository type, either svn or git");
      }

      $url = pwg_db_real_escape_string($_POST['url']);
      
      if (empty($svn_url) and empty($git_url))
      { 
        $root_dir = ltrim(strrchr(rtrim($url, '/\\'), '/'), '/\\');
        $root_dir = str_replace(".git","", $root_dir);
        $archive_name = $root_dir . '_%.zip';
      }
      else
      {
        if (preg_match('/[^a-z0-9_-]/i', $_POST['root_dir']))
        {
          $template->assign(
            array(
              'MESSAGE' => l10n('Characters not allowed in archive root directory.'),
              'MESSAGE_TYPE' => 'error'
            )
          );
          $page['errors'][] = l10n('Characters not allowed in archive root directory.');
        }
        if (preg_match('/[^a-z0-9_\-%\.]/i', $_POST['archive_name']))
        {
          $template->assign(
            array(
              'MESSAGE' => l10n('Characters not allowed in archive root directory.'),
              'MESSAGE_TYPE' => 'error'
            )
          );
          $page['errors'][] = l10n('Characters not allowed in archive root directory.');
        }

        $root_dir = pwg_db_real_escape_string($_POST['root_dir']);
        $archive_name = pwg_db_real_escape_string($_POST['archive_name']);

        $extension = substr(strrchr($_POST['archive_name'], '.' ), 1, strlen($_POST['archive_name']));
        if ($extension != 'zip')
        {
          $archive_name .= '.zip';
        }
      }

      if (empty($page['errors']))
      {
        // first we reset both URLs
        $query = '
UPDATE '.PEM_EXT_TABLE.'
  SET svn_url = NULL
    , git_url = NULL
  WHERE id_extension = '.$_GET['eid'].'
;';
        pwg_query($query);

        $query = '
UPDATE '.PEM_EXT_TABLE.'
SET '.$_POST['type'].'_url = "'.$url.'",
    archive_root_dir = "'.$root_dir.'",
    archive_name = "'.$archive_name.'"
WHERE id_extension = '.$_GET['eid'].'
;';

        pwg_query($query);

        list($svn_url, $git_url) = array(null,null);
        if ('svn' == $_POST['type'])
        {
          $svn_url = $url;
        }
        elseif ('git' == $_POST['type'])
        {
          $git_url = $url;
        }

        // $country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
        // $country_name = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);

        $country_code = 'unkown';
        $country_name = 'unkown';
    
        notify_mattermost('['.$conf['mattermost_notif_type'].'] user #'.$user['id'].' ('.$user['username'].') updated svn/git for extension #'.$current_extension_page_id.' , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);
        pwg_activity('pem_svn_git', $_GET['eid'], 'edit', array('extension' => $_GET['eid']));

        $template->assign(
          array(
            'MESSAGE' => 'SVN/Git information succesfully updated.',
            'MESSAGE_TYPE' => 'success'
          )
        );

        unset($_POST);
      }
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