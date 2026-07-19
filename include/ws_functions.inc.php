<?php
defined('PEM_PATH') or die('Hacking attempt!');

include_once(PEM_PATH . 'include/functions_core.inc.php');
include_once(PEM_PATH . 'include/functions_users.inc.php');

function pem_ws_add_methods($arr)
{
  $service = &$arr[0];
 
  $service->addMethod(
    'pem.categories.getList',
    'ws_pem_categories_get_list',
    array(
    ),
    'Get list of categories.'
  );

  $service->addMethod(
    'pem.categories.getInfo',
    'ws_pem_categories_get_info',
    array(
      'category_id' => array('type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
    ),
    'Get category info.'
  );

  $service->addMethod(
    'pem.extensions.getList',
    'ws_pem_extensions_get_list',
        array(
      'category_id' => array(
        'flags'=>WS_PARAM_OPTIONAL,
        'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE,
        'info'=>'use category id',
      ),
      'page' => array(
        'flags'=>WS_PARAM_OPTIONAL,
        'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE,
      ),
      'sort_by' => array(
        'default'=>'date_desc',
        'info'=>'date_desc, date_asc, a_z, z_a',
      ),
      'filter_version' => array(
        'flags'=>WS_PARAM_OPTIONAL,
        'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE,
        'info'=>'a piwigo version id',
      ),
      'filter_authors' => array(
        'flags'=>WS_PARAM_OPTIONAL,
        'info'=>'array of user ids',
      ),
      'filter_tags' => array(
        'flags'=>WS_PARAM_OPTIONAL|WS_PARAM_FORCE_ARRAY,
        'info'=>'array of tag ids',
      ),
      'filter_search' => array(
        'flags'=>WS_PARAM_OPTIONAL,
      ),
    ),
    'Get list of extensions. Filter by category or version. Apply different sorting orders. Get limited number of extension by using pages.'
  );

  $service->addMethod(
    'pem.extensions.getCount',
    'ws_pem_extensions_get_count',
    array(
      'category_id' => array(
        'default' => null,
        'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE,
        'info'=>'use category id'
      ),
    ),
    'Get number of extensions, can filter for number of extensions per catetgory.'
  );

  $service->addMethod(
    'pem.extensions.getInfo',
    'ws_pem_extensions_get_info',
    array(
      'extension_id' => array('type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
    ),
    'Get extension info.'
  );

  $service->addMethod(
    'pem.extensions.getHighestRated',
    'ws_pem_extensions_get_highest_rated',
    array(
      'extension_type' => array('info'=>'language, theme, tool, plugin'),
    ),
    'Get highest rated extension depending on type.'
  );

  $service->addMethod(
    'pem.extensions.getMostDownloaded',
    'ws_pem_extensions_get_most_downloaded',
    array(
      'extension_type' => array('info'=>'language, theme, tool, plugin'),
    ),
    'Get most downloded extension depending on type.'
  );

  $service->addMethod(
    'pem.extensions.getMostRecent',
    'ws_pem_extensions_get_most_recent',
    array(
      'extension_type' => array('info'=>'language, theme, tool, plugin'),
    ),
    'Get most recent extension depending on type.'
  );

  $service->addMethod(
    'pem.extensions.deleteAuthor',
    'ws_pem_extensions_delete_author',
    array(
      'extension_id' => array('type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'user_id' => array('type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'pwg_token' =>  array(),
    ),
    'Remove a user from extension authors'
  );

  $service->addMethod(
    'pem.extensions.setOwner',
    'ws_pem_extensions_set_owner',
    array(
      'extension_id' => array('type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'user_id' => array('type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'pwg_token' =>  array(),
    ),
    'Set a user as extension owner'
  );

  $service->addMethod(
    'pem.extensions.deleteLink',
    'ws_pem_extensions_delete_link',
    array(
      'extension_id' => array('type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'link_id' => array('type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'pwg_token' =>  array(),
    ),
    'Delete a link associated to an extension'
  );

  $service->addMethod(
    'pem.extensions.deleteSvnGitConfig',
    'ws_pem_extensions_delete_svn_git_config',
    array(
      'extension_id' => array('type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'pwg_token' =>  array(),
    ),
    'Delete svn/git configuration linked to extension'
  );

  $service->addMethod(
    'pem.extensions.deleteExtension',
    'ws_pem_extensions_delete_extension',
    array(
      'extension_id' => array('type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'pwg_token' =>  array(),
    ),
    'Delete extension'
  );

  $service->addMethod(
    'pem.revisions.getLanguageInfo',
    'ws_pem_revisions_get_language_info',
    array(
      'extension_id' => array(
        'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE,
        'flags'=>WS_PARAM_OPTIONAL,
      ),
      'revision_id' => array(
        'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE,
        'flags'=>WS_PARAM_OPTIONAL,
      ),
    ),
    'Used by the "Detect Languages" button when creating/editing a revision. Provide either revision_id OR extension_id'
  );

  $service->addMethod(
    'pem.revisions.deleteRevision',
    'ws_pem_revisions_delete_revision',
    array(
      'extension_id' => array('type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'revision_id' => array('type'=>WS_TYPE_INT|WS_TYPE_POSITIVE),
      'pwg_token' =>  array(),
    ),
    'Delete a revision associated to an extension'
  );

}

/**
 * Get list of all extensions
 * Filter by category or version
 * Can apply sort order and limit number or extensions returned with page
 */
function ws_pem_extensions_get_list($params, &$service)
{
  global $conf;

  $filter = array();
  
  $extensions_per_page = conf_get_param('extensions_per_page', 15);

  // Get sort order
  $sort_by = $params['sort_by'];
  switch ( $sort_by) 
  {
    case "a_z":
      $sort_by = 'compare_extension_name_asc';
      break;
    case "z_a":
      $sort_by = 'compare_extension_name_desc';
      break;
    case "date_desc":
      $sort_by = 'compare_extension_date_desc';
      break;
    case "date_asc":
      $sort_by = 'compare_extension_date_asc';
      break;
    default:
      return new PwgError(WS_ERR_INVALID_PARAM, 'Invalid sort_by');
      break;
  }
  
  // page is used to display a certain amount of extensions per page and get the next ones for each page
  if (isset($params['page']))
  {
    $page = ($extensions_per_page * $params['page']) - $extensions_per_page;
  }

  // Filter category
  $all_category_ids = array_keys(ws_pem_categories_get_list());

  // Check if category is set for filter, return if category doesn't exist
  if(isset($params['category_id']))
  {
    if(!in_array($params['category_id'], $all_category_ids))
    {
      return array(
        'message' => 'NoThis category doesn\'t exist'
      );
    }
    $filter['category_ids'] = explode(" ",$params['category_id']); 
    $filter['category_mode'] = 'and';
    $nb_total = pem_extensions_get_count($params['category_id']);
  }
  else {
    $nb_total = pem_extensions_get_count();
  }

  // Filter version is used in list_view to filter extensions by compatible version
  if (isset($params['filter_version']))
  {
    $filter['id_version'] = $params['filter_version'];
  }

  if (isset($params['filter_authors']))
  {
    $filter['user_ids'] = explode(",", $params['filter_authors']);
  }
  
  if (isset($params['filter_tags']))
  {
    $filter['tag_ids'] = $params['filter_tags'];
    $filter['tag_mode'] = 'and';
  }

  if (isset($params['filter_search']))
  {
    $filter['search'] = $params['filter_search'];
  }

  $revision_ids = array();
  $revision_infos_of = array();
  $extension_ids = array();
  $extension_infos_of = array();

  // Apply filter to extension ids
  if ($filter != null)
  {
    $filtered_extension_ids = get_filtered_extension_ids($filter);
    if (empty($filtered_extension_ids))
    {
      return array(
        'message' => 'No extensions match your filter'
      );
    }

    $filtered_extension_ids_string = implode(
      ',',
      $filtered_extension_ids
    );
  
  }

  // retrieve N last updated extensions
  $query = '
  SELECT
      r.idx_extension,
      MAX(r.id_revision) AS id_revision,
      MAX(r.date) AS max_date
    FROM '.PEM_REV_TABLE.' r';
  if (isset($filtered_extension_ids)) {
    if (count($filtered_extension_ids) > 0) {
      $query.= '
    WHERE idx_extension IN ('.$filtered_extension_ids_string.')';
    }
  }
  $query.= '
    GROUP BY idx_extension';

  if (isset($params['filter_search'])) {
    $query.= '
    ORDER BY FIND_IN_SET(idx_extension, "'.$filtered_extension_ids_string.'")';
  }
  else {
    $query.= '
    ORDER BY max_date DESC';
  }
  $query.= '
  ;';

  $all_revision_ids = query2array($query, null, 'id_revision');

  if (count($all_revision_ids) == 0)
  {
    return array(
      'message' => 'No extensions match your filter'
    );
  }

  $nb_extensions = count($all_revision_ids);

  // Offset is used to get extensions from specific page 
  if (isset($page))
  {
    $revision_ids = array_slice($all_revision_ids, $page , $extensions_per_page, true);
  }
  else
  {
    $revision_ids = $all_revision_ids;
  }

  $versions_of = get_versions_of_revision($revision_ids);
  $languages_of = get_languages_of_revision($revision_ids);

  // retrieve revisions information
  $revision_infos_of = get_revision_infos_of($revision_ids);
  $extension_ids = array_unique(
  array_from_subfield(
    $revision_infos_of,
    'idx_extension'
    )
  );

  $extension_infos_of = get_extension_infos_of($extension_ids);
  $download_of_extension = get_download_of_extension($extension_ids);
  $categories_of_extension = get_categories_of_extension($extension_ids);
  $tags_of_extension = get_tags_of_extension($extension_ids);

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


  $revisions = array();

  foreach ($revision_ids as $revision_id)
  {
    $extension_id = $revision_infos_of[$revision_id]['idx_extension'];
    $authors = get_extension_authors($extension_id);
    $screenshot_infos = get_extension_screenshot_infos($extension_id);


    $versions_of_extension = get_versions_of_extension(array($extension_id));
    $last_ext_rev = end($versions_of_extension[$extension_id]);
    
    $compatible_latest_pwg_version = false;
    if ($pwg_latest_version['version'] == $last_ext_rev)
    {
      $compatible_latest_pwg_version = true;
    }

    // Compare lat revision date for certification
    $date = new DateTime();
    $date->setTimestamp($revision_infos_of[$revision_id]['date']);
    $now = new DateTime();
    
    $last_revision_diff = $now->diff($date);
    $certification = 1;

    if ($last_revision_diff->days < 90) // if the last revision is new of 3 month or less
    {
      $certification = 3;
    }
    elseif ($last_revision_diff->days < 180) // 6 month or less
    {
      $certification = 2;
    }
    elseif ($last_revision_diff->y > 3) // 3 years or less
    {
      $certification = 0;
    }
    // Between 6 month and 3 years : certification = 1

    // Get number of ratings for extension
    $query = '
    SELECT 
        COUNT(*) 
      FROM '.PEM_RATE_TABLE.'
          WHERE idx_extension = "'.$extension_id.'"
        ;';
    
    list($nb_ratings) = pwg_db_fetch_row(pwg_query($query));
    

    array_push(
      $revisions,
      array(
        'extension_id' => $extension_id,
        'extension_name' => $extension_infos_of[$extension_id]['name'],
        'rating_score' => $extension_infos_of[$extension_id]['rating_score'],
        'rating_score_stars' => generate_static_stars($extension_infos_of[$extension_id]['rating_score']),
        'nb_ratings' =>$nb_ratings,
        'nb_reviews' => !empty($extension_infos_of[$extension_id]['nb_reviews']) ? sprintf(l10n('%d reviews'), $extension_infos_of[$extension_id]['nb_reviews']) : null,
        'about' => nl2br(
          htmlspecialchars(
            strip_tags(
              stripslashes($extension_infos_of[$extension_id]['description'])
            )
          )
        ),
        'authors' => array_combine($authors, get_author_name($authors)),
        'name' => $revision_infos_of[$revision_id]['version'],
        'compatible_versions' => implode(', ', $versions_of[$revision_id]),
        'languages' => isset($languages_of[$revision_id]) ?
            $languages_of[$revision_id] : array(),
        'description' => 
        nl2br(
          htmlspecialchars(
            strip_tags(
              stripslashes($revision_infos_of[$revision_id]['description'])
            )
          )
        ),
        'date' => date('Y-m-d', $revision_infos_of[$revision_id]['date']),
        'certification' => $certification,
        'thumbnail_src' => $screenshot_infos
          ? $screenshot_infos['thumbnail_src']
          : null,
        'screenshot_url' => $screenshot_infos
          ? $screenshot_infos['screenshot_url']
          : null,
        'downloads' => isset($download_of_extension[$extension_id]) ?
                        $download_of_extension[$extension_id] : 0,
        'categories' => isset($categories_of_extension[$extension_id])? $categories_of_extension[$extension_id]: null,
        'tags' => empty($tags_of_extension[$extension_id]) ? array() : $tags_of_extension[$extension_id],
        'revision_name' => $revision_infos_of[$revision_id]['version'],
        'compatible_latest_pwg_version' => $compatible_latest_pwg_version, 
      )
    );
  }

  // sort alphabetically by name
  usort($revisions, $sort_by);

  if (!isset($_REQUEST['format']))
  {
    //Echo to be compatible with previous version of Piwigo
    echo serialize($revisions, $extensions_per_page, $nb_total);
    exit;
  }

  return array(
    'revisions' => $revisions,
    'extensions_per_page' => $extensions_per_page,
    'nb_total_extensions' => $nb_total,
    'nb_extensions_filtered' => $nb_extensions,
  );
}

function compare_extension_name_asc($a, $b)
{
  return strcasecmp($a['extension_name'], $b['extension_name']);
}

function compare_extension_name_desc($a, $b)
{
  return strcasecmp($b['extension_name'], $a['extension_name']);
}

function compare_extension_date_desc($a, $b)
{
  return strcmp($b['date'], $a['date']);
}

function compare_extension_date_asc($a, $b)
{
  return strcmp($a['date'], $b['date']);
}

/**
 * Get extension info
 * params extension id
 */
function ws_pem_extensions_get_info($params, &$service)
{

  if (!isset($params['extension_id']))
  {
    die('missing eid');
  }

  if (!preg_match('/^\d+$/', $params['extension_id']))
  {
    die('expected format for eid');
  }
  
  $extension_infos_of = array();
  
  $query = '
SELECT
    id_extension,
    name,
    description,
    username
  FROM '.PEM_EXT_TABLE.' AS e
    JOIN '.USERS_TABLE.' AS u ON u.id= e.idx_user
  WHERE id_extension = '.$params['extension_id'].'
;';

  $extension_infos_of = query2array($query, 'id_extension');

  if (!isset($_REQUEST['format']))
  {
    //Echo to be compatible with previous version of Piwigo
    echo serialize($extension_infos_of);
    exit;
  }

  return $extension_infos_of;  
}

/**
 * Get number of extensions depending on category
 */
function ws_pem_extensions_get_count($params, &$service)
{
  // Check if category is set for filter, die if category doesn't exist
  $category_ids = array_keys(ws_pem_categories_get_list());
  if(isset($params['category_id']))
  {
    if(!in_array($params['category_id'], $category_ids))
    {
      die(
        'No categories match your filter'
      );
    }
    $category_id = $params['category_id']; 
  }

  $count_of_extensions = pem_extensions_get_count($category_id);

  return $count_of_extensions;
}

/**
 * Get highest rated extension depending on category used in params
 * params category id
 */
function ws_pem_extensions_get_highest_rated($params, &$service)
{
  $category_id;
  switch ($params['extension_type']) 
  {
    case 'language':
      $category_id = 8;
      break;
    case 'theme':
      $category_id = 10;
      break;
    case 'tool':
      $category_id = 11;
      break;
    case 'plugin':
      $category_id = 12;
      break;
  }

  $query = '
SELECT 
    id_extension,
    name,
    description,
    rating_score
  FROM '.PEM_EXT_TABLE.' AS extensions
  left JOIN '.PEM_EXT_CAT_TABLE.' AS categories
   ON extensions.id_extension = categories.idx_extension
   WHERE categories.idx_category = '.$category_id.'
   ORDER BY rating_score DESC
   LIMIT 1
;';

  $highest_rated = query2array($query);

  if (!isset($_REQUEST['format']))
  {
    //Echo to be compatible with previous version of Piwigo
    echo serialize($highest_rated);
    exit;
  }

  $highest_rated = $highest_rated [0];

  return $highest_rated;
}

/**
 * Get most downloaded for a type of extension defined in params
 */
function ws_pem_extensions_get_most_downloaded($params, &$service)
{
  $category_id;
  switch ($params['extension_type']) 
  {
    case 'language':
      $category_id = 8;
      break;
    case 'theme':
      $category_id = 10;
      break;
    case 'tool':
      $category_id = 11;
      break;
    case 'plugin':
      $category_id = 12;
      break;
  }

  $query = '
SELECT
    extensions.id_extension,
    name,
    SUM(nb_downloads) AS download_count,
    extensions.description
  FROM piwigo_pem_pem_revisions AS revisions
    LEFT JOIN piwigo_pem_pem_extensions AS extensions
    ON revisions.idx_extension = extensions.id_extension
      left JOIN piwigo_pem_pem_extensions_categories AS categories
      ON extensions.id_extension = categories.idx_extension
      WHERE categories.idx_category = '.$category_id.'
  GROUP BY revisions.idx_extension
  ORDER BY download_count DESC 
  LIMIT 1
;';

  $most_downloaded = query2array($query);

  if (!isset($_REQUEST['format']))
  {
    //Echo to be compatible with previous version of Piwigo
    echo serialize($most_downloaded);
    exit;
  }

  $most_downloaded= $most_downloaded[0];

  return $most_downloaded;
}

/**
 * Get most recent for a type of extension defined in params
 */
function ws_pem_extensions_get_most_recent($params, &$service)
{
  $category_id;
  switch ($params['extension_type']) 
  {
    case 'language':
      $category_id = 8;
      break;
    case 'theme':
      $category_id = 10;
      break;
    case 'tool':
      $category_id = 11;
      break;
    case 'plugin':
      $category_id = 12;
      break;
  }

  $query = '
SELECT
    extensions.id_extension,
    name,
    revisions.date,
    extensions.description
  FROM piwigo_pem_pem_revisions AS revisions
    LEFT JOIN piwigo_pem_pem_extensions AS extensions
    ON revisions.idx_extension = extensions.id_extension
      LEFT JOIN piwigo_pem_pem_extensions_categories AS categories
      ON extensions.id_extension = categories.idx_extension
      WHERE categories.idx_category = '.$category_id.'
  ORDER BY revisions.date DESC 
  LIMIT 1
;';

  $most_recent= query2array($query);

  if (!isset($_REQUEST['format']))
  {
    //Echo to be compatible with previous version of Piwigo
    echo serialize($most_recent);
    exit;
  }

  $most_recent= $most_recent[0];
  $most_recent['formatted_date'] = format_date($most_recent['date']);
  $most_recent['time_since']= time_since($most_recent['date'], $stop='weeks');

  return $most_recent;
}

/**
 * Get list of extension categories
 */
function ws_pem_categories_get_list()
{
  $categories_infos_of = array();

  $query = '
SELECT
    id_category,
    name,
    description
  FROM '.PEM_CAT_TABLE.'
;';

  $categories_infos_of = query2array($query, 'id_category');

  if (!isset($_REQUEST['format']))
  {
    //Echo to be compatible with previous version of Piwigo
    echo serialize($categories_infos_of);
    exit;
  }

  return $categories_infos_of;
}

/**
 * Get category info
 * params extension id
 */
function ws_pem_categories_get_info($params, &$service)
{

  if (!isset($params['category_id']))
  {
    die('missing category id');
  }

  if (!preg_match('/^\d+$/', $params['category_id']))
  {
    die('expected format for category id');
  }
  
  $category_infos_of = array();
  
  $query = '
SELECT
    id_category,
    name,
    description
  FROM '.PEM_CAT_TABLE.' AS e
  WHERE id_category = '.$params['category_id'].'
;';

  $category_infos_of = query2array($query, 'id_category');

  if (!isset($_REQUEST['format']))
  {
    //Echo to be compatible with previous version of Piwigo
    echo serialize($category_infos_of);
    exit;
  }

  return $category_infos_of;  
}

/**
 * Remove a user from extension authors
 */

function ws_pem_extensions_delete_author($params, &$service)
{

  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  if (!isset($params['extension_id']))
  {
    die('missing extension id');
  }

  if (!isset($params['user_id'])) 
  {
    die('missing user id');
  }

  global $user, $conf, $logger;

  // Gets the available authors and owner
  $query = '
SELECT DISTINCT 
    eT.idx_user as uid
  FROM '.PEM_EXT_TABLE.' as eT
    WHERE eT.id_extension = '.$params['extension_id'].'
;';

  $owners = query2array($query, 'uid');

  $query = '
SELECT DISTINCT
    aT.idx_user as uid
  FROM '.PEM_AUTHORS_TABLE.' as aT
    WHERE aT.idx_extension = '.$params['extension_id'].'
;';

  $authors= query2array($query, 'uid');

  $all_authors = array_merge_recursive($owners, $authors);

  $is_author = false;

  foreach ($all_authors as $author) {
    if ($author['uid'] == $user['id']) {
      $is_author = true;
      break;
    }
  }

  //Check if user is admin, owner or author before doing action
  if ($user['id'] != $params['user_id'] and (is_admin() or $is_author))
  {
    delete_author($params['extension_id'], $params['user_id']);

    // $country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
    // $country_name = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);

    $country_code = 'unkown';
    $country_name = 'unkown';
    
    notify_mattermost('['.$conf['mattermost_notif_type'].'] user #'.$user['id'].' ('.$user['username'].') deleted author for extension #'.$params['extension_id'].' , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);
    pwg_activity('pem_author', $params['extension_id'], 'delete', array('extension' => $params['extension_id']));

  }
  else
  {
    $logger->info('Action not allowed in FILE = '.__FILE__.', LINE = '.__LINE__);
    set_status_header(489);
    exit();
  }
}

/**
 * Set a user as extension owner
 */

 function ws_pem_extensions_set_owner($params, &$service)
 {
  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  if (!isset($params['extension_id']))
  {
    die('missing extension id');
  }

  if (!isset($params['user_id'])) 
  {
    die('missing user id');
  }

  global $user, $conf, $logger;

  // Gets the available authors and owner
  $query = '
SELECT DISTINCT 
    eT.idx_user as uid
  FROM '.PEM_EXT_TABLE.' as eT
    WHERE eT.id_extension = '.$params['extension_id'].'
;';

  $owners = query2array($query, 'uid');

  //Check if user is admin, owner or author before doing action
  if (is_admin() or !in_array($user['id'], $owners))
  {
    extension_set_owner($params['extension_id'], $params['user_id']);

    // $country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
    // $country_name = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);

    $country_code = 'unkown';
    $country_name = 'unkown';
    
    notify_mattermost('['.$conf['mattermost_notif_type'].'] user #'.$user['id'].' ('.$user['username'].') set owner for extension #'.$params['extension_id'].' , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);
    pwg_activity('pem_author', $params['user_id'], 'delete', array('extension' => $params['extension_id']));

  }
  else
  {
    $logger->info('Action not allowed in FILE = '.__FILE__.', LINE = '.__LINE__);
    set_status_header(489);
    exit();
  }

}

/**
 * Delete a link associated to an extension
 */
function ws_pem_extensions_delete_link($params, &$service)
{
  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  global $user, $conf, $logger;

  // Gets the available authors and owner
  $query = '
SELECT DISTINCT 
    eT.idx_user as uid
  FROM '.PEM_EXT_TABLE.' as eT
    WHERE eT.id_extension = '.$params['extension_id'].'
;';

  $owners = query2array($query, 'uid');

  $query = '
SELECT DISTINCT
    aT.idx_user as uid
  FROM '.PEM_AUTHORS_TABLE.' as aT
    WHERE aT.idx_extension = '.$params['extension_id'].'
;';

  $authors= query2array($query, 'uid');

  $all_authors = array_merge_recursive($owners, $authors);

  $is_author = false;

  foreach ($all_authors as $author) {
    if ($author['uid'] == $user['id']) {
      $is_author = true;
      break;
    }
  }

  //Check if user is admin, owner or author before doing action
  if (is_admin() or $is_author)
  {
    $query = '
DELETE
  FROM '.PEM_LINKS_TABLE.'
  WHERE id_link = '.$params['link_id'].'
    AND idx_extension = '.$params['extension_id'].'
;';
    pwg_query($query);

    // $country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
    // $country_name = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);

    $country_code = 'unkown';
    $country_name = 'unkown';

    notify_mattermost('['.$conf['mattermost_notif_type'].'] user #'.$user['id'].' ('.$user['username'].') deleted link for extension #'.$params['extension_id'].' , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);
    pwg_activity('pem_link', $params['extension_id'], 'delete', array('extension' => $params['extension_id']));
  }
  else
  {
    $logger->info('Action not allowed in FILE = '.__FILE__.', LINE = '.__LINE__);
    set_status_header(489);
    exit();
  }
}

/**
 * Delete a svn/git config linked to an extension
 */
function ws_pem_extensions_delete_svn_git_config($params, &$service)
{
  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  global $user, $conf, $logger;

  // Gets the available authors and owner
  $query = '
SELECT DISTINCT 
    eT.idx_user as uid
  FROM '.PEM_EXT_TABLE.' as eT
    WHERE eT.id_extension = '.$params['extension_id'].'
;';

  $owners = query2array($query, 'uid');

  $query = '
SELECT DISTINCT
    aT.idx_user as uid
  FROM '.PEM_AUTHORS_TABLE.' as aT
    WHERE aT.idx_extension = '.$params['extension_id'].'
;';

  $authors= query2array($query, 'uid');

  $all_authors = array_merge_recursive($owners, $authors);

  $is_author = false;

  foreach ($all_authors as $author) {
    if ($author['uid'] == $user['id']) {
      $is_author = true;
      break;
    }
  }

  //Check if user is admin, owner or author before doing action
  if (is_admin() or $is_author)
  {

    $query = '
UPDATE '.PEM_EXT_TABLE.'
SET svn_url = NULL,
    git_url = NULL,
    archive_root_dir = NULL,
    archive_name = NULL
WHERE id_extension = '.$params['extension_id'].'
;';

    pwg_query($query);

    // $country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
    // $country_name = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);

    $country_code = 'unkown';
    $country_name = 'unkown';

    notify_mattermost('['.$conf['mattermost_notif_type'].'] user #'.$user['id'].' ('.$user['username'].') deleted SVN/git config for extension #'.$params['extension_id'].' , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);
    pwg_activity('pem_svn_git', $params['extension_id'], 'delete', array('extension' => $params['extension_id']));
  }
  else
  {
    $logger->info('Action not allowed in FILE = '.__FILE__.', LINE = '.__LINE__);
    set_status_header(489);
    exit();
  }
}

/**
 * Delete an extension
 */
function ws_pem_extensions_delete_extension($params, &$service)
{
  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  global $user, $conf, $logger;

  if (is_admin())
  {
    $query = '
SELECT
    name
  FROM '.PEM_EXT_TABLE.'
    WHERE id_extension = '.$params['extension_id'].'
;';
  list($extension_name) = pwg_db_fetch_row(pwg_query($query));

  // Delete all the revisions for the given extension
    $query = '
SELECT id_revision
  FROM '.PEM_REV_TABLE.'
  WHERE idx_extension = '.$params['extension_id'].'
;';
    $rev_to_delete = query2array($query, null, 'id_revision');
    delete_revisions($rev_to_delete);

  // Deletes all the categories relations
    $query = '
DELETE
  FROM '.PEM_EXT_CAT_TABLE.'
  WHERE idx_extension = '.$params['extension_id'].'
;';
    pwg_query($query);

  // Deletes all the tags relations
    $query = '
DELETE
  FROM '.PEM_EXT_TAG_TABLE.'
  WHERE idx_extension = '.$params['extension_id'].'
;';
    pwg_query($query);

  // Deletes all the rates
    $query = '
DELETE
  FROM '.PEM_RATE_TABLE.'
  WHERE idx_extension = '.$params['extension_id'].'
;';
    pwg_query($query);

  // Deletes all the reviews
    $query = '
DELETE
  FROM '.PEM_REVIEW_TABLE.'
  WHERE idx_extension = '.$params['extension_id'].'
;';
    pwg_query($query);

  // And finally delete the extension
    $query = '
DELETE
  FROM '.PEM_EXT_TABLE.'
  WHERE id_extension = '.$params['extension_id'].'
;';
    pwg_query($query);

    // $country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
    // $country_name = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);

    $country_code = 'unkown';
    $country_name = 'unkown';
    
    notify_mattermost('['.$conf['mattermost_notif_type'].'] user #'.$user['id'].' ('.$user['username'].') deleted extension #'.$params['extension_id'].'('.$extension_name.') , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);

    pwg_activity('pem_extension', $params['extension_id'], 'delete', array(''));
  }
  else
  {
    $logger->info('Action not allowed in FILE = '.__FILE__.', LINE = '.__LINE__);
    set_status_header(489);
    exit();
  }
}

/**
 * Delete a revision linked to an extension
 */
function ws_pem_revisions_delete_revision($params, &$service)
{ 
  if (get_pwg_token() != $params['pwg_token'])
  {
    return new PwgError(403, 'Invalid security token');
  }

  //Check if the revision can be deleted, if it is less than on hour old and less than 10 downlaods by users
  $can_delete_revision = can_revision_be_deleted($params['extension_id']);

  if (!$can_delete_revision)
  {
    $message = l10n('This action cannot be performed at this time. Please contact an admin.');
    exit($message);
  }

  check_user_activity();

  global $user, $conf, $logger;

  // Gets the available authors and owner
  $query = '
SELECT DISTINCT 
    eT.idx_user as uid
  FROM '.PEM_EXT_TABLE.' as eT
    WHERE eT.id_extension = '.$params['extension_id'].'
;';

  $owners = query2array($query, 'uid');

  $query = '
SELECT DISTINCT
    aT.idx_user as uid
  FROM '.PEM_AUTHORS_TABLE.' as aT
    WHERE aT.idx_extension = '.$params['extension_id'].'
;';

  $authors= query2array($query, 'uid');

  $all_authors = array_merge_recursive($owners, $authors);

  $is_author = false;

  foreach ($all_authors as $author) {
    if ($author['uid'] == $user['id']) {
      $is_author = true;
      break;
    }
  }

  //Check if user is admin, owner or author before doing action
  if (is_admin() or $is_author)
  {
    $query = '
SELECT
    id_extension,
    name
  FROM '.PEM_EXT_TABLE.'
    WHERE id_extension = '.$params['extension_id'].'
;';

    list($eid, $extension_name) = pwg_db_fetch_row(pwg_query($query));
  
    $revision_infos_of = get_revision_infos_of(array($params['revision_id']));

    @unlink(
      get_revision_src(
        $revision_infos_of[$params['revision_id']]['idx_extension'],
        $params['revision_id'],
        $revision_infos_of[$params['revision_id']]['url']
      )
    );

    $query = '
DELETE
  FROM '.PEM_COMP_TABLE.'
  WHERE idx_revision = '.$params['revision_id'].'
;';
    pwg_query($query);

    $query = '
DELETE
  FROM '.PEM_REV_TABLE.'
  WHERE id_revision = '.$params['revision_id'].'
;';
    pwg_query($query);

    // $country_code = geoip_country_code_by_name($_SERVER['REMOTE_ADDR']);
    // $country_name = geoip_country_name_by_name($_SERVER['REMOTE_ADDR']);

    $country_code = 'unkown';
    $country_name = 'unkown';
    
    notify_mattermost('['.$conf['mattermost_notif_type'].'] user #'.$user['id'].' ('.$user['username'].') deleted revision #'.$params['revision_id'].' from extension #'.$eid.' ('.$extension_name.') , IP='.$_SERVER['REMOTE_ADDR'].' country='.$country_code.'/'.$country_name);

    pwg_activity('pem_revision', $params['revision_id'], 'delete', array('extension'=>$params['extension_id']));
  }
  else
  {
    $logger->info('Action not allowed in FILE = '.__FILE__.', LINE = '.__LINE__);
    set_status_header(489);
    exit();
  }
}

function ws_pem_revisions_get_language_info($params, &$service)
{
  global $conf, $logger;

  if (isset($params['revision_id']) and !isset($params['extension_id']))
  {
    $query = '
  SELECT
      *
    FROM '.PEM_REV_TABLE.'
    WHERE id_revision = '.$params['revision_id'].'
  ;';
    $result = pwg_query($query);
    while ($row = pwg_db_fetch_assoc($result))
    {
      $revision = $row;
    }

    $archive_path = get_revision_src($revision['idx_extension'], $params['revision_id'], $revision['url']);

    $cmd = 'unzip -t '.$archive_path.' | grep -E "/language/[a-z]{2,3}_[A-Z]{2,3}/ "';
    $logger->info($cmd);
    exec($cmd, $exec_output);

    $languages_in_archive = array();

    if (count($exec_output) > 0)
    {
      foreach ($exec_output as $exec_output_line)
      {
        if (preg_match('#/language/([a-z]{2,3}_[A-Z]{2,3})/\s*OK#', $exec_output_line, $matches))
        {
          $languages_in_archive[ $matches[1] ] = 1;
        }
      }
    }

    $languages_cur = array_keys($languages_in_archive);

    $params['extension_id'] = $revision['idx_extension'];
  }
  elseif (isset($params['extension_id']) and !isset($params['revision_id']))
  {
    //
    // what is the list of languages in SVN/Git
    //
    $svn_url = null;
    $git_url = null;
    $language_candidates = array();

    $query = '
SELECT
    svn_url,
    git_url
  FROM '.PEM_EXT_TABLE.'
  WHERE id_extension = '.$params['extension_id'].'
;';
    $result = pwg_query($query);
    while ($row = pwg_db_fetch_assoc($result))
    {
      if (!empty($row['svn_url']))
      {
        $svn_url = $row['svn_url'];
      }

      if (!empty($row['git_url']))
      {
        $git_url = $row['git_url'];
      }
    }

    if (!empty($svn_url))
    {
      $svn_command = 'svn list '.$svn_url.'/language';
      exec($svn_command, $language_candidates);
    }

    if (!empty($git_url))
    {
      $temp_path = PHPWG_ROOT_PATH.'_data' . '/git_clone';
      if (mkgetdir($temp_path, MKGETDIR_DEFAULT&~MKGETDIR_DIE_ON_ERROR))
      {
        $temp_path .= '/' . md5(uniqid(rand(), true));

        $cmd = 'git clone -n --depth=1 --filter=tree:0 '.$git_url.' '.$temp_path;
        $cmd.= ' && cd '.$temp_path;
        $cmd.= ' && git sparse-checkout set --no-cone language';
        $cmd.= ' && git checkout';
        $cmd.= ' 2>&1';

        exec($cmd, $out);

        if ( ($glob = glob($temp_path.'/language/*_*')) !== false)
        {
          foreach ($glob as $file)
          {
            $language_candidates[] = basename($file);
          }
        }

        include_once(PHPWG_ROOT_PATH.'admin/include/functions.php');
        deltree($temp_path);
      }
    }

    $languages_cur = array();

    foreach ($language_candidates as $lang)
    {
      if (preg_match('#^([a-z]{2,3}_[A-Z]{2,3})#', $lang, $matches))
      {
        $languages_cur[] = $matches[1];
      }
    }
  }
  else
  {
    return new PwgError(WS_ERR_INVALID_PARAM, 'Provide either revision_id or extension_id');
  }

  //
  // details about languages
  //
  $info_of_language = array();

  $query = '
  SELECT
      id_language,
      code,
      name
    FROM '.PEM_LANG_TABLE.'
  ;';
  $result = pwg_query($query);
  while ($row = pwg_db_fetch_assoc($result))
  {
    if (isset($conf['language_english_names'][$row['code']]))
    {
      $row['english_name'] = $conf['language_english_names'][$row['code']];
    }

    $info_of_language[ $row['code'] ] = $row;
  }

  //
  // language_ids is the list of languages in the "current" revision (or upcoming)
  //
  $language_ids = array();

  foreach ($languages_cur as $lang_code)
  {
    if (isset($info_of_language[$lang_code]))
    {
      $language_ids[] = $info_of_language[$lang_code]['id_language'];
    }
  }

  //
  // find the reference revision_id based on the extension id: the most recent
  // revision
  //
  $ref_revision_id = null;

  $query = '
  SELECT
      id_revision
    FROM '.PEM_REV_TABLE.'
    WHERE idx_extension = '.$params['extension_id'];

  if (isset($revision))
  {
    $query.= '
      AND date < '.$revision['date'].'
  ';
  }

  $query .= '
    ORDER BY date DESC
    LIMIT 1
  ;';
  $result = pwg_query($query);
  while ($row = pwg_db_fetch_assoc($result))
  {
    $ref_revision_id = $row['id_revision'];
  }

  // what is the list of languages in reference revision (the previous
  // revision, most of the time)
  $languages_old = array();

  if(!empty($ref_revision_id)) 
  {
    $query = '
SELECT
    code
  FROM '.PEM_REV_LANG_TABLE.'
    JOIN '.PEM_LANG_TABLE.' ON idx_language = id_language
  WHERE idx_revision = '.$ref_revision_id.'
;';
    $result = pwg_query($query);
    while ($row = pwg_db_fetch_assoc($result))
    {
      $languages_old[] = $row['code'];
    }
  }

  //
  // new languages
  //
  $languages_new = array_diff($languages_cur, $languages_old);

  $desc_extra = '';
  if (count($languages_new) > 0) {
    $desc_extra = 'New languages:';
  }

  foreach ($languages_new as $lang) {
    if (isset($info_of_language[$lang])) {
      $desc_extra.= "\n".'* ';
      if (isset($info_of_language[$lang]['english_name'])) {
        $desc_extra.= $info_of_language[$lang]['english_name'].' ('.$info_of_language[$lang]['name'].')';
      }
      else {
        $desc_extra.= $info_of_language[$lang]['name'];
      }
    }
  }

  $rev_lang_info = array(
    'language_ids' => $language_ids,
    'desc_extra' => $desc_extra,
  );

  return $rev_lang_info;  
}
