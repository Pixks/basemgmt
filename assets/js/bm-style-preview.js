/* Baza Obozowa – Style-settings live preview & preset switcher
 * Data passed via wp_localize_script as window.bmStyleSettings:
 *   { shadows: {...}, fonts: {...}, pillLabel: '...' }
 */
(function () {
	'use strict';

	function init() {
		var cfg = window.bmStyleSettings || {};
		var shadows = cfg.shadows || {};
		var fonts   = cfg.fonts   || {};
		var p       = document.getElementById('bm-style-preview');

		// css() is a no-op when the preview element is absent.
		function css(prop, val) {
			if (p) p.style.setProperty(prop, val);
		}

	// Update hex labels next to color pickers
	function bindColor(id, cssProp) {
		var el = document.getElementById(id);
		if (!el) return;
		el.addEventListener('input', function () {
			css(cssProp, el.value);
			var group = el.closest('.bm-style-form-group');
			var hex = group ? group.querySelector('.color-hex') : null;
			if (hex) hex.textContent = el.value;
			updateHeader();
		});
	}

	function updateHeader() {
		var grad    = document.querySelector('[name=bm_ui_header_gradient]');
		var primary = document.getElementById('bm-ui-primary-color');
		var hover   = document.getElementById('bm-ui-primary-hover-color');
		if (!grad || !primary || !hover) return;
		var bg = grad.checked
			? 'linear-gradient(135deg, ' + primary.value + ', ' + hover.value + ')'
			: primary.value;
		css('--bm-header-bg', bg);
	}

	bindColor('bm-ui-primary-color',     '--bm-primary');
	bindColor('bm-ui-primary-hover-color','--bm-primary-hover');
	bindColor('bm-ui-badge-color',       '--bm-badge-bg');
	bindColor('bm-ui-badge-text-color',  '--bm-badge-text');
	bindColor('bm-ui-btn-text-color',    '--bm-btn-text');
	bindColor('bm-ui-link-color',        '--bm-link');
	bindColor('bm-ui-text-color',        '--bm-text');
	bindColor('bm-ui-heading-color',     '--bm-heading');
	bindColor('bm-ui-surface-color',     '--bm-surface');
	bindColor('bm-ui-background-color',  '--bm-bg');
	bindColor('bm-ui-border-color',      '--bm-border');

	// Card radius slider
	var rng = document.getElementById('bm-ui-radius');
	if (rng) {
		rng.addEventListener('input', function () {
			css('--bm-radius', rng.value + 'px');
			css('--bm-radius-sm', Math.max(0, parseInt(rng.value, 10) - 2) + 'px');
		});
	}

	// Button / badge radius slider
	var btnRng    = document.getElementById('bm-ui-btn-radius');
	var btnActual = document.getElementById('bm-ui-btn-radius-actual');
	var btnLabel  = document.getElementById('bm-btn-radius-val');
	function updateBtnRadius() {
		if (!btnRng) return;
		var v = parseInt(btnRng.value, 10);
		var actual = (v >= 32) ? 999 : v;
		if (btnActual) btnActual.value = actual;
		if (btnLabel)  btnLabel.textContent = (v >= 32) ? (cfg.pillLabel || 'Pill') : v;
		css('--bm-radius-pill', actual + 'px');
	}
	if (btnRng) {
		btnRng.addEventListener('input', updateBtnRadius);
	}

	// Shadow select
	var shd = document.getElementById('bm-ui-shadow');
	if (shd) {
		shd.addEventListener('change', function () {
			css('--bm-shadow', shadows[shd.value] || 'none');
		});
	}

	// Font select + custom font rows visibility
	var fnt         = document.getElementById('bm-ui-font-family');
	var rowFontUrl  = document.getElementById('bm-row-custom-font-url');
	var rowFontName = document.getElementById('bm-row-custom-font-name');
	var fontName    = document.getElementById('bm-ui-custom-font-name');
	function updateFontPreview() {
		if (!fnt) return;
		var isCustom = fnt.value === 'custom';
		if (rowFontUrl)  rowFontUrl.style.display  = isCustom ? '' : 'none';
		if (rowFontName) rowFontName.style.display  = isCustom ? '' : 'none';
		if (isCustom && fontName && fontName.value) {
			css('--bm-font', '"' + fontName.value + '", sans-serif');
		} else {
			css('--bm-font', fonts[fnt.value] || 'sans-serif');
		}
	}
	if (fnt) {
		fnt.addEventListener('change', updateFontPreview);
	}
	if (fontName) {
		fontName.addEventListener('input', updateFontPreview);
	}

	// Gradient checkbox
	var grd = document.querySelector('[name=bm_ui_header_gradient]');
	if (grd) {
		grd.addEventListener('change', updateHeader);
	}

	// Preset buttons – registered unconditionally so they work regardless of preview presence
	document.querySelectorAll('[data-bm-preset]').forEach(function (btn) {
		btn.addEventListener('click', function () {
			var d;
			try { d = JSON.parse(btn.dataset.bmPreset || '{}'); } catch(e) { return; }

			// Update color pickers
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
				'bm-ui-border-color':        'border_color',
			};
			for (var id in colorMap) {
				var el = document.getElementById(id);
				if (el && colorMap[id] in d && d[colorMap[id]] !== undefined) {
					el.value = d[colorMap[id]];
					el.dispatchEvent(new Event('input'));
				}
			}

			// Card radius
			var rv = document.getElementById('bm-ui-radius');
			var rvLabel = document.getElementById('bm-radius-val');
			if (rv && d.radius) {
				rv.value = d.radius;
				if (rvLabel) rvLabel.textContent = d.radius;
				rv.dispatchEvent(new Event('input'));
			}

			// Button radius
			if (btnRng && d.btn_radius !== undefined) {
				btnRng.value = Math.min(32, parseInt(d.btn_radius, 10));
				updateBtnRadius();
			}

			// Shadow
			if (shd && d.shadow) { shd.value = d.shadow; shd.dispatchEvent(new Event('change')); }

			// Font
			if (fnt && d.font_family) { fnt.value = d.font_family; fnt.dispatchEvent(new Event('change')); }

			// Gradient
			if (grd) { grd.checked = (d.header_gradient === '1'); grd.dispatchEvent(new Event('change')); }

			// Preset key
			var pk = document.getElementById('bm-ui-style-preset');
			if (pk && d.key) pk.value = d.key;

			// Highlight active preset button
			document.querySelectorAll('[data-bm-preset]').forEach(function (b) {
				b.classList.remove('button-primary');
				b.classList.add('button');
			});
			btn.classList.add('button-primary');
		});
	});

	// Preview tab switching
	document.querySelectorAll('.bm-prev-tab').forEach(function (tab) {
		tab.addEventListener('click', function () {
			document.querySelectorAll('.bm-prev-tab').forEach(function (t) { t.classList.remove('active'); });
			document.querySelectorAll('.bm-prev-pane').forEach(function (pane) { pane.classList.remove('active'); });
			tab.classList.add('active');
			var target = document.getElementById(tab.dataset.pane);
			if (target) target.classList.add('active');
		});
	});

	// Init preview CSS vars from current form values on page load
	if (p) {
		(function initPreview() {
			var colorMap = {
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
				'bm-ui-border-color':        '--bm-border',
			};
			for (var id in colorMap) {
				var el = document.getElementById(id);
				if (el) css(colorMap[id], el.value);
			}
			if (rng) {
				css('--bm-radius', rng.value + 'px');
				css('--bm-radius-sm', Math.max(0, parseInt(rng.value, 10) - 2) + 'px');
			}
			updateBtnRadius();
			if (shd) css('--bm-shadow', shadows[shd.value] || 'none');
			updateFontPreview();
			updateHeader();
		})();
	} else {
		// No preview element – still initialise font row visibility
		updateFontPreview();
	}
	} // end init()

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
