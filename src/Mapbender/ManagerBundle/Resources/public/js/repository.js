$(function() {
  var showInfoBox = function(){
    $(".infoMsgBox").addClass("hide");
    $(this).find(".infoMsgBox").removeClass("hide");

    $(document).one("click", function(){
      $(".infoMsgBox").addClass("hide");
    });
    return false;
  }

  var toggleInstanceTableStatus = function(e,target) {
    if(typeof target === "undefined") {
        var maxCount = $(this).closest('tr').siblings().length;
        var myClass = $(this).closest('td').attr('data-cb');
        var count = $(this).closest('tbody').find('td[data-cb="' + myClass + '"]').find('input:checked').length;
    } else {
        var myClass = target;
        var maxCount = $('td[data-cb="' + myClass + '"]').length;
        var count = $('td[data-cb="' + myClass + '"]').closest('tbody').find('td[data-cb="' + myClass + '"]').find('input:checked').length;
    }

    // none checked
    if(count === 0) {
        $('#' + myClass).removeClass().addClass('iconCheckbox');
    // all checked
    } else if(maxCount === count) {
        $('#' + myClass).removeClass().addClass('iconCheckboxActive');
    // some checked
    } else {
        $('#' + myClass).removeClass().addClass('iconCheckboxHalf');
    }
  }

  var toggleInstanceRowsStatus = function() {
  	var myClass = $(this).attr('id');

		$(this).closest('table')
			.find('td[data-cb="' + myClass + '"]')
			.find('input').prop("checked", !$(this).hasClass("iconCheckboxActive"))
			.change();

  	toggleInstanceTableStatus(null, myClass);
  }

  $("#instanceTable").on("click", ".iconInfo", showInfoBox); 
  $('#instanceTable').on("click", ".checkbox", toggleInstanceTableStatus);
  $('#instanceTable').on("click", "thead span", toggleInstanceRowsStatus);

  toggleInstanceTableStatus(null, 'cb-active');
  toggleInstanceTableStatus(null, 'cb-select-on');
  toggleInstanceTableStatus(null, 'cb-select-allow');
	toggleInstanceTableStatus(null, 'cb-info-on');
	toggleInstanceTableStatus(null, 'cb-info-allow');
	toggleInstanceTableStatus(null, 'cb-toggle-on');
	toggleInstanceTableStatus(null, 'cb-toggle-allow');
	toggleInstanceTableStatus(null, 'cb-recorder-allow');

});