jQuery(function() {
	
	var ds = jQuery('fieldset.SectionSchemaDatasource');
	if(!ds.length) return;
	
	var fields = ds.find('label.fields');
	var section = ds.find('select[name="fields[SectionSchemaDatasource][section]"]');
	var name = 'fields[SectionSchemaDatasource][fields][]';
	
	var reset = false;
	section.on('change', function() {
		var section = jQuery(this).val();
		fields.hide().attr('name', '');
		fields.filter('.fields-for-' + section).show().attr('name', name);
		if(reset) fields.find('option').removeAttr('selected');
		reset = true;
	});
	
	section.change();
});