/* global jQuery */
(function ($) {
	'use strict';

	if (!document.getElementById('bm-settlement-form')) {
		return;
	}

	// ── Line counter ─────────────────────────────────────────────────────────

	function getLineCount() {
		return $('#bm-lines-tbody .bm-settlement-line').length;
	}

	function renumberLines() {
		$('#bm-lines-tbody .bm-settlement-line').each(function (i) {
			$(this).attr('data-idx', i);
			$(this).find('.bm-line-num').text(i + 1);
			// Fix checkbox names so each index is unique.
			$(this).find('input[type=checkbox]').attr(
				'name',
				'lines[include_in_settlement][' + i + ']'
			);
		});
	}

	// ── Row total calculation ─────────────────────────────────────────────────

	function calcRowTotal($row) {
		var amount   = parseFloat($row.find('.bm-line-amount').val())   || 0;
		var discount = parseFloat($row.find('.bm-line-discount').val()) || 0;
		var discType = $row.find('.bm-line-disc-type').val();
		var discValue = 0;

		if (discType === 'percent') {
			discValue = amount * discount / 100;
		} else {
			discValue = Math.min(amount, discount);
		}

		var total = Math.max(0, amount - discValue);
		$row.find('.bm-line-total').text(formatPLN(total));
		return total;
	}

	// ── Summary recalculation ─────────────────────────────────────────────────

	function recalcSummary() {
		var sumGross     = 0;
		var sumDiscounts = 0;
		var sumPaid      = 0;

		$('#bm-lines-tbody .bm-settlement-line').each(function () {
			var $row   = $(this);
			var inc    = $row.find('.bm-line-include').is(':checked');
			if (!inc) return;

			var amount   = parseFloat($row.find('.bm-line-amount').val())   || 0;
			var discount = parseFloat($row.find('.bm-line-discount').val()) || 0;
			var discType = $row.find('.bm-line-disc-type').val();
			var discValue = 0;

			if (discType === 'percent') {
				discValue = amount * discount / 100;
			} else {
				discValue = Math.min(amount, discount);
			}

			var total = Math.max(0, amount - discValue);
			sumGross     += total;
			sumDiscounts += discValue;

			var payStatus = $row.find('.bm-line-pay-status').val();
			if (payStatus === 'paid') {
				sumPaid += total;
			}
		});

		// Apply global discount.
		var gDisc     = parseFloat($('#bm-global-discount').val())  || 0;
		var gDiscType = $('#bm-global-disc-type').val();
		var gDiscValue = 0;

		if (gDiscType === 'percent') {
			gDiscValue = sumGross * gDisc / 100;
		} else {
			gDiscValue = Math.min(sumGross, gDisc);
		}

		var finalGross = Math.max(0, sumGross - gDiscValue);
		sumDiscounts += gDiscValue;

		var outstanding = Math.max(0, finalGross - sumPaid);
		var overpaid    = Math.max(0, sumPaid - finalGross);

		$('#bm-sum-gross').text(formatPLN(sumGross + sumDiscounts));
		$('#bm-sum-discounts').text('- ' + formatPLN(sumDiscounts));
		$('#bm-sum-total').text(formatPLN(finalGross));
		$('#bm-sum-paid').text(formatPLN(sumPaid));

		if (outstanding > 0.005) {
			$('#bm-sum-outstanding').text(formatPLN(outstanding));
		} else if (overpaid > 0.005) {
			$('#bm-sum-outstanding').text(formatPLN(overpaid));
		} else {
			$('#bm-sum-outstanding').text('✓');
		}
	}

	// ── Helpers ───────────────────────────────────────────────────────────────

	function formatPLN(value) {
		return value.toFixed(2).replace('.', ',').replace(/\B(?=(\d{3})+(?!\d))/g, ' ') + ' zł';
	}

	// ── Event wiring ──────────────────────────────────────────────────────────

	// Add new line.
	$('#bm-add-line').on('click', function () {
		var idx      = getLineCount();
		var template = document.getElementById('bm-line-template').innerHTML;
		template = template.replace(/__IDX__/g, idx).replace(/__NUM__/g, idx + 1);
		$('#bm-lines-tbody').append(template);
		recalcSummary();
	});

	// Remove line.
	$('#bm-lines-tbody').on('click', '.bm-remove-line', function () {
		$(this).closest('.bm-settlement-line').remove();
		renumberLines();
		recalcSummary();
	});

	// Recalc on any change inside a row.
	$('#bm-lines-tbody').on('input change', '.bm-line-amount, .bm-line-discount, .bm-line-disc-type, .bm-line-pay-status, .bm-line-include', function () {
		var $row = $(this).closest('.bm-settlement-line');
		calcRowTotal($row);
		recalcSummary();
	});

	// Recalc on global discount change.
	$('#bm-global-discount, #bm-global-disc-type').on('input change', function () {
		recalcSummary();
	});

	// Initial calculation on page load.
	$('#bm-lines-tbody .bm-settlement-line').each(function () {
		calcRowTotal($(this));
	});
	recalcSummary();

})(jQuery);
