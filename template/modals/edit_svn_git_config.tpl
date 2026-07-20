{*Start of svn_git_config tpl*}
<section id="editSvnGit-popin">
  <div>
    <div class="modal fade" id="editSvnGitModal" tabindex="-1" aria-labelledby="editSvnGitModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog modal-dialog-scrollable modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="editSvnGitModalLabel"><i class="icon-git-alt"></i> {'SVN & Git configuration'|translate}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>

          <form method="POST" enctype="multipart/form-data" autocomplete="off" id="editSvnGitForm">
            
            <div class="modal-body">

              <h4>{'Edit configuration'|translate}</h4>

              <div class="mb-3 form-check d-inline-block">
                <input class="form-check-input" type="radio" name="type" id="type_svn" value="svn" {if $TYPE == 'svn'} checked="checked"{/if}>
                <label class="form-check-label" for="type">
                  SVN
                </label>
              </div>

              <div class="mb-3 ms-3 form-check d-inline-block">
                <input class="form-check-input" type="radio" name="type" id="type_git" value="git" {if $TYPE == 'git'} checked="checked"{/if}>
                <label class="form-check-label" for="type">
                  Git
                </label>
              </div>

              <div class="mb-3 form-group">
                <label for="repository_url" class="col-12">{'Repository URL'|translate}</label>
                <input type="text" name="url" id="repository_url" size="30" class="w-100 form-control" {if $TYPE == 'svn'}value="{$SVN_URL}" {else if $TYPE == 'git'}value="{$GIT_URL}" {/if} required>
              </div>

              <div id="config_type" class="col-12 mt-2">

{if isset($ROOT_DIR)}
              <div id="svn_config" class="mb-3 form-group">
                <label for="root_dir" class="col-12">{'Archive root directory'|translate}</label>
                <input type="text" name="root_dir" id="root_dir" size="30" class="w-100 form-control" {if isset($ROOT_DIR)}value="{$ROOT_DIR}"{/if}>
              </div>
{/if}

{if isset($ARCHIVE_NAME)}
              <div id="" class="mb-3 form-group">
                <label for="archive_name" class="col-12">{'Archive name'|translate}</label>
                <input type="text" name="archive_name" id="archive_name" size="30" class="w-100 form-control" {if isset($ARCHIVE_NAME)}value="{$ARCHIVE_NAME}"{/if}>
                <p class="form-text">{'% will be replaced by version number'|translate}</p>
              </div>
{/if}

            </div>
{if (isset($admin) && $admin == true)}
  {if isset($GIT_URL) ||isset($SVN_URL)}
              <div class="mb-3 form-group">
                <button class="link-primary" onclick="deleteSVNGitConfig({$extension_id})">{'Delete SVN/Git data'|translate}</button>
              </div>
  {/if}   
{/if}             

              
{if isset($SVN_INFOS) && !empty($SVN_INFOS)}
                <h4>{'Information'|translate}</h4>
  {foreach from=$SVN_INFOS item=info}
                <p>{$info}</p>
  {/foreach}
{/if}

            </div>

            <input type="hidden" name="pem_action" value="edit_svn_git">

            <div class="modal-footer">
              <button type="button" class="btn btn-tertiary small-btn" data-bs-dismiss="modal">{'Close'|translate}</button>
              <div class="spinner-border d-none" role="status">
                <span class="visually-hidden">{'Loading...'|translate}</span>
              </div>
              <input type="submit" class="btn btn-primary small-btn" value="{'Save Changes'|translate}" name="submit" onclick="onSubmitDisplaySpinner('editSvnGitForm');"/>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</section>