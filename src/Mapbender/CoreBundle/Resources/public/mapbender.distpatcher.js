(function($) {
    Mapbender = Mapbender || {};
    Mapbender.Util = Mapbender.Util || {};
    Mapbender.Util.addDispatcher = function (doc) {
        $(doc).on('click', '[mb-action]', function(e) {
            var clel = $(e.target);
            var actionValue = clel.attr('mb-action');
            if(Mapbender.declarative && Mapbender.declarative[actionValue] && typeof Mapbender.declarative[actionValue] === 'function') {
                e.preventDefault();
                Mapbender.declarative[actionValue](clel);
            }
            return false;
        });
    };
    Mapbender.Util.addDispatcher(document);
})(jQuery);