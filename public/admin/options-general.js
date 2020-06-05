"use strict";

jQuery(document).ready(function ($) {
	var $checkboxes = $('form.taxonomy-taxi input[type=checkbox]');

	var $all = $('<a>check all</a>').click(function () {
		$checkboxes.attr('checked', true);
	});

	var $none = $('<a>uncheck all</a>').click(function () {
		$checkboxes.attr('checked', false);
	});

	$('form.taxonomy-taxi table').append($all, ' | ', $none);
});