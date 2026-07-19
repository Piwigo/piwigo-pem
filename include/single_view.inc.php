<?php
if (isset($_GET['eid']) && 1 == count($_GET))
{
  global $conf, $user;

// +-----------------------------------------------------------------------+
// |                         Include functionnalites                       |
// +-----------------------------------------------------------------------+

  // include_once(PEM_PATH . 'include/functions_language.inc.php');

  // For general information modal
  include_once(PEM_PATH . 'include/extension/extension_mod.inc.php');

  // For edit image modal
  include_once(PEM_PATH . 'include/extension/extension_screenshot.inc.php');

  // For authors modal
  include_once(PEM_PATH . 'include/extension/extension_authors.inc.php');

  // For links
  include_once(PEM_PATH . 'include/extension/extension_links.inc.php');

  // For SVN & Git
  include_once(PEM_PATH . 'include/extension/extension_svn.inc.php');

  // For add revision modal
  include_once(PEM_PATH . 'include/revision/revision_add.inc.php');

// +-----------------------------------------------------------------------+
// |                         Get extension infos                           |
// +-----------------------------------------------------------------------+
  $current_extension_page_id = $_GET['eid'];

  // Get current language id, transition to PEM plugin means that language id is the language code in the old PEM database
  // Using this we get the number id from PEM_LANG_TABLE
  $query = '
  SELECT 
    id_language
    FROM '.PEM_LANG_TABLE.'
      WHERE code = "'.$user['language'].'"
  ';
  $result = query2array($query);
  $id_language = $result[0]['id_language'];

  $self_url = PEM_PATH.'index.php?eid='.$current_extension_page_id;

  $data = get_extension_infos_of($current_extension_page_id);

  // If extension exists get info
  if (!isset($data['id_extension']))
  {
    http_response_code(404);
    $template->set_filenames(array('pem_page' => realpath(PEM_PATH . 'template/404.tpl')));

    $template->assign(
      array(
        'MESSAGE' => l10n('Sorry, this extension doesn\'t exist.'),
      )
    );
  }
  else
  {
    $template->assign(array('CURRENT_LANG' => $id_language, ));

    //Get List of versions for filter
    $query = '
    SELECT 
        id_version,
        version
      FROM '.PEM_VER_TABLE.'
      ORDER BY id_version DESC
    ;';
      $versions_of_pwg= query2array($query, 'id_version');

      $template->assign(array('VERSIONS_PWG' => $versions_of_pwg, ));

    $authors = get_extension_authors($current_extension_page_id);
    $add_current_user = (in_array($user['id'],$authors))? false :true;
    $owner_id = null;

    foreach ($authors as $key => $author)
    {
      $temp = array (
        "uid" => $author,
        "username" => get_author_name($author),
        "owner" => $author == $data['idx_user'] ? true : false,
      );
      $author == $data['idx_user'] ? $owner_id = $data['idx_user'] : $owner_id = null;

      $authors[$key] = $temp;
    }

    $template->assign(
      array(
        'owner_id' => $owner_id,
      )
    );

    $template->assign(
      array(
        'current_user_id' => $user['id'],
        'current_user_name' => get_author_name($user['id']),
        'add_current_user' => $add_current_user,
        'PWG_TOKEN' => get_pwg_token(),
      )
    );

    //Check if user is extension owner
    $user['extension_owner'] = false;
    if (isset($user['id']) and (is_admin($user['id']) or $user['id'] == $data['idx_user']))
    {
      $user['extension_owner'] = true;
    }

    // Get extension versions
    $versions_of_extension = get_versions_of_extension(
      array($current_extension_page_id)
    );

    // Get extension category
    $categories_of_extension = get_categories_of_extension(
      array($current_extension_page_id)
    );

    $categories_of_extension[$current_extension_page_id]['plural_name'] = $categories_of_extension[$current_extension_page_id]['name'] .'s';

    // Get extension tag
    $tags_of_extension = get_tags_of_extension(
      array($current_extension_page_id)
    );

    if (!empty($tags_of_extension))
    {
      $tag_ids_of_extension = array();
      foreach($tags_of_extension[$current_extension_page_id] as $tag)
      {
        array_push($tag_ids_of_extension, $tag['id_tag']);
      }
    }
    
    // Get download statistics
    $extension_downloads = get_download_of_extension(array($current_extension_page_id));

    // Get extension descriptions for all languages that exist
    $descriptions_of_extension = array();

    $query = '
    SELECT
        idx_language as id_lang,
        description
      FROM '.PEM_EXT_TRANS_TABLE.'
      WHERE idx_extension = '.$current_extension_page_id.'
    ;';

    $ext_descriptions_translations = query2array($query);

    $query = '
    SELECT
        idx_language as id_lang,
        description
      FROM '.PEM_EXT_TABLE.'
      WHERE id_extension = '.$current_extension_page_id.'
    ;';
    $default_ext_description = query2array($query);
    $default_ext_description[0]['default'] = true;

    $ext_descriptions = array_merge($ext_descriptions_translations, $default_ext_description);

    foreach ($ext_descriptions as $key => $ext_description)
    {
      $ext_descriptions[$key]['description'] = strip_tags(stripslashes($ext_description['description']));
    }

    $json_descriptions = json_encode($ext_descriptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    $escaped_json_descriptions = htmlspecialchars($json_descriptions, ENT_QUOTES, 'UTF-8');
    //Check if extension is one of those embedded in Piwigo
    $is_in_embedded_ext_array = false;

    if (isset($conf['embedded_extensions']) and in_array($current_extension_page_id, $conf['embedded_extensions']))
    {
      $is_in_embedded_ext_array = true;
    }

    // Send extension data to template
    $template->assign(
      array(
        'extension_id' => $current_extension_page_id,
        'extension_name' => htmlspecialchars(strip_tags(stripslashes($data['name']))),
        'descriptions' =>$ext_descriptions,
        'json_descriptions' => $escaped_json_descriptions,
        'default_description' =>
        htmlspecialchars(
          stripslashes(
            str_replace('"', "'", $data['default_description'])
          )
        ),
        'authors' => $authors,
        'first_date' => l10n('no revision yet'),
        'last_date'  => l10n('no revision yet'),
        'compatible_with' => implode(
            ', ',
            $versions_of_extension[$current_extension_page_id]
          ),
        'latest_compatible_version' => end($versions_of_extension[$current_extension_page_id]),
        'extension_downloads' => $extension_downloads[$current_extension_page_id],
        'extension_categories' => $categories_of_extension[$current_extension_page_id],
        'extension_tags' => empty($tags_of_extension[$current_extension_page_id]) ? array() : $tags_of_extension[$current_extension_page_id],
        'extension_tag_ids' => empty($tag_ids_of_extension)? array() : $tag_ids_of_extension,
        'is_in_embedded_ext_array' => $is_in_embedded_ext_array,
      )
    );

    //Check if user can make changes to extension, only for authors, owners and admins
    $page['user_can_modify'] = false;

    $author_ids = array();
    foreach($authors as $author)
    {
      array_push($author_ids, $author['uid']);
    }

    if (isset($user['id']) and (is_admin() or $user['extension_owner'] or in_array($user['id'], $author_ids)))
    {
      $page['user_can_modify'] = true;
    }

    $extension_infos_of = get_extension_infos_of($current_extension_page_id);

    // If user can modify send this info to template
    if (isset($user['id']))
    {
      // See if user can modifiy page, check if user is admin
      $template->assign(
        array(
          'can_modify' => $page['user_can_modify'],
          'admin' => get_user_status() =='admin' or get_user_status() =='webmaster',
        )
      );
      //Know if the user is the user owner
      if ($user['extension_owner'])
      {
        $template->assign(
          array(
            'u_owner' => true,
          )
        );
      }

      $template->assign(
        array(
          'u_owner_id' => $extension_infos_of['idx_user'],
        )
      );

      $allow_svn_file_creation = conf_get_param('allow_svn_file_creation',false);

      if ($allow_svn_file_creation and $user['extension_owner'])
      {
        $template->assign('u_svn', 'extension_svn.php?eid='.$current_extension_page_id);
          
      }
    }

    // If image is set send to template
    if ($screenshot_infos = get_extension_screenshot_infos($current_extension_page_id))
    {
      $template->assign(
        'screenshot', $screenshot_infos['screenshot_url'],    
      );
    }

    /**
     * Get links infos
     */

    // Links associated to the current extension
    $tpl_all_extension_links = array();

    // if the extension is hosted on github, add a link to the Github page
    if (isset($data['git_url']) and preg_match('/github/', $data['git_url']))
    {
      array_push(
        $tpl_all_extension_links,
        array(
          'id_link' => 'git',
          'name' => l10n('Github page'),
          'url' => $data['git_url'],
          'language' => l10n("All languages"),
          'description' => l10n('source code, bug/request tracker'),
        )
      );
    }
    
    // if the extension is hosted on piwigo.rg SVN repo, add a link to Trac
    if (isset($data['svn_url']) and preg_match('#piwigo.org/svn/extensions#', $data['svn_url']))
    {
      array_push(
        $tpl_all_extension_links,
        array(
          'id_link' => 'svn',
          'name' => l10n('Subversion page'),
          'url' => $data['svn_url'], // str_replace('piwigo.org/svn/extensions', 'piwigo.org/dev/browser/extensions', $data['svn_url']),
          'language' => l10n("All languages"),
          'description' => l10n('source code'),
        )
      );
    }

    $query = '
    SELECT id_link,
          lT.name,
          lT.url,
          lT.description,
          lT.idx_language as id_lang,
          iT.name as lang,
          lT.rank
      FROM '.PEM_LINKS_TABLE.' as lT
      LEFT JOIN '.PEM_LANG_TABLE.' as iT
        ON lT.idx_language = iT.id_language
        WHERE idx_extension = '.$current_extension_page_id.'
        ORDER BY lT.rank ASC
    ;';
    $result = pwg_query($query);

    while ($row = pwg_db_fetch_assoc($result))
    {
      array_push(
        $tpl_all_extension_links,
        array(
          'id_link' => $row['id_link'],
          'name' => $row['name'],
          'url' => $row['url'],
          'id_lang' => $row['id_lang'],
          'language' => (0 == $row['id_lang']) ? l10n("All languages") :$row['lang'],
          'rank' => $row['rank'],
        )
      );
    }
    $template->assign('links', $tpl_all_extension_links);

    /**
     * Get SVN GIT infos
     */

     $query = '
SELECT 
    svn_url,
    git_url,
    archive_root_dir,
    archive_name
  FROM '.PEM_EXT_TABLE.'
  WHERE id_extension = '.$_GET['eid'].'
;';
    $result = pwg_query($query);

    list($svn_url, $git_url, $root_dir, $archive_name) = pwg_db_fetch_array($result);

    $show_repo_infos = false;
    if (!empty($svn_url))
    {
      $show_repo_infos = true;
      $url = $svn_url;
    }
    elseif (!empty($git_url) and preg_match('/github/', $git_url))
    {
      $show_repo_infos = true;
      $url = $git_url;
    }

    if ($show_repo_infos)
    {
      exec($conf['svn_path'].' info '.escapeshellarg($url), $svn_infos);

      if (empty($svn_infos))
      {
        $svn_infos = array(l10n('Unable to retrieve SVN data!'));
      }

      $template->assign(
        array(
          'SVN_INFOS' => $svn_infos,
        )
      );
    }

    $template->assign(
      array(
        'ROOT_DIR' => $root_dir,
        'ARCHIVE_NAME' => $archive_name,
      )
    );

    if (!empty($git_url))
    {
      $template->assign(
        array(
          'TYPE' => 'git',
          'GIT_URL' => $git_url,
          'GIT_BRANCH' => 'master',

          )
        );
    }
    else
    {
      $template->assign(
        array(
          'TYPE' => 'svn',
          'SVN_URL' => $svn_url,
          'SVN_REVISION' => 'HEAD',

          )
        );
    }

    /**
     * Get revisions info
     */

    // which revisions to display?
    $revision_ids = array();

    $query = '
    SELECT id_revision
      FROM '.PEM_REV_TABLE.' r
        LEFT JOIN '.PEM_COMP_TABLE.' c ON c.idx_revision = r.id_revision
        INNER JOIN '.PEM_EXT_TABLE.' e ON e.id_extension = r.idx_extension
      WHERE id_extension = '.$current_extension_page_id;

    if (isset($_SESSION['filter']['id_version']))
    {
      $query.= '
        AND idx_version = '.$_SESSION['filter']['id_version'];
    }
      
    $query.= '
    ;';
    $revision_ids = query2array($query, null, 'id_revision');

    $tpl_revisions = array();

    if (count($revision_ids) > 0)
    {
      $versions_of = get_versions_of_revision($revision_ids);
      $languages_of = get_languages_of_revision($revision_ids);
      $downloads_of_revision = get_download_of_revision($revision_ids);

      $diff_languages_of = get_diff_languages_of_extension($current_extension_page_id);

      $rev_languages_of_ids = array();
      foreach($languages_of as $key => $rev){
        $rev_languages_of_ids[$key] = array();
        foreach($rev as $rev_lang)
        {
          array_push(
            $rev_languages_of_ids[$key], $rev_lang['id_language']  
          );
        }
      }

      $template->assign(
        array(
          'all_rev_languages_of_ids' => json_encode($rev_languages_of_ids,JSON_NUMERIC_CHECK),
          'count_rev' => count($revision_ids),
        )
      );

      $revisions = array();

      $query = '
    SELECT id_revision,
          version,
          r.description as default_description,
          FROM_UNIXTIME(date) AS date,
          url,
          author
      FROM '.PEM_REV_TABLE.' AS r
      WHERE id_revision IN ('.implode(',', $revision_ids).')
      ORDER by date DESC
    ;';

      $first_date = '';

      $is_first_revision = true;
      $result = pwg_query($query);  
      while ($row = pwg_db_fetch_assoc($result))
      {
        if (!isset($last_date_set))
        {
          $template->assign(array(
            'last_date' => explode(' ', $row['date'])[0],
            'last_date_formatted_since' => time_since($row['date'], $stop='month'),
            'download_last_url' => PHPWG_ROOT_PATH.'download.php?rid='.$row['id_revision'],
            ));
          $last_date_set = true;
        }

        $is_first_revision = false;
        $ids_versions_compatible = get_version_ids_of_revision([$row['id_revision']]);

        // Get revision descriptions
        $descriptions_of_revision = array();

        $query = '
SELECT
    idx_language as id_lang,
    description
  FROM '.PEM_REV_TRANS_TABLE.'
  WHERE idx_revision = '.$row['id_revision'].'
;';
        $rev_descriptions_translations = query2array($query);

        $query = '
SELECT
    idx_language as id_lang,
    description
  FROM '.PEM_REV_TABLE.'
  WHERE id_revision = '.$row['id_revision'].'
;';
        $default_rev_description = query2array($query);
        $default_rev_description[0]['default'] = true;

        $rev_descriptions = array_merge($rev_descriptions_translations, $default_rev_description);

        foreach ($rev_descriptions as &$rev_description)
        {
          $rev_description['description'] = strip_tags(stripslashes($rev_description['description']));
        }

        $json_rev_descriptions = json_encode($rev_descriptions, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $escaped_json_rev_descriptions = htmlspecialchars($json_rev_descriptions, ENT_QUOTES, 'UTF-8');

        $tpl_revisions[] = array(
            'id' => $row['id_revision'],
            'version' => $row['version'],
            'versions_compatible' => implode(
              ', ',
              $versions_of[ $row['id_revision'] ]
              ),
            'ids_versions_compatible' => isset($ids_versions_compatible[$row['id_revision']])?
              implode(', ',$ids_versions_compatible[$row['id_revision']]) : null,
            'languages' => isset($languages_of[$row['id_revision']]) ?
              $languages_of[$row['id_revision']] : array(),
            'languages_diff' => isset($diff_languages_of[$row['id_revision']]) ?
              $diff_languages_of[$row['id_revision']] : array(),
            'rev_lang_ids' => isset($rev_languages_of_ids[$row['id_revision']]) ?
              $rev_languages_of_ids[$row['id_revision']] : array(),
            'date' => format_date($row['date'], array('day_name','day','month','year')),
            'age' => time_since($row['date'], 'month', null, false),
            'author' => get_author_name($row['author']) ,
            'author_id' => $row['author'] ,
            'u_download' => PHPWG_ROOT_PATH.'download.php?rid='.$row['id_revision'],
            'rev_descriptions' => $rev_descriptions,
            'rev_json_descriptions' => $escaped_json_rev_descriptions,
            'rev_default_description' =>
              htmlspecialchars(
                stripslashes($row['default_description'])
              ),
            'can_modify' => $page['user_can_modify'],
            'u_modify' => 'revision_mod.php?rid='.$row['id_revision'],
            'DELETE_REVISION' => 'revision_del.php?rid='.$row['id_revision'],
            'expanded' => isset($_GET['rid']) && $row['id_revision'] == $_GET['rid'],
            'downloads' => isset($downloads_of_revision[$row['id_revision']]) ? 
                            $downloads_of_revision[$row['id_revision']] : 0,
            'can_be_deleted' => can_revision_be_deleted($row['id_revision']),
        );

        $first_date = $row['date'];
      }

      $template->assign(
        'first_date', format_date($first_date, array('day_name','day','month','year'))
      );

      $template->assign(
        'first_date_formatted_since', time_since($first_date, $stop='month'),
      );

      $revisions_sort_order = conf_get_param('revisions_sort_order','version');

      if ($revisions_sort_order == 'version')
      {
        usort($tpl_revisions, function($a, $b) {
          return safe_version_compare($b['version'], $a['version']);
        });
      }

      $tpl_revisions[0]['expanded'] = true;
      $template->assign('revisions', $tpl_revisions);
    }

// +-----------------------------------------------------------------------+
// |                         Extension rating                              |
// +-----------------------------------------------------------------------+

    $rate_summary = array(
      'count' => 0, 
      'count_text' => sprintf(l10n('Rated %d times'), 0), 
      'rating_score' => generate_static_stars($data['rating_score']), 
      );
    if ($rate_summary['rating_score'] != null)
    {
      $query = '
    SELECT COUNT(1)
      FROM '.PEM_RATE_TABLE.'
      WHERE idx_extension = '.$current_extension_page_id.'
    ;';
      list($rate_summary['count']) = pwg_db_fetch_row(pwg_query($query));
      $rate_summary['count_text'] = sprintf(l10n('Rated %d times'), $rate_summary['count']);
    }
    $template->assign('rate_summary', $rate_summary);

    $user_rate = null;
    if ($rate_summary['count'] > 0)
    {
      $user_id = empty($user['id']) ? 0 : $user['id'];
      
      $query = '
    SELECT rate
      FROM '.PEM_RATE_TABLE.'
      WHERE 
        idx_extension = '.$current_extension_page_id. '
        AND idx_user = '.$user_id.';';
      if ($user_id == 0)
      {
        $ip_components = explode('.', $_SERVER['REMOTE_ADDR']);
        if (count($ip_components) > 3)
        {
          array_pop($ip_components);
        }
        $query.= '
        AND anonymous_id = "'.implode('.', $ip_components) . '"';
      }
      $query.= '
    ;';

      if ( pwg_db_num_rows( pwg_query($query) ) > 0 )
      {
        list($user_rate) = pwg_db_fetch_row($result);
      }
    }
    $template->assign('user_rating', array(
      'action' => $self_url.'&amp;action=rate',
      'rate' => $user_rate,
      ));
      
    // +-----------------------------------------------------------------------+
    // |                         Extension reviews                             |
    // +-----------------------------------------------------------------------+

    // total reviews in each language
    $current_language_id = get_current_language_id();

    $query = '
    SELECT
        idx_language,
        COUNT(1) AS count
      FROM '.PEM_REVIEW_TABLE.'
      WHERE 
        idx_extension = '.$current_extension_page_id.'
        '.(get_user_status() !='admin' ? 'AND validated = "true"' : null).'
      GROUP BY idx_language
    ;';
    $total_reviews = query2array($query, 'idx_language');

    $total=0;
    foreach ($total_reviews as $language) $total+= $language['count'];
    $template->assign('nb_reviews', $total);

    // reviews filter
    // if ( isset($_GET['display_all_reviews']) or !array_key_exists($current_language_id, $total_reviews) )
    // {
      $where_clause = '1=1';
    // }
    // else
    // {
    //   $where_clause = 'idx_language = '.$current_language_id;
    //   if ($total != $total_reviews[ $current_language_id ]['count'])
    //   {
    //     $template->assign('U_DISPLAY_ALL_REVIEWS', $self_url.'&amp;display_all_reviews#reviews');
    //     $template->assign('NB_REVIEWS_MASKED', $total-$total_reviews[ $current_language_id ]['count']);
    //   }
    // }

    // get displayed reviews
    $query = '
    SELECT *
      FROM '.PEM_REVIEW_TABLE.'
      WHERE
        idx_extension = '.$current_extension_page_id.'
        AND '.$where_clause.'
        '.(get_user_status() !='admin' ? 'AND validated = "true"' : null).'
      ORDER BY date DESC
    ;';
    $all_reviews = query2array($query);

    $language_reviews = $other_reviews = array();
    foreach ($all_reviews as $review)
    {
      $review['in_edit'] = false;
      $review['rate'] = generate_static_stars($review['rate'], false);
      $review['date'] = date('d F Y', strtotime($review['date']));
      
      if (get_user_status() =='admin')
      {
        if ( isset($_GET['edit_review']) and $_GET['edit_review'] == $review['id_review'] ) 
        {
          $review['in_edit'] = true;
          $review['u_cancel'] = $self_url;
          $review['action'] = $self_url.'&amp;action=edit_review';
        }
        
        $review['u_delete']   = $self_url.'&amp;delete_review='.$review['id_review'];
        if (!$review['in_edit'])             $review['u_edit']     = $self_url.'&amp;edit_review='.$review['id_review'];
        if ($review['validated'] == 'false') $review['u_validate'] = $self_url.'&amp;validate_review='.$review['id_review'];
      }
      else
      {
        unset($review['email']);
      }
      
      if (!$review['in_edit'])
      {
        $review['content'] = nl2br($review['content']);
      }
      
      if ($review['idx_language'] == $current_language_id)
      {
        array_push($language_reviews, $review);
      }
      else
      {
        array_push($other_reviews, $review);
      }
    }
    $template->assign('reviews', array_merge($language_reviews, $other_reviews));

    // prefilled and invisible inputs
    if (!empty($user['id']))
    {
      $user_review['author'] = $user['username'];
      $user_review['email'] = $user['email'];
      $user_review['is_logged'] = true;
    }
    $user_review['form_action'] = $self_url.'&amp;action=add_review';

    // $user_review = is_array($user_review) ? array_map('stripslashes', $user_review) : stripslashes($user_review); 
    $template->assign('user_review', $user_review);

    $scores = array_combine(range(0.5,5,0.5), range(0.5,5,0.5));
    $scores[0] = '--';
    asort($scores);
    $template->assign('scores', $scores);


    // +-----------------------------------------------------------------------+
    // |                     Extension & revision languages                    |
    // +-----------------------------------------------------------------------+
    $default_language = $interface_languages[$conf['default_language']]['code'];
    
    // Get selected languages of last revision
    $query = '
SELECT MAX(id_revision) as id
  FROM '.PEM_REV_TABLE.'
  WHERE idx_extension = '.$_GET['eid'].'
;';

    if ($last_rev = pwg_db_fetch_assoc(pwg_query($query))
      and !empty($last_rev['id']))
    {
      $language_ids_of_revision = get_language_ids_of_revision(array($last_rev['id']));
      $selected_languages = !empty($language_ids_of_revision[$last_rev['id']]) ?
        $language_ids_of_revision[$last_rev['id']] : array();
    }

    // by default the contributor accepts the agreement
    // $accept_agreement_checked = 'checked="checked"';

    $template->assign(
      array(
        'use_agreement' => $conf['use_agreement'],
        'agreement_description' => l10n('agreement_description'),
        'default_language' => $default_language,
        'file_needed' => true,
        )
      );
      
    // Get the main application versions listing
    $query = '
SELECT
    id_version,
    version
  FROM '.PEM_VER_TABLE.'
;';

    // Get all extensions language listing
    $query = '
SELECT
    id_language,
    code,
    name
  FROM '.PEM_LANG_TABLE.'
  ORDER BY name
;';
    $extensions_languages = query2array($query);
    // $tpl_languages = array();
    $ext_languages = array();
    $extension_language_ids = array();

    foreach($extensions_languages as $ext_lang)
    {
      // $name = trim(substr($ext_lang['name'], 0, -4));

      if( in_array($ext_lang['id_language'], $selected_languages))
      {
        array_push(
          $ext_languages,
          array(
            'id' => $ext_lang['id_language'],
            'code' => $ext_lang['code'],
            'name' => $ext_lang['name'],
            )
        );
        array_push($extension_language_ids, $ext_lang['id_language']);
      }
    }
    
    $template->assign(
      array(
        'ext_languages' => $ext_languages,
        'extensions_languages_ids' => $extension_language_ids,
      )
    );
    
    $upload_methods = array('upload', 'git');
    if ($conf['allow_download_url'])
    {
      array_push($upload_methods, 'url');
      $template->assign(
        array(
          'DOWNLOAD_URL' => '',
          )
        );
    }
    if ($conf['allow_svn_file_creation'])
    {
      array_push($upload_methods, 'svn');
    }

    $file_type = 'upload';
    if (!empty($svn_url))
    {
      $file_type = 'svn';
    }
    elseif (!empty($git_url))
    {
      $file_type = 'git';
    }

    $template->assign(
      array(
        'upload_methods' => $upload_methods,
        'FILE_TYPE' => $file_type,
      )
    );

    //Get List of versions for filter
    $query = '
    SELECT 
        id_version,
        version
      FROM '.PEM_VER_TABLE.'
      WHERE state = \'stable\'
      ORDER BY id_version DESC
      LIMIT 1
    ;';
    $result = query2array($query);
    $pwg_latest_version = $result[0];

    $template->assign(
      array(
        'pwg_latest_version' => $pwg_latest_version,
      )
    );

    // Assign single_view template
    $template->set_filename('pem_page', realpath(PEM_PATH . 'template/single_view.tpl'));

// +-----------------------------------------------------------------------+
// |                         Assign modal tpls                             |
// +-----------------------------------------------------------------------+

    // Assign template for general information modal
    $template->set_filename('pem_edit_general_info_form', realpath(PEM_PATH . 'template/modals/edit_general_info_form.tpl'));
    $template->assign_var_from_handle('PEM_EDIT_GENERAL_INFO_FORM', 'pem_edit_general_info_form');

    // Assign template for edit image modal
    $template->set_filename('pem_edit_image_form', realpath(PEM_PATH . 'template/modals/edit_image_form.tpl'));
    $template->assign_var_from_handle('PEM_EDIT_IMAGE_FORM', 'pem_edit_image_form');

    // Assign template for authors modal
    $template->set_filename('pem_edit_authors_form', realpath(PEM_PATH . 'template/modals/edit_authors_form.tpl'));
    $template->assign_var_from_handle('PEM_EDIT_AUTHORS_FORM', 'pem_edit_authors_form');

    // Assign template for edit description modal for translators
    $template->set_filename('pem_edit_description_form', realpath(PEM_PATH . 'template/modals/edit_description_form.tpl'));
    $template->assign_var_from_handle('PEM_EDIT_DESCRIPTION_FORM', 'pem_edit_description_form');

    // Assign template for add link modal
    $template->set_filename('pem_add_link_form', realpath(PEM_PATH . 'template/modals/add_link_form.tpl'));
    $template->assign_var_from_handle('PEM_ADD_LINK_FORM', 'pem_add_link_form');
    
    // Assign template for edit related links modal
    $template->set_filename('pem_edit_related_link_form', realpath(PEM_PATH . 'template/modals/edit_related_link_form.tpl'));
    $template->assign_var_from_handle('PEM_EDIT_RELATED_LINK_FORM', 'pem_edit_related_link_form');

    // Assign template for edit svn and git configuration
    $template->set_filename('pem_edit_svn_git_config', realpath(PEM_PATH . 'template/modals/edit_svn_git_config.tpl'));
    $template->assign_var_from_handle('PEM_EDIT_SVN_GIT_FORM', 'pem_edit_svn_git_config');

    // Assign template for edit revision modal
    $template->set_filename('pem_edit_revision_form', realpath(PEM_PATH . 'template/modals/edit_revision_form.tpl'));
    $template->assign_var_from_handle('PEM_EDIT_REVISION_FORM', 'pem_edit_revision_form');

    // Assign template for add revision modal
    $template->set_filename('pem_add_revision_form', realpath(PEM_PATH . 'template/modals/add_revision_form.tpl'));
    $template->assign_var_from_handle('PEM_ADD_REVISION_FORM', 'pem_add_revision_form');

    // Assign template for deleting extension
    $template->set_filename('pem_delete_extension', realpath(PEM_PATH . 'template/modals/delete_ext.tpl'));
    $template->assign_var_from_handle('PEM_DELETE_EXTENSION', 'pem_delete_extension');
    
    $template->set_filename('pem_delete_revision', realpath(PEM_PATH . 'template/modals/delete_revision.tpl'));
    $template->assign_var_from_handle('PEM_DELETE_REVISION', 'pem_delete_revision');

    $template->set_filename('pem_delete_link', realpath(PEM_PATH . 'template/modals/delete_link.tpl'));
    $template->assign_var_from_handle('PEM_DELETE_LINK', 'pem_delete_link');

    // Assign template for confirmation modal
    $template->set_filename('pem_display_languages', realpath(PEM_PATH . 'template/modals/display_languages.tpl'));
    $template->assign_var_from_handle('PEM_DISPLAY_LANGUAGES', 'pem_display_languages');
  }
}
else
{
  http_response_code(404);
  $template->set_filenames(array('pem_page' => realpath(PEM_PATH . 'template/404.tpl')));
}