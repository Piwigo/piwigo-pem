$(document).ready(function()
{
  //set ajax action on delete click in modal
  const deleteRevisionModal = document.getElementById('deleteRevisionModal');

  deleteRevisionModal.addEventListener('show.bs.modal', event => {
    const buttonDeleteRevision = event.relatedTarget
    // Extract info from data-bs-* attributes
    const ext_id = buttonDeleteRevision.getAttribute('data-bs-ext_id')
    const rev_id = buttonDeleteRevision.getAttribute('data-bs-rev_id')
    const pem_root_url = buttonDeleteRevision.getAttribute('data-bs-root_url')
    jQuery('#deleteRevisionModal #deleteRevision').attr("onClick", 'deleteRevision('+rev_id+', '+ext_id+', "'+pem_root_url+'/index.php?eid='+ext_id+'")')

  });

});

//display Form spinner when waiting for submit
function onSubmitDisplaySpinner(formId)
{
  const inputs = jQuery('#'+formId+' [required]')

  console.log(inputs)

  var allFieldsFilled = true
  jQuery(inputs).each(function(i, input){
    if(jQuery(input).val() == ''){
      allFieldsFilled = false;
    }
  });
  
  if(false != allFieldsFilled){
    jQuery('#'+formId+' .btn').addClass('d-none');
    jQuery('#'+formId+' .spinner-border').removeClass('d-none');
  }
}

// Ajax request to delete a revision from an extension
function deleteRevision(revisionId,extensionId, link )
{
  jQuery.ajax({
    type: 'GET',
    dataType: 'json',
    async: false,
    url: 'ws.php?format=json&method=pem.revisions.deleteRevision&extension_id=' + extensionId + '&revision_id=' + revisionId+ '&pwg_token=' + pwg_token,
    data: { ajaxload: 'true' },
    success: function (data) {
      if ( window.history.replaceState ) {
        window.history.replaceState( null, null, window.location.href );
      }
      window.location = window.location.href;
    },
    error: function (data) {
      jQuery('#deleteRevisionModal .modal-body').html('<p>'+data.responseText+'</p>')
    }
  });
}