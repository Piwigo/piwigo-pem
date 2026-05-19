<?php
global $logger, $user;

$query = '
SELECT
    name,
    idx_user,
    svn_url,
    git_url,
    archive_root_dir,
    archive_name
  FROM '.PEM_EXT_TABLE.'
  WHERE id_extension = '.$_GET['eid'].'
;';
$result = pwg_query($query);

list($page['extension_name'], $ext_user, $svn_url, $git_url, $archive_root_dir, $archive_name) = pwg_db_fetch_array($result);

$authors = get_extension_authors($_GET['eid']);

// +-----------------------------------------------------------------------+
// |                           Form submission                             |
// +-----------------------------------------------------------------------+

if (isset($_POST['pem_action']) and isset($_POST['submit']))
{
  check_status(ACCESS_CLASSIC);
  
    // Form submitted for translator
    if("edit_revision_translation" == $_POST['pem_action'])
    {
      if (!array_key_exists($user['id'], $conf['translator_users']))
      {
        return;
      }

      $query = 'SELECT idx_language FROM '.PEM_REV_TABLE.' WHERE id_revision = '.$_POST['revision_id'].';';
      $result = pwg_query($query);
      list($def_language) = pwg_db_fetch_array($result);

      $query = '
DELETE
  FROM '.PEM_REV_TRANS_TABLE.'
  WHERE idx_revision = '.$_POST['revision_id'].'
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
          $new_default_lang = pwg_db_real_escape_string($desc);
        }
        else
        {
          array_push(
            $inserts,
            array(
              'idx_revision'  => $_POST['revision_id'],
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
          mass_inserts(PEM_REV_TRANS_TABLE, array_keys($inserts[0]), $inserts);
        }
        if (!empty($new_default_desc))
        {
          $query = '
UPDATE '.PEM_REV_TABLE.'
  SET description = \''.$new_default_desc.'\'
  WHERE id_revision = '.$_POST['revision_id'].'
;';
          pwg_query($query);
        }

        // $country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
        // $country_name = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);

        $country_code = 'unkown';
        $country_name = 'unkown';
        
        notify_mattermost('['.$conf['mattermost_notif_type'].'] user #'.$user['id'].' as a translator ('.$user['username'].') updated description for extension #'.$current_extension_page_id.' , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);
        pwg_activity('pem_revision', $_POST['revision_id'], 'edit', array('language_id' => $lang_id));
  
        $template->assign(
          array(
            'MESSAGE' => l10n('Revision translation sucessfully updated'),
            'MESSAGE_TYPE' => 'success'
          )
        );
          
        unset($_POST);
      }
    }
    else if("add_revision" == $_POST['pem_action'] or "edit_revision" == $_POST['pem_action'])
    {
      if (is_admin() or in_array($user['id'], $authors))
      {

      // The file is mandatory only when we add a revision, not when we modify it
      $file_to_upload = null;
      if ("add_revision" == $_POST['pem_action'])
      {
        if (isset($_POST['file_type']) and in_array($_POST['file_type'], array('upload', 'svn', 'git', 'url')))
        {
          $file_to_upload = $_POST['file_type'];
        }
        else
        {
          $template->assign(
            array(
              'MESSAGE' => l10n('Some fields are missing'),
              'MESSAGE_TYPE' => 'error'
            )
          );
          $page['errors'][] = l10n('Some fields are missing');
        }
      }
      else
      {
        // we are on revision_mod.inc.php
        
        $revision_infos_of = get_revision_infos_of(array($_POST['rid']));
        $file_to_upload = 'none';
      }

      if ($file_to_upload == 'upload')
      {
        // Check file extension
        $file_ext = pathinfo($_FILES['revision_file']['name'], PATHINFO_EXTENSION);
        $allowed_extensions = array('zip', 'jar');
        if (!in_array($file_ext, $allowed_extensions))
        {
          $template->assign(
            array(
              'MESSAGE' => l10n('Only *.{'.implode(', ', $allowed_extensions).'} files are allowed'),
              'MESSAGE_TYPE' => 'error'
            )
          );
          $page['errors'][] = l10n('Only *.{'.implode(', ', $allowed_extensions).'} files are allowed');
        }
      
        // Check file size
        else if ($_FILES['revision_file']['error'] == UPLOAD_ERR_INI_SIZE)
        {
          $template->assign(
            array(
              'MESSAGE' => sprintf(l10n('File too big. Filesize must not exceed %s.'),ini_get('upload_max_filesize')),
              'MESSAGE_TYPE' => 'error'
            )
          );
          $page['errors'][] = sprintf(
            l10n('File too big. Filesize must not exceed %s.'),
            ini_get('upload_max_filesize')
            );
        }
        else
        {
          $archive_name = $_FILES['revision_file']['name'];
        }
      }

      if ($file_to_upload == 'svn')
      {
        $svn_url = $_POST['svn_url'];
        if (empty($svn_url))
        {
          $template->assign(
            array(
              'MESSAGE' => l10n('Some fields are missing'),
              'MESSAGE_TYPE' => 'error'
            )
          );
          $page['errors'][] = l10n('Some fields are missing');
        }
        else
        {
          $temp_path = $conf['local_data_dir'] . '/svn_import';
          if (!is_dir($temp_path))
          {
            umask(0000);
            if (!mkdir($temp_path, 0777))
            {
              die("problem during ".$temp_path." creation");
            }
          }

          // Create random path
          $temp_path .= '/' . md5(uniqid(rand(), true));

          // SVN export
          $svn_command = $conf['svn_path'] . ' export';
          $svn_command .= is_numeric($_POST['svn_revision']) ? ' -r'.$_POST['svn_revision'] : '';
          $svn_command .= ' ' . escapeshellarg($svn_url);
          $svn_command .= ' ' . $temp_path;

          exec($svn_command, $svn_infos);

          if (empty($svn_infos))
          {
            $template->assign(
              array(
                'MESSAGE' => l10n('An error occured during SVN/Git export.'),
                'MESSAGE_TYPE' => 'error'
              )
            );
            $page['errors'][] = l10n('An error occured during SVN/Git export.');
          }
          else
          {
            $archive_name = str_replace('%', $_POST['revision_version'], $archive_name);
            $svn_revision = preg_replace('/exported revision (\d+)\./i', '$1', end($svn_infos));

            if (!empty($conf['archive_comment']) and !file_exists($temp_path.'/'.$conf['archive_comment_filename']))
            {
              file_put_contents(
                $temp_path.'/'.$conf['archive_comment_filename'],
                sprintf($conf['archive_comment'], $svn_url, $svn_revision)
              );
            }
          }
        }
      }

      if ($file_to_upload == 'git')
      {
        $git_url = $_POST['git_url'];
        if (empty($git_url))
        {
          $template->assign(
            array(
              'MESSAGE' => l10n('Some fields are missing'),
              'MESSAGE_TYPE' => 'error'
            )
          );
          $page['errors'][] = l10n('Some fields are missing');
        }
        else
        {
          $temp_path = PHPWG_ROOT_PATH.'_data' . '/git_clone';
          if (!is_dir($temp_path))
          {
            umask(0000);
            if (!mkdir($temp_path, 0777))
            {
              die("problem during ".$temp_path." creation");
            }
          }

          // Create random path
          $temp_path .= '/' . md5(uniqid(rand(), true));

          // SVN export
          $git_command = $conf['git_path'] . ' clone --depth=1';
          
          if (isset($_POST['git_branch']) and 'master' != $_POST['git_branch'])
          {
            $git_command .= ' -b '.escapeshellarg($_POST['git_branch']);
          }
          
          $git_command .= ' ' . escapeshellarg($git_url);
          $git_command .= ' ' . $temp_path;

          exec($git_command, $git_infos);

          if (!file_exists($temp_path.'/.git'))
          {
            $template->assign(
              array(
                'MESSAGE' => l10n('An error occured during SVN/Git export.'),
                'MESSAGE_TYPE' => 'error'
              )
            );
            $page['errors'][] = l10n('An error occured during SVN/Git export.');
          }
          else
          {
            $archive_name = str_replace('%', $_POST['revision_version'], $archive_name);

            unset($git_infos);
            
            $working_dir = getcwd();
            chdir($temp_path);
            $git_command = $conf['git_path'].' log ';
            exec($git_command, $git_infos);
            chdir($working_dir);

            exec('rm -rf '.$temp_path.'/.git');

            $git_commit = '';
            $git_date = '';
            foreach ($git_infos as $line)
            {
              $line = trim($line);
              if (preg_match('/commit\s+([a-f0-9]{40})/', $line, $matches))
              {
                $git_commit = $matches[1];
              }

              if (preg_match('/Date:\s*(.*)$/', $line, $matches))
              {
                $git_date = $matches[1];
              }
            }
            
            $revision = $git_commit.' ('.$git_date.')';

            if (!empty($conf['archive_comment']) and !file_exists($temp_path.'/'.$conf['archive_comment_filename']))
            {
              file_put_contents(
                $temp_path.'/'.$conf['archive_comment_filename'],
                sprintf($conf['archive_comment'], $git_url, $revision)
              );
            }
          }
        }
      }

      if ('url' == $file_to_upload)
      {
        $download_url = $_POST['download_url'];
        if (empty($download_url))
        {
          $template->assign(
            array(
              'MESSAGE' => l10n('Some fields are missing'),
              'MESSAGE_TYPE' => 'error'
            )
          );
          $page['errors'][] = l10n('Some fields are missing');
        }
        else
        {
          $sch = parse_url($download_url, PHP_URL_SCHEME);
          if (!in_array($sch, array('http', 'https')))
          {
            $template->assign(
              array(
                'MESSAGE' => l10n('The download URL must start with "http"'),
                'MESSAGE_TYPE' => 'error'
              )
            );
            $page['errors'][] = l10n('The download URL must start with "http"');
          }
          else
          {
            $headers = get_headers($download_url, 1);
            if ($headers["Content-Length"] > $conf['download_url_max_filesize']*1024*1024)
            {
              $template->assign(
                array(
                  'MESSAGE' => l10n('The archive on the download URL is bigger than '.$conf['download_url_max_filesize'].'MB'),
                  'MESSAGE_TYPE' => 'error'
                )
              );
              $page['errors'][] = l10n('The archive on the download URL is bigger than '.$conf['download_url_max_filesize'].'MB');
            }
            else
            {
              $archive_name = basename($download_url);
            }
          }
        }
      }

      $required_fields = array(
        'revision_version',
        'compatible_versions',
        );
      
      foreach ($required_fields as $field)
      {
        
        if (empty($_POST[$field]))
        {
          // rmdir($temp_path);
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
      if (empty($_POST['revision_descriptions'][$_POST['default_description']]))
      {
        // rmdir($temp_path);
        $template->assign(
          array(
            'MESSAGE' => l10n('Default description can not be empty'),
            'MESSAGE_TYPE' => 'error'
          )
        );
        $page['errors'][] = l10n('Default description can not be empty');
      }

      if (empty($page['errors']))
      {

        /* Begin specific piwigo website */
        if (in_array($file_to_upload, array('svn', 'git')))
        {    
          $query = 'SELECT idx_category FROM '.PEM_EXT_CAT_TABLE.' WHERE idx_extension = '.$_GET['eid'].';';
          $result = pwg_query($query);
          list($extension_category) = pwg_db_fetch_array($result);
          if ($extension_category == '12' and file_exists($temp_path.'/main.inc.php'))
          {
            // Check extension category (plugins => 12)
            $main = file_get_contents($temp_path.'/main.inc.php');
            $modified = false;
            
            if (preg_match("|Version:(.*)|", $main, $val)
                and trim($val[1]) != $_POST['revision_version'])
            {
              $main = preg_replace("|Version: .*|", "Version: ".$_POST['revision_version'], $main);
              $modified = true;
            }
            
            if (preg_match("#define\((?:'|\")(.*)_VERSION(?:'|\"),(?:\s*)(?:'|\")(.*)(?:'|\")\);#", $main, $val)
                and trim($val[2]) != $_POST['revision_version'])
            {
              $main = preg_replace(
                "#define\((?:'|\")(.*)_VERSION(?:'|\"),(?:\s*)(?:'|\")(.*)(?:'|\")\);#",
                "define('$1_VERSION', '".$_POST['revision_version']."');",
                $main
                );
              
              $modified = true;
            }
            
            if (preg_match("|Plugin URI:(.*)|", $main, $val)
                and trim($val[1]) != 'https://piwigo.org/ext/extension_view.php?eid='.$_GET['eid'])
            {
              $main = preg_replace(
                "|Plugin URI: .*|",
                "Plugin URI: https://piwigo.org/ext/extension_view.php?eid=".$_GET['eid'],
                $main
                );
              
              $modified = true;
            }
            
            if ($modified)
            {
              file_put_contents($temp_path.'/main.inc.php', $main);
            }
          }

          // Check extension category (themes => 10)
          if ($extension_category == '10' and file_exists($temp_path.'/themeconf.inc.php'))
          {
            $themeconf = file_get_contents($temp_path.'/themeconf.inc.php');
            $modified = false;

            if (preg_match("|Version:(.*)|", $themeconf, $val)
                and trim($val[1]) != $_POST['revision_version'])
            {
              $themeconf = preg_replace("|Version: .*|", "Version: ".$_POST['revision_version'], $themeconf);
              $modified = true;
            }
            if (preg_match("|Theme URI:(.*)|", $themeconf, $val)
                and trim($val[1]) != 'https://piwigo.org/ext/extension_view.php?eid='.$_GET['eid'])
            {
              $themeconf = preg_replace(
                "|Theme URI: .*|",
                "Theme URI: https://piwigo.org/ext/extension_view.php?eid=".$_GET['eid'],
                $themeconf
                );
              
              $modified = true;
            }
            
            if ($modified)
            {
              file_put_contents($temp_path.'/themeconf.inc.php', $themeconf);
            }
          }

          // Check extension category (languages => 8)
          if ($extension_category == '8' and file_exists($temp_path.'/common.lang.php'))
          {
            $common = file_get_contents($temp_path.'/common.lang.php');
            $modified = false;

            if (preg_match("|Version:(.*)|", $common, $val)
                and trim($val[1]) != $_POST['revision_version'])
            {
              $common = preg_replace("|Version: .*|", "Version: ".$_POST['revision_version'], $common);
              $modified = true;
            }
            
            if (preg_match("|Language URI:(.*)|", $common, $val)
                and trim($val[1]) != 'https://piwigo.org/ext/extension_view.php?eid='.$_GET['eid'])
            {
              $common = preg_replace(
                "|Language URI: .*|",
                "Language URI: https://piwigo.org/ext/extension_view.php?eid=".$_GET['eid'],
                $common
                );
              
              $modified = true;
            }
            
            if ($modified)
            {
              file_put_contents($temp_path.'/common.lang.php', $common);
            }
          }
        }
        /* End specific piwigo website */
        if ("edit_revision" == $_POST['pem_action'])
        {

          mass_updates(
            PEM_REV_TABLE,
            array(
              'primary' => array('id_revision'),
              'update'  => array('version', 'description', 'idx_language', 'author'),
              ),
            array(
              array(
                'id_revision'    => $_POST['rid'],
                'version'        => pwg_db_real_escape_string($_POST['revision_version']),
                'description'    => pwg_db_real_escape_string($_POST['revision_descriptions'][$_POST['default_description']]),
                'idx_language'   => pwg_db_real_escape_string($_POST['default_description']),
                'author'         => isset($_POST['author']) ? pwg_db_real_escape_string($_POST['author']) : $revision_infos_of[$_POST['rid']]['author'],
                ),
              )
            );
          $query = '
DELETE
  FROM '.PEM_REV_TRANS_TABLE.'
  WHERE idx_revision = '.$_POST['rid'].'
;';
          pwg_query($query);
        }
        else
        {

          $insert = array(
            'version'        => pwg_db_real_escape_string($_POST['revision_version']),
            'idx_extension'  => $_GET['eid'],
            'date'           => time(),
            'description'    => pwg_db_real_escape_string($_POST['revision_descriptions'][$_POST['default_description']]),
            'idx_language'   => pwg_db_real_escape_string($_POST['default_description']),
            'url'            => $archive_name,
            'author'         => isset($_POST['author']) ? pwg_db_real_escape_string($_POST['author']) : $user['id'],
            );

            if ($conf['use_agreement'])
          {
            $insert['accept_agreement'] = isset($_POST['accept_agreement'])
              ? 'true'
              : 'false'
              ;
          }
          
          mass_inserts(
            PEM_REV_TABLE,
            array_keys($insert),
            array($insert)
            );

          $_POST['rid'] = pwg_db_insert_id();
        }

        if ($file_to_upload != 'none')
        {
          // Moves the file to its final destination:
          // upload/extension-X/revision-Y
          $extension_dir = $conf['upload_dir'].'extension-'.$_GET['eid'];
          $revision_dir = $extension_dir.'/revision-'.$_POST['rid'];
          
          if (!is_dir($extension_dir))
          {
            umask(0000);
            if (!mkdir($extension_dir, 0777, true))
            {
              die("problem during ".$extension_dir." creation");
            }
          }
          
          umask(0000);
          mkdir($revision_dir, 0777);

          if ($file_to_upload == 'upload')
          {
            move_uploaded_file(
              $_FILES['revision_file']['tmp_name'],
              $revision_dir.'/'.$_FILES['revision_file']['name']
            );
          }
          elseif (in_array($file_to_upload, array('svn', 'git')))
          {
            // Create zip archive
            //
            // We need to create a temporary directory _data/zip_create/1234 and inside it create a
            // symbolic link with the name of the archive root directory, as configured by the user
            // in the SVN/Git setting. This will let us create a zip archive with the root directory
            // as we like.
            $zip_create_uniqdir = $conf['local_data_dir'].'/zip_create/'.uniqid();
            $cmd = 'mkdir -p '.$zip_create_uniqdir.' && ln -s '.realpath($temp_path).' '.$zip_create_uniqdir.'/'.$archive_root_dir;
            $logger->info($cmd);
            exec($cmd);

            $zip_path = getcwd().'/'.$revision_dir.'/'.$archive_name;
            $cmd = 'cd '.$zip_create_uniqdir.' && zip -r '.$zip_path.' '.$archive_root_dir;
            $logger->info($cmd);
            exec($cmd);

            include_once(PHPWG_ROOT_PATH.'admin/include/pclzip.lib.php');
            $zip = new PclZip($revision_dir.'/'.$archive_name);

            /* Begin specific piwigo website */
            // Get obsolete list
            if (($extension_category == '12' or $extension_category == '10')
              and !file_exists($temp_path.'/obsolete.list'))
            {
              $list = $zip->listContent();
              $archive_files = array();
              $len = strlen($archive_root_dir) + 1;
              $main_file = ($extension_category == '12' ? 'main.inc.php' : 'themeconf.inc.php');
              foreach ($list as $file)
              {
                array_push($archive_files, substr($file['filename'], $len));
              }

              $query = '
SELECT id_revision,
      url
  FROM '.PEM_REV_TABLE.'
  WHERE idx_extension = '.$_GET['eid'].'
;';
              $result = pwg_query($query);
              $obsolete = array();
              while ($row = pwg_db_fetch_assoc($result))
              {
                if ($arch = new PclZip(get_revision_src($_GET['eid'], $row['id_revision'], $row['url']))
                  and $list = $arch->listContent())
                {
                  foreach ($list as $file)
                  {
                    // we search main.inc.php in archive
                    if (basename($file['filename']) == $main_file
                      and (!isset($main_filepath)
                      or strlen($file['filename']) < strlen($main_filepath)))
                    {
                      $main_filepath = $file['filename'];
                    }
                  }
                  if (isset($main_filepath))
                  {
                    $root = dirname($main_filepath);

                    if ('.' == $root)
                    {
                      // when there is no root directory in the archive
                      $len = 0;
                    }
                    else
                    {
                      $len = strlen($root) + 1;
                    }

                    foreach ($list as $file)
                    {
                      // if the file is not in the same directory than the "main"
                      // file, we can't decide
                      if (!preg_match('#^'.$root.'#', $file['filename'])) {
                        continue;
                      }
                      
                      $filename = substr($file['filename'], $len);
                      if (!in_array($filename, $archive_files) and $filename != 'obsolete.list')
                      {
                        array_push($obsolete, $filename);
                      }
                    }
                  }
                }
                unset($arch, $main_filepath);
              }
              if (!empty($obsolete))
              {
                $filename = $temp_path.'/obsolete.list';
                $obsolete = array_unique($obsolete);
                file_put_contents($filename, implode("\n", $obsolete));
                $cmd = 'cd '.$zip_create_uniqdir.' && zip '.$zip_path.' '.$archive_root_dir.'/obsolete.list';
                exec($cmd);
              }
            }
            /* End specific piwigo website */

            @rmdir($temp_path);
            @rmdir($zip_create_uniqdir);
          }
          elseif ('url' == $file_to_upload)
          {
            copy($download_url, $revision_dir.'/'.$archive_name);
          }
        }

        // Insert translations
        $inserts = array();
        foreach ($_POST['revision_descriptions'] as $lang_id => $desc)
        {
          if ($lang_id == $_POST['default_description'] or empty($desc))
          {
            continue;
          }
          array_push(
            $inserts,
            array(
              'idx_revision'  => $_POST['rid'],
              'idx_language'   => $lang_id,
              'description'    => pwg_db_real_escape_string($desc),
              )
            );
        }
        if (!empty($inserts))
        {
          mass_inserts(PEM_REV_TRANS_TABLE, array_keys($inserts[0]), $inserts);
        }

        $query = '
DELETE
  FROM '.PEM_COMP_TABLE.'
  WHERE idx_revision = '.$_POST['rid'].'
;';
        pwg_query($query);
        
        // Inserts the revisions <-> compatibilities link
        $inserts = array();
        foreach ($_POST['compatible_versions'] as $version_id)
        {
          array_push(
            $inserts,
            array(
              'idx_revision'  => $_POST['rid'],
              'idx_version'   => pwg_db_real_escape_string($version_id),
              )
            );
        }
        mass_inserts(
          PEM_COMP_TABLE,
          array_keys($inserts[0]),
          $inserts
          );

        $query = '
DELETE
  FROM '.PEM_REV_LANG_TABLE.'
  WHERE idx_revision = '.$_POST['rid'].'
;';
        pwg_query($query);

        // Inserts the revisions <-> languages
        $inserts = array();
        if (!empty($_POST['revision_languages']))
        {
          foreach ($_POST['revision_languages'] as $language_id)
          {
            array_push(
              $inserts,
              array(
                'idx_revision'  => $_POST['rid'],
                'idx_language'  => pwg_db_real_escape_string($language_id),
                )
              );
          }
          mass_inserts(
            PEM_REV_LANG_TABLE,
            array_keys($inserts[0]),
            $inserts
            );
        }

          // $country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
          // $country_name = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);

          $country_code = 'unkown';
          $country_name = 'unkown';

        if ("add_revision" == $_POST['pem_action'])
        {
          notify_mattermost('['.$conf['mattermost_notif_type'].'] user #'.$user['id'].' ('.$user['username'].') added a new revision #'.$_POST['rid'].'('.$_POST['revision_version'].') for extension #'.$_GET['eid'].'('.$page['extension_name'].') , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);
          pwg_activity('pem_revision', $_POST['rid'], 'add', array('extension' => $_GET['eid']));

          $template->assign(
            array(
              'MESSAGE' => l10n('Revision successfully added.'),
              'MESSAGE_TYPE' => 'success'
            )
          );
        }
        else if ("edit_revision" == $_POST['pem_action'])
        {
          notify_mattermost('['.$conf['mattermost_notif_type'].'] user #'.$user['id'].' ('.$user['username'].') updated revision #'.$_POST['rid'].' ('.$_POST['revision_version'].') for extension #'.$_GET['eid'].' ('.$page['extension_name'].') , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);
          pwg_activity('pem_revision', $_POST['rid'], 'edit', array('extension' => $_GET['eid']));

          $template->assign(
            array(
              'MESSAGE' => l10n('Revision successfully modified.'),
              'MESSAGE_TYPE' => 'success'
            )
          );
        }

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
}

$version = isset($_POST['revision_version']) ? $_POST['revision_version'] : '';
$descriptions = isset($_POST['revision_descriptions']) ? $_POST['revision_descriptions'] : array();
$selected_versions = isset($_POST['compatible_versions']) ? $_POST['compatible_versions'] : array();
$selected_author = isset($_POST['author']) ? $_POST['author'] : $user['id'];
$selected_languages = isset($_POST['revision_languages']) ? $_POST['revision_languages'] : array();


?>
