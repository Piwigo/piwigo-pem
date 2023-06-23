<?php
defined('PEM_PATH') or die('Hacking attempt!');

// include_once(PEM_PATH . 'include/constants.inc.php');
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
        'default' => null,
        'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE,'info'=>'use category id'
      ),
      'page' => array(
        'default'=>null,
        'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE,
      ),
      'sort_by' => array(
        'default'=>'date_desc',
        'info'=>'date_desc, date_asc, a_z, z_a'
      ),
      'filter_version' => array(
        'default' => null,
        'type'=>WS_TYPE_FLOAT|WS_TYPE_POSITIVE,
        'info'=>'use a piwigo version number'
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
        'type'=>WS_TYPE_INT|WS_TYPE_POSITIVE,'info'=>'use category id'
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

}

/**
 * Get list of all extensions
 * Filter by category or version
 * Can apply sort order and limit number or extensions returned with page
 */
function ws_pem_extensions_get_list($params, &$service)
{
  global $conf;

  $extensions_per_page = conf_get_param('extensions_per_page',15);

  if(isset($params['category_id']))
  {
    $cid = $params['category_id']; 
  }

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

  $offset = ($extensions_per_page * $params['page']) - $extensions_per_page;
  
  $revision_ids = array();
  $revision_infos_of = array();
  $extension_ids = array();
  $extension_infos_of = array();
  $author_ids = array();
  $author_infos_of = array();

  $query = '
  SELECT 
  r.idx_extension,
  r.id_revision,
  r.date AS latest_date,
  ec.idx_category
FROM 
  (SELECT 
     idx_extension, 
     MAX(date) AS latest_date
   FROM 
   '.PEM_REV_TABLE.'
   GROUP BY 
     idx_extension) AS latest_revisions
INNER JOIN 
'.PEM_REV_TABLE.' AS r
  ON latest_revisions.idx_extension = r.idx_extension AND latest_revisions.latest_date = r.date
INNER JOIN 
'.PEM_EXT_CAT_TABLE.' AS ec
  ON r.idx_extension = ec.idx_extension';

  if(isset($cid))
  {
    $query .= '
  WHERE 
    ec.idx_category = '.$cid;
  }

  $query .= ';';

  $all_revision_ids = query2array($query, null, 'id_revision');

  $nb_total = count($all_revision_ids);

  if (count($all_revision_ids) == 0)
  {
    message_die(
      'No extensions match your filter',
      'Most recent extensions',
      false
    );
  }

  $revision_ids = array_slice($all_revision_ids, $offset , $extensions_per_page, true);
  
  $nb_pages = ceil(count($all_revision_ids)/ $extensions_per_page);

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

  $revisions = array();

  foreach ($revision_ids as $revision_id)
  {
    $extension_id = $revision_infos_of[$revision_id]['idx_extension'];
    $authors = get_extension_authors($extension_id);
    $screenshot_infos = get_extension_screenshot_infos($extension_id);

    array_push(
      $revisions,
      array(
        'revision_id' => $revision_id,
        'extension_id' => $extension_id,
        'extension_name' => $extension_infos_of[$extension_id]['name'],
        'rating_score' => $extension_infos_of[$extension_id]['rating_score'],
        'rating_score_stars' => generate_static_stars($extension_infos_of[$extension_id]['rating_score']),
        'nb_reviews' => !empty($extension_infos_of[$extension_id]['nb_reviews']) ? sprintf(l10n('%d reviews'), $extension_infos_of[$extension_id]['nb_reviews']) : null,
        'about' => nl2br(
          htmlspecialchars(
            strip_tags($extension_infos_of[$extension_id]['description'])
            )
          ),
        'authors' => array_combine($authors, get_author_name($authors)),
        'name' => $revision_infos_of[$revision_id]['version'],
        'compatible_versions' => implode(', ', $versions_of[$revision_id]),
        'languages' => isset($languages_of[$revision_id]) ?
            $languages_of[$revision_id] : array(),
        'description' => nl2br(
          htmlspecialchars(
            strip_tags($revision_infos_of[$revision_id]['description'])
            )
          ),
        'date' => date('Y-m-d', $revision_infos_of[$revision_id]['date']),
        'thumbnail_src' => $screenshot_infos
          ? $screenshot_infos['thumbnail_src']
          : null,
        'screenshot_url' => $screenshot_infos
          ? $screenshot_infos['screenshot_url']
          : null,
        'revision_url' => sprintf(
          'extension_view.php?eid=%u&amp;rid=%u#rev%u',
          $extension_id,
          $revision_id,
          $revision_id
          ),
        'downloads' => isset($download_of_extension[$extension_id]) ?
                        $download_of_extension[$extension_id] : 0,
        'categories' => $categories_of_extension[$extension_id],
        'tags' => empty($tags_of_extension[$extension_id]) ? array() : $tags_of_extension[$extension_id] ,
      )
    );
  }

  // sort alphabetically by name
  usort($revisions, $sort_by);

  if (!isset($_REQUEST['format']))
  {
    //Echo to be compatible with previous version of Piwigo
    echo serialize($revisions);
    exit;
  }

  return array(
    'revisions' => $revisions,
    'nb_pages' => $nb_pages,
    'nb_total_extensions' => $nb_total,
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
  return strcmp($a['date'], $b['date']);
}

function compare_extension_date_asc($a, $b)
{
  return strcmp($b['date'], $a['date']);
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