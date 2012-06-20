/**
 * Mapbender Manager form customization: YAML editor.
 */
(function() {
    var form = $('form');
    var yaml = form.find('textarea.code-yaml');
    yaml.each(function() {
        var editor = CodeMirror.fromTextArea(this, {
            mode: 'yaml',
            lineNumbers: true,
            onCursorActivity: function() {
                editor.setLineClass(hlLine, null);
                hlLine = editor.setLineClass(editor.getCursor().line,
                    "activeline");
            }
        });
        var hlLine = editor.setLineClass(0, "activeline");
    });
})();

