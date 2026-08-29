/* Baza Obozowa – Style-settings live preview & preset switcher
 * Data passed via wp_localize_script as window.bmStyleSettings:
 *   { shadows: {...}, fonts: {...}, pillLabel: '...' }
 */
jQuery(function ($) {
	var cfg     = window.bmStyleSettings || {};
	var shadows = cfg.shadows || {};
	var fonts   = cfg.fonts   || {};
	var $prev   = $('#bm-style-preview');

	function css(prop, val) {
		if ($prev.length) {
			$prev[0].style.setProperty(prop, val);
		}
	}

	function updateHeader() {
		var isGrad  = $('[name=bm_ui_header_gradient]').prop('checked');
		var primary = $('#bm-ui-primary-color').val();
		var hover   = $('#bm-ui-primary-hover-color').val();
		css('--bm-header-bg', isGrad
			? 'linear-gradient(135deg, ' + primary + ', ' + hover + ')'
			: primary
		);
	}

	function bindColor(id, cssProp) {
		$('#' + id).on('input', function () {
			css(cssProp, this.value);
			$(this).closest('.bm-style-form-group').find('.color-hex').text(this.value);
			updateHeader();
		});
	}

	bindColor('bm-ui-primary-color',       '--bm-primary');
	bindColor('bm-ui-primary-hover-color', '--bm-primary-hover');
	bindColor('bm-ui-badge-color',         '--bm-badge-bg');
	bindColor('bm-ui-badge-text-color',    '--bm-badge-text');
	bindColor('bm-ui-btn-text-color',      '--bm-btn-text');
	bindColor('bm-ui-link-color',          '--bm-link');
	bindColor('bm-ui-text-color',          '--bm-text');
	bindColor('bm-ui-heading-color',       '--bm-heading');
	bindColor('bm-ui-surface-color',       '--bm-surface');
	bindColor('bm-ui-background-color',    '--bm-bg');
	bindColor('bm-ui-border-color',        '--bm-border');

	// Card radius slider
	var $rng     = $('#bm-ui-radius');
	var $rvLabel = $('#bm-radius-val');
	$rng.on('input', function () {
		var v = parseInt(this.value, 10);
		css('--bm-radius', v + 'px');
		css('--bm-radius-sm', Math.max(0, v - 2) + 'px');
		$rvLabel.text(this.value);
	});

	// Button / badge radius slider
	var $btnRng    = $('#bm-ui-btn-radius');
	var $btnActual = $('#bm-ui-btn-radius-actual');
	var $btnLabel  = $('#bm-btn-radius-val');
	function updateBtnRadius() {
		var v      = parseInt($btnRng.val(), 10);
		var actual = (v >= 32) ? 999 : v;
		$btnActual.val(actual);
		$btnLabel.text((v >= 32) ? (cfg.pillLabel || 'Pill') : v);
		css('--bm-radius-pill', actual + 'px');
	}
	$btnRng.on('input', updateBtnRadius);

	// Shadow select
	var $shd = $('#bm-ui-shadow');
	$shd.on('change', function () {
		css('--bm-shadow', shadows[this.value] || 'none');
	});

	// Font select + custom font rows visibility
	var $fnt         = $('#bm-ui-font-family');
	var $rowFontUrl  = $('#bm-row-custom-font-url');
	var $rowFontName = $('#bm-row-custom-font-name');
	var $fontName    = $('#bm-ui-custom-font-name');
	function updateFont() {
		var isCustom = $fnt.val() === 'custom';
		$rowFontUrl.toggle(isCustom);
		$rowFontName.toggle(isCustom);
		if (isCustom && $fontName.val()) {
			css('--bm-font', '"' + $fontName.val() + '", sans-serif');
		} else {
			css('--bm-font', fonts[$fnt.val()] || 'sans-serif');
		}
	}
	$fnt.on('change', updateFont);
	$fontName.on('input', updateFont);

	// Gradient checkbox
	$('[name=bm_ui_header_gradient]').on('change', updateHeader);

	// Preset buttons – event delegation so it works regardless of DOM timing
	$(document).on('click', '[data-bm-preset]', function () {
		var d;
		try { d = JSON.parse($(this).attr('data-bm-preset') || '{}'); } catch (e) { return; }

		var colorMap = {
			'bm-ui-primary-color':       'primary_color',
			'bm-ui-primary-hover-color': 'primary_hover',
			'bm-ui-badge-color':         'badge_color',
			'bm-ui-badge-text-color':    'badge_text_color',
			'bm-ui-btn-text-color':      'btn_text_color',
			'bm-ui-link-color':          'link_color',
			'bm-ui-text-color':          'text_color',
			'bm-ui-heading-color':       'heading_color',
			'bm-ui-surface-color':       'surface_color',
			'bm-ui-background-color':    'background',
			'bm-ui-border-color':        'border_color'
		};
		$.each(colorMap, function (id, key) {
			if (d[key] !== undefined) {
				$('#' + id).val(d[key]).trigger('input');
			}
		});

		if (d.radius !== undefined) {
			$rng.val(d.radius).trigger('input');
		}
		if (d.btn_radius !== undefined) {
			$btnRng.val(Math.min(32, parseInt(d.btn_radius, 10)));
			updateBtnRadius();
		}
		if (d.shadow)      { $shd.val(d.shadow).trigger('change'); }
		if (d.font_family) { $fnt.val(d.font_family).trigger('change'); }
		$('[name=bm_ui_header_gradient]').prop('checked', d.header_gradient === '1').trigger('change');
		if (d.key) { $('#bm-ui-style-preset').val(d.key); }

		$('[data-bm-preset]').removeClass('button-primary').addClass('button');
		$(this).removeClass('button').addClass('button-primary');
	});

	// Preview tab switching – event delegation
	$(document).on('click', '.bm-prev-tab', function () {
		$('.bm-prev-tab').removeClass('active');
		$('.bm-prev-pane').removeClass('active');
		$(this).addClass('active');
		var pane = $(this).data('pane');
		if (pane) { $('#' + pane).addClass('active'); }
	});

	// Prevent preview links from navigating
	$(document).on('click', '[data-bm-preview-link]', function (e) {
		e.preventDefault();
	});

	// Init preview CSS vars from current form values
	if ($prev.length) {
		var colorInitMap = {
			'bm-ui-primary-color':       '--bm-primary',
			'bm-ui-primary-hover-color': '--bm-primary-hover',
			'bm-ui-badge-color':         '--bm-badge-bg',
			'bm-ui-badge-text-color':    '--bm-badge-text',
			'bm-ui-btn-text-color':      '--bm-btn-text',
			'bm-ui-link-color':          '--bm-link',
			'bm-ui-text-color':          '--bm-text',
			'bm-ui-heading-color':       '--bm-heading',
			'bm-ui-surface-color':       '--bm-surface',
			'bm-ui-background-color':    '--bm-bg',
			'bm-ui-border-color':        '--bm-border'
		};
		$.each(colorInitMap, function (id, prop) {
			var val = $('#' + id).val();
			if (val) { css(prop, val); }
		});
		if ($rng.length) {
			var rv = parseInt($rng.val(), 10);
			css('--bm-radius', rv + 'px');
			css('--bm-radius-sm', Math.max(0, rv - 2) + 'px');
		}
		updateBtnRadius();
		if ($shd.length) { css('--bm-shadow', shadows[$shd.val()] || 'none'); }
		updateFont();
		updateHeader();
	} else {
		updateFont();
	}
});
