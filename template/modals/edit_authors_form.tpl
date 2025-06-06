<section id="authors-popin">
  <div>
    <div class="modal fade" id="authorsModal" tabindex="-1" aria-labelledby="authorsModalLabel" aria-hidden="true">
      <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="authorsModalLabel"><i class="icon-user-group me-2"></i>{'Edit authors'|translate}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" enctype="multipart/form-data" autocomplete="off" id="editAuthorsForm">

            <div class="modal-body">
              <h4>{'Add authors'|translate}</h4>
              {* Extension Authors *}
              <div class="mb-3 form-group">
                <label for="extension_author_select" class="col-12">{'Authors'|translate}</label>
                <select required class="extension_author_select col-12" data-selectize="author"
                  placeholder="{'Type in a search term'|translate}"
                  data-create="true" name="author">
                  <option selected>{'Type in a search term'|translate}</option>
{foreach from=$ALL_AUTHORS item=author}
  {if in_array($author, $authors)}
  {else}
                  <option value="{$author.uid}" >{$author.username}</option>
  {/if}
{/foreach}
                </select>
              </div>

              <h4>{'Manage authors'|translate}</h4>
              <div>
{foreach from=$authors item=author}
                <div class="d-block">
                  <strong>{$author.username}</strong>
  {if isset($u_owner_id) && $u_owner_id == $author.uid}
                    <p class="d-inline-block m-0">({'Owner'|translate})</p>   
  {else if isset($can_modify) && true == $can_modify}
    {if $current_user_id != $author.uid}
      <button class="link-primary" type="button" onclick="deleteAuthor({$author.uid}, {$extension_id})">{'Delete'|translate}</button>
      <p class="d-inline-block m-0">|</p>
    {/if}
      <button class="link-primary" onclick="setOwner({$author.uid}, {$extension_id})">{'Set as owner'|translate}</button>
  {/if}
{/foreach}
                </div>
              </div>
            </div>

            <input type="hidden" name="pem_action" value="edit_authors">

            <div class="modal-footer mt-3">
              <button type="button" class="btn btn-tertiary small-btn" data-bs-dismiss="modal">{'Close'|translate}</button>
              <div class="spinner-border d-none" role="status">
                <span class="visually-hidden">{'Loading...'|translate}</span>
              </div>
              <input type="submit" class="btn btn-primary small-btn" value="{'Save Changes'|translate}" name="submit" onclick="onSubmitDisplaySpinner('editAuthorsForm');"/>
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</section>
