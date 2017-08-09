(function( $ ) {

// privacy dropdown
$(document).on('click', '.ps-privacy-dropdown ul li a', function() {
	var $a = $( this ).closest('a'),
		$menu = $a.closest('ul'),
		$input = $menu.siblings('input'),
		$btn = $menu.siblings('.ps-btn,.ps-dropdown-toggle'),
		$icon = $btn.find('i'),
		$label = $btn.find('.ps-privacy-title');

	$input.val( $a.attr('data-option-value') );
	$icon.attr('class', $a.find('i').attr('class'));
	$label.html( $a.find('span').html() );
	$menu.css('display', 'none');
});

// cache bootstrap datepicker in case of override
$.fn.peepso_dp = $.fn.datepicker.noConflict();

// init datepicker
function initDatepicker( $dp ) {
	if ( !$dp ) {
		return;
	}

	if ( window.peepsodatepickerdata && peepsodatepickerdata.config ) {
		var dpConfig = peepsodatepickerdata.config,
			daysShort = dpConfig.daysShort,
			daysMin = [];

		for ( var i = 0; i < daysShort.length; i++ ) {
			daysMin.push( daysShort[i].replace(/^([a-z]{2}).+$/i, '$1') );
		}

		dpConfig.daysMin = daysMin;

		$.fn.peepso_dp.dates['peepso'] = {
			days: dpConfig.days,
			daysShort: dpConfig.daysShort,
			daysMin: dpConfig.daysMin,
			months: dpConfig.months,
			monthsShort: dpConfig.monthsShort,
			today: dpConfig.today,
			clear: dpConfig['clear'],
			format: dpConfig.format,
			weekStart: dpConfig.weekStart,
			rtl: dpConfig.rtl
		};
	}

	$dp.each(function() {
		var $input = $(this),
			value = $input.data('value'),
			startDate = $input.data('dateStartDate'),
			endDate = $input.data('dateEndDate'),
			dt;

		$input.peepso_dp({
			autoclose: true,
			format: peepsodata.date_format,
			language: 'peepso',
			multidateSeparator: false,
			startDate: startDate,
			endDate: endDate
		})
		.on('changeDate', function(e) {
			var $el = $(this);
			var date = e.format('yyyy-mm-dd');
			var input_name = $el.data('input');
			$el.data('value', date);
			$el.trigger('input');
			$('#' + input_name).val(date);
		});

		if ( value ) {
			value = value.split('-');
			dt = new Date(+value[0], +value[1] - 1, +value[2]);
			$input.peepso_dp('update', dt);
		}
	});

	// http://stackoverflow.com/questions/24981072/bootstrap-datepicker-empties-field-after-selecting-current-date
	$dp.on('show', function(e){
		if ( e.date ) {
			 $(this).data('stickyDate', e.date);
		} else {
			 $(this).data('stickyDate', null);
		}
	}).on('hide', function(e){
		var stickyDate = $(this).data('stickyDate');
		if ( !e.date && stickyDate ) {
			$(this).peepso_dp('setDate', stickyDate);
			$(this).data('stickyDate', null);
		}
	});

	$dp.addClass('datepicker-initialized');
}

ps_datepicker = {
	init: initDatepicker
};

$(function() {
	initDatepicker( $('#peepso-wrap .datepicker').not('.datepicker-initialized') );
});

}( jQuery ));
