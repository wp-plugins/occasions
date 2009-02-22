Date.firstDayOfWeek = 1;
//Date.format = 'dd/mm/yyyy';

jQuery(document).ready(function() {
	
	jQuery('#occ_addnode').click(function() {
		jQuery('input#occ_addnode').attr( 'disabled', 'disabled' );
		jQuery('input#occ_addnode').val( OccasionsAjaxL10n.addingLabel );
		occ_addnode();
	});
	
	occ_setupDP();
});

function occ_setupDP(){
	jQuery('.startdate').datePicker({
		createButton: false
	}).bind('dpClosed', function(e, selectedDates){
		var d = selectedDates[0];
		if (d) {
			d = new Date(d);
			jQuery(this).parent().next().find('a:first').dpSetStartDate(d.addDays(0).asString());
		}
	});
	
	jQuery('.enddate').datePicker({
		createButton: false
	}).bind('dpClosed', function(e, selectedDates){
		var d = selectedDates[0];
		if (d) {
			d = new Date(d);
			jQuery(this).parent().prev().find('a:first').dpSetEndDate(d.addDays(0).asString());
		}
	});
	
	jQuery('.date-pick').datePicker({
		startDate: '01/01/' + OccasionsAjaxL10n.currentYear,
		endDate: '31/12/' + OccasionsAjaxL10n.nextYear,
		createButton: false,
		displayClose: true
	}).dpSetPosition(jQuery.dpConst.POS_TOP, jQuery.dpConst.POS_RIGHT).bind('click', function(){
		jQuery(this).dpDisplay();
		this.blur();
		return false;
	}).bind('dateSelected', function(e, selectedDate){
		var selDate = selectedDate.getDate() + '.' + (Number(selectedDate.getMonth()) + 1) + '.';
		jQuery(this).parent().find('input:first').val(selDate);
	});
}

function occ_deleteNode( nodeid ) {
	jQuery('#occnode' + nodeid).remove();
	//jQuery('input#occnodes').val( occ_index-1 );
}

function occ_hardDeleteNode( nodeid ) {
	if (confirm(OccasionsAjaxL10n.confirmMessage)) {
		jQuery('tr#occnode' + nodeid).animate({ backgroundColor: '#FFF4F4' }, 300, "linear", function() {
			var occ_sack = new sack(
			OccasionsAjaxL10n.requestUrl);
			occ_sack.execute = 1;
			occ_sack.method = 'POST';
			occ_sack.setVar( "action", "occasions_sack_deletedataset" );
			occ_sack.setVar( "inumber", nodeid );
			occ_sack.onError = function() { alert('Ajax error') };
			occ_sack.runAJAX();
		});
	} else {
		return false;
	}
	
	//occ_deleteNode( nodeid );
}

function occ_addnode() {
	var datasets = jQuery("tr[id^='occnode']").length;
	if( datasets > 0 ) {
		var occ_new = 0;
		var occ_index = parseInt((jQuery("tr[id^='occnode']:last").attr("id").substr(7))) + 1;
	} else {
		var occ_new = 1;
		var occ_index = 1;
	}
	var occ_sack = new sack(
	OccasionsAjaxL10n.requestUrl);
	occ_sack.execute = 1;
	occ_sack.method = 'POST';
	occ_sack.setVar( "action", "occasions_sack_addnode" );
	occ_sack.setVar( "inumber", occ_index );
	occ_sack.setVar( "occ_new", occ_new );
	occ_sack.onError = function() { alert('Ajax error') };
	occ_sack.runAJAX();
}

function occ_confirm() {
	if (confirm(OccasionsAjaxL10n.confirmMessage)) {
		return true;
	} else {
		return false;
	}
}