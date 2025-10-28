<!DOCTYPE html>
<html lang="{$lang_info.code}"  dir="{$lang_info.direction}">
  <head>
    {* Add title and decsription*}
    <title>Piwigo extension manager</title>
    <meta name="description" content="{'Change appearance with themes. Add features with plugins. Extensions require just a few clicks to get installed. 350 extensions available, and growing!'|translate|escape:html}">

    {*Add favicon*}
    <link rel="shortcut icon" type="image/x-icon" href="themes/default/icon/favicon.ico">

    {* Required meta tags *}
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    {* Bootstrap CSS *}
    <link rel="stylesheet" type="text/css" href="{$PEM_ROOT_URL_PLUGINS}vendor/bootstrap/css/bootstrap.min.css">
    <link rel="stylesheet" type="text/css" href="{$PEM_ROOT_URL}admin/themes/default/fonts/open-sans/open-sans.css">

    {* Jquery *}
    <script src="{$PEM_ROOT_URL_PLUGINS}vendor/jquery/jquery-3.6.0.min.js"></script>

    {* Bootstrap javascript *}
    <script src="{$PEM_ROOT_URL_PLUGINS}vendor/bootstrap/js/bootstrap.bundle.min.js"></script>

    {*Fontello css*}
    <link rel="stylesheet" type="text/css" href="{$PEM_ROOT_URL_PLUGINS}vendor/fontello/css/fontello.css">

    {* Selectize *}
    <script src="{$PEM_ROOT_URL_PLUGINS}vendor/js/selectize.min.js"></script>
    <link rel="stylesheet" type="text/css" href="{$PEM_ROOT_URL_PLUGINS}vendor/css/selectize.clear.css">

    {* Main CSS & JS file, common to all tpls *}
    <link rel="stylesheet" type="text/css" href="{$PEM_ROOT_URL_PLUGINS}styles/css/main.css">
    <script src="{$PEM_ROOT_URL_PLUGINS}template/js/main.js" require="jquery"></script>

    {* Footer CSS *}
    <link rel="stylesheet" type="text/css" href="{$PEM_ROOT_URL_PLUGINS}styles/css/footer.css">

    {* Plausible *}
    <script defer data-domain="piwigo.org" src="https://plausible.piwigo.org/js/script.js"></script>
    
  </head>
  <body>
