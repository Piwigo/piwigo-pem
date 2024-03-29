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

define('PHPWG_ROOT_PATH', '../../../');

include_once(PHPWG_ROOT_PATH .'include/common.inc.php');
include_once('../include/constants.inc.php');
include_once('../include/functions_language.inc.php');

// Language selection
$_SESSION['language'] = get_current_language();

$query = '
SELECT
    idx_category,
    COUNT(*) AS counter
  FROM '.PEM_EXT_CAT_TABLE.'
  GROUP BY idx_category
;';
$nb_ext_of_category = query2array($query, 'idx_category', 'counter');

$query = '
SELECT
    id_category AS id,
    c.name AS default_name,
    ct.name
  FROM '.PEM_CAT_TABLE.' AS c
  LEFT JOIN '.PEM_CAT_TRANS_TABLE.' AS ct
    ON c.id_category = ct.idx_category
    AND ct.idx_language = '.$_SESSION['language']['id'].'
  ORDER BY name ASC
;';
$output = query2array($query);
foreach ($output as $i => $category) {
  if (empty($output[$i]['name']))
  {
    $output[$i]['name'] = $output[$i]['default_name'];
  }
  unset($output[$i]['default_name']);
  
  $output[$i]['counter'] = 0;
  if (isset($nb_ext_of_category[ $category['id'] ])) {
    $output[$i]['counter'] = $nb_ext_of_category[ $category['id'] ];
  }
}

$format = 'json';
if (isset($_GET['format'])) {
  $format = strtolower($_GET['format']);
}

switch ($format) {
  case 'json' :
    echo json_encode($output);
    break;
  case 'php' :
    echo serialize($output);
    break;
  default :
    echo json_encode($output);
}
?>
