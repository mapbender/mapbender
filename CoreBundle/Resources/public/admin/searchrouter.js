(function() {

    var addYamlEditor = function(textarea) {
        var _textarea = textarea[0];

        var editor = CodeMirror.fromTextArea(_textarea, {
            mode: 'yaml',
            lineNumbers: true,
            onCursorActivity: function() {
                editor.setLineClass(hlLine, null);
                hlLine = editor.setLineClass(editor.getCursor().line,
                    "activeline");
            }
        });
        var hlLine = editor.setLineClass(0, "activeline");
        textarea.data('editor', editor);
    };

    var context = $('form#elementForm');
    var init = function() {
        addYamlEditor($('textarea', context));
    }

    init();
})();

