(function($) {
    var form = $('#elementForm');
    var textarea = form.find('textarea');
    codeMirror = CodeMirror.fromTextArea(textarea[0], {
        mode: {
            name: "xml",
            htmlMode: true,
            alignCDATA: true},
        keyMap: 'sublime',
        styleActiveLine: true,
        matchBrackets: true,
        lineNumbers: true,
        theme: 'neo'
    });

    codeMirror.on('change', function() {
        codeMirror.save();
    });
})(jQuery);
