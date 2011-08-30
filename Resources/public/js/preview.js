$(function() {
	$('form.wmsregister').submit(function(){
		var target = $(this).attr('action');
		
		$('div#wmspreview').dialog({
			modal:true,
			autoOpen:true,
			width: 600,
			height: 650,
			title: 'WMS Preview',
			position: [300,50],
			show: 'slide',
			buttons:{
				'Abbrechen': function(){
					$(this).dialog('destroy');
				},
				'Speichern': function(){
					$(this).find('form.wms').submit();	
				}
				
			}
		});
		
		var values = {};
		$.each($(this).serializeArray(), function(i, field) {
    		values[field.name] = field.value;
		});
		
		$.ajax({
			url: target,
			data: values,
			type: 'POST',
			success: function(data) {
				$('div#wmspreview').empty().html(data);
				$( "#tabs" ).tabs();				
			}
		});
		
		return false;
	});
});
