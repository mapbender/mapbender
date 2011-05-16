$(function() {

// Initialize all elements by calling their init function with their options
$.each(Mapbender.configuration.elements, function(id, data) {
	if(typeof($.mapbender[data.init]) == 'function') {
		$('#' + id)[data.init](data.options);
	}
});

});
