<section id="addExt-popin">
  <div>
    <div class="modal fade" id="addExtModal" tabindex="-1" aria-labelledby="generalInfoModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="generalInfoModalLabel"><i class="icon-circle-info"></i>General information</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <form method="POST" action="{$f_action}" enctype="multipart/form-data">

            <div class="modal-body">
              {* Extension name *}
              <div class="mb-3 form-group">
                <label for="extension_name" class="form-label w-100 ">Name</label>
                <input type="text" name="extension_name" size="35" maxlength="255" value="{$extension_name}" class="w-100 form-control" required {if $translator}disabled="disabled"{/if}/>
              </div>

              {* Extension Author *}
              <input type="hidden" name="authors[]" value="{$author.uid}">

              {* Extension category *}
              <div class="mb-3 form-group">
                <label for="extension_category_select" class="col-12">Category</label>
        
                <select class="form-select w-100" id="extension_category_select" name="extension_category[]">
{foreach from=$CATEGORIES_INFO item=category}
                  <option value="{$category.cid}" {if $category.cid == $categories_of_extension.id_category}selected{/if}>{$category.name}</option>
{/foreach}
                </select>
              </div>

            </div>

            <input type="hidden" name="pem_action" value="add_ext">

            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
              <input type="submit" class="btn btn-primary" value="{'Submit'|@translate}" name="submit" />
            </div>

          </form>
        </div>
      </div>
    </div>
  </div>
</section>