<?php
/**
 * List view display page with list of extensions
 * For example the different category pages
 */

if (isset($_GET['cId']))
{
  $current_category_page_id = $_GET['cId'];

  $query = '
SELECT
    idx_category AS cId,
    COUNT(*) AS count
  FROM '.PEM_EXT_CAT_TABLE.'
  WHERE idx_category  = '.$current_category_page_id.'
  GROUP BY idx_category
;';

  $nb_ext_of_category = query2array($query, 'cId', 'count');

  // Get category name with Id and count
  $query = '
SELECT
      id_category as cId,
      name
  FROM '.PEM_CAT_TABLE.'
    WHERE id_category  = '.$current_category_page_id.'
;';
      
  $current_category_page_info = query2array($query);
  $current_category_page_info = $current_category_page_info[0];

  $current_category_page_info['extension_count'] = $nb_ext_of_category[$current_category_page_id];

  $current_category_page_info['name_plural_EN'] = $current_category_page_info['name'].'s';

  //Get spotlighted extension
  $pem_spotlight_extensions_ids = conf_get_param('pem_spotlight_extensions',array());

  $query = '
  SELECT
      id_extension AS eId,
      name,
      description
    FROM '.PEM_EXT_TABLE.'
    WHERE id_extension = ('.$pem_spotlight_extensions_ids[$current_category_page_id].')
  ;';

  $result= query2array($query);

  $current_category_page_info['spotlight_extension'] = $result[0];

  //Get List of authors for filter
  $query = '
  SELECT DISTINCT
      aT.idx_user as uId,
      uT.username,
      ecT.idx_category as cId,
      aT.idx_extension as eId
    FROM '.PEM_AUTHORS_TABLE.' as aT
    JOIN '.USERS_TABLE.' as uT on id = aT.idx_user
    JOIN '.PEM_EXT_CAT_TABLE.' as ecT on ecT.idx_extension = aT.idx_extension
    WHERE ecT.idx_category = '.$current_category_page_id.'
  ;';
  $authors= query2array($query, 'uId');

  //Get List of authors for filter
  $query = '
  SELECT 
      id_version,
      version
    FROM '.PEM_VER_TABLE.'
    ORDER BY id_version DESC
 ;';
  $versions= query2array($query, 'id_version');

  $template->set_filename('pem_page', realpath(PEM_PATH . 'template/list_view.tpl'));
  
  $template->assign(
    array(
    'PEM_PATH' => PEM_PATH,
    'CATEGORY' => $current_category_page_info,
    'AUTHORS' => $authors,
    'VERSIONS' => $versions,
    )
  );
  
}
else
{
  http_response_code(404);
  $template->set_filenames(array('pem_page' => realpath(PEM_PATH . 'template/404.tpl')));
}