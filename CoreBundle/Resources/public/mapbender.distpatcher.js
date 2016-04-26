(function($){
    $(document).on('click', '[mb-action]', function(e){
        var clel = $(e.target);
        if(Mapbender.declarative && Mapbender.declarative[clel.attr('mb-action')] && typeof Mapbender.declarative[clel.attr('mb-action')] === 'function'){
            e.preventDefault();
            Mapbender.declarative[clel.attr('mb-action')](clel);
        }
        return false;
    });
})(jQuery);