$(function() {
    var actions = $('div.entity-actions');
    actions.find('a.action-view').button({
        icons: { primary: 'ui-icon-folder-open' },
        text: false
    });
    actions.find('a.action-edit').button({
        icons: { primary: 'ui-icon-gear' },
        text: false
    });
    actions.find('a.action-clone').button({
        icons: { primary: 'ui-icon-copy' },
        text: false
    });
    actions.find('a.action-delete').button({
        icons: { primary: 'ui-icon-trash' },
        text: false
    });
    actions.buttonset();
});

