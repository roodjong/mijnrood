$(document).ready(function() {

    $('.btn-upload').click((e) => documentActions('#form-upload', '.btn-upload') && false);
    $('.btn-new-folder').click((e) => documentActions('#form-new-folder', '.btn-new-folder') && false);

    $('.btn-rename').click(function(e) {
        let $this = $(this);
        let entry = entryInfo(this);
        hideButtons(this);

        let newName = prompt('Nieuwe naam', entry.name);
        if (newName !== null) {
            let route = entry.type == 'folder' ? 'member_documents_rename_folder' : 'member_documents_rename';
            let renameUrl = Routing.generate(route, { [entry.type + 'Id']: entry.id }, true);
            $.post(renameUrl, {
                name: newName
            }, function(r) {
                if (r.status == 'renamed') {
                    $this.parents('.entry').find('.slot-name').text(newName);
                    $this.data('name', newName);
                } else {
                    alert('Onverwachte melding: ' + r.status);
                }
            }, 'JSON');
        }
    });

    $('.btn-delete').click(function(e) {
        e.preventDefault();

        let $this = $(this);
        let entry = entryInfo(this);
        hideButtons(this);

        let conf = confirm('Weet je zeker dat je "' + entry.name + '" wil verwijderen?' + (entry.type == 'folder' ? ' Alle documenten in deze map zullen verplaatst worden naar de bovenliggende map.' : ''));
        if (conf) {
            let route = entry.type == 'folder' ? 'member_documents_delete_folder' : 'member_documents_delete';
            let deleteUrl = Routing.generate('member_documents_delete_folder', { [entry.type + 'Id']: entry.id });
            $.post(deleteUrl, {}, function(r) {
                if (r.status == 'deleted') {
                    $this.parents('.entry').remove();
                    if (entry.type == 'folder')
                        location.reload();
                    else
                        checkDocumentListEmpty();
                } else {
                    alert('Onverwachte melding: ' + r.status);
                }
            }, 'JSON');
        }
    });

    $('.btn-move').click(function(e) {
        e.preventDefault();

        let $this = $(this);
        let entry = entryInfo(this);
        hideButtons(this);

        let formSelector = entry.type == 'folder' ? '#form-move-folder' : '#form-move';

        $('#move-header').text((entry.type == 'folder' ? 'Map "' : 'Document "') + entry.name + '"');
        $('#move_type').val(entry.type);
        $('#move_id').val(entry.id);
        $('#move_file').val(entry.name);

        documentActions(formSelector, '#nothing');
    });

});

function documentActions(formSelector, buttonSelector) {
    $('.document-action-forms form:not(' + formSelector + ')').hide();
    $('.document-folder-actions a:not(' + buttonSelector + ')').removeClass('active');
    $(buttonSelector).toggleClass('active');
    $(formSelector).toggle();
}

function checkDocumentListEmpty() {
    if ($('.document-list tbody tr:not(.folder-empty)').length)
        $('.folder-empty').hide();
    else
        $('.folder-empty').show();
}

function entryInfo(t) {
    let $this = $(t);
    let id = $this.parents('.entry').data('id');
    let name = $this.parents('.entry').find('.slot-name').text();
    let folder = $this.parents('.entry').hasClass('folder');

    return {
        type: folder ? 'folder' : 'document',
        id: id,
        name: name
    };
}

function hideButtons(t) {
    $(t).parents('.document-actions').find('input').prop('checked', false);
}
