/*
 * custom javascript
*/
$j =  jQuery.noConflict();
$j(function() {
	var today = new Date();
	var dd = String(today.getDate()).padStart(2, '0');
	var mm = String(today.getMonth() + 1).padStart(2, '0'); //January is 0!
	var yyyy = today.getFullYear();
	today = mm + '/' + dd + '/' + yyyy;
	
	$j("#date_range").daterangepicker({
		"format": "MM/DD/YYYY",
		'minDate': today,
		'maxYear': 1,
	});

	$j(".dropdown-menu").css('display','none');
});
