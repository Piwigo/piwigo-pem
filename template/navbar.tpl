<link rel="stylesheet" type="text/css" href="{$PEM_ROOT_URL_PLUGINS}styles/css/navbar.css">

{*Start of footer tpl*}
<nav class="navbar navbar-expand-lg navbar-light bg-light justify-content-between py-3">
  
  <div class="container">
    <div class="navbar-brand brand">
      <a class="d-inline-block" href="https://www.piwigo.org">
        <img src="{$PEM_ROOT_URL_PLUGINS}images/porg.svg" alt="piwigo logo in orange and grey">
      </a>
      <a class="d-inline-block fw-bold" id="link-back-porg" href="https://www.piwigo.org">Accueil piwigo.org</a>
    </div>

    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle Navigation">
      <span class="navbar-toggler-icon"></span>
    </button>

    <div class="collapse navbar-collapse justify-content-end" id="navbarNav">
      <ul class="navbar-nav">
        <li class="nav-item fw-bold" id="extensions-link">
          <a class="nav-link" href="{$PEM_ROOT_URL}"><i class="icon-house"></i>Extensions</a>
        </li>
{foreach from=$CATEGORIES_INFO item=category}
        <li class="nav-item active fw-bold">
          <a class="nav-link" href="{$PEM_ROOT_URL}index.php?cid={$category.cid}&page=1"><i class="{$category.icon_class}"></i>{$category.plural_name}</a>
        </li>
{/foreach}
        <li class="nav-item fw-bold position-relative" id="account-link">
          <a class="nav-link pe-0" href="{$ACCOUNT_URL}"><i class="icon-user"></i>Your account</a>
{if $USER_STATUS != 'guest'}
          <div id="account-dropdown" class="dropdown">
            <div class="dropdown-content d-flex flex-column ">
  {if $USER_STATUS == 'webmaster' or $USER_STATUS == 'admin'}
              <a class="w-100" href="{$PEM_ROOT_URL}admin.php">Administration</a>
  {/if}
              <a class="w-100" href="{$PEM_ROOT_URL}?act=logout">Logout</a>
            </div>
          </div>
{/if}
        </li>
      </ul>
    </div> 
  </div>
</nav>

