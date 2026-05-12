jQuery(function ($) {
	'use strict';

	$('.ctwp-color').wpColorPicker();

	function syncSource($fs) {
		var v = $fs.find('input[name$="[source]"]:checked').val();
		$fs.find('.ctwp-acf-row').toggle(v === 'acf');
		$fs.find('.ctwp-direct-row').toggle(v === 'direct');
	}

	$('.ctwp-rule').each(function () {
		syncSource($(this));
	});

	$(document).on('change', '.ctwp-rule input[name$="[source]"]', function () {
		syncSource($(this).closest('.ctwp-rule'));
	});
});
