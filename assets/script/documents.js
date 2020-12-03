$(document).ready(function() {

    $('.btn-upload').click(function(e) {
        e.preventDefault();
        $('.document-action-forms form:not(#form-upload)').hide();
        $('#form-upload').toggle();
    })

    $('.btn-new-folder').click(function(e) {
        e.preventDefault();
        $('.document-action-forms form:not(#form-new-folder)').hide();
        $('#form-new-folder').toggle();
    });

});
