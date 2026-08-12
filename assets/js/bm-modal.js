/**
 * BM Modal — replaces browser alert() and confirm() with styled popups.
 *
 * API:
 *   bmModal.alert(message, title?)
 *   bmModal.confirm(message, onConfirm, title?)
 *
 * Declarative:
 *   data-bm-confirm="message"  on <a> or <button> — shows confirm before action
 *   data-bm-alert="message"    on any element — shows alert on click
 */
window.bmModal = (function () {
	'use strict';

	var overlay, dialog, dialogTitle, dialogBody, dialogFooter;
	var _resolveQueue = [];

	function buildDOM() {
		if (overlay) return;

		overlay = document.createElement('div');
		overlay.id = 'bm-modal-overlay';

		dialog = document.createElement('div');
		dialog.id = 'bm-modal-dialog';
		dialog.setAttribute('role', 'dialog');
		dialog.setAttribute('aria-modal', 'true');

		dialogTitle = document.createElement('div');
		dialogTitle.id = 'bm-modal-title';

		dialogBody = document.createElement('div');
		dialogBody.id = 'bm-modal-body';

		dialogFooter = document.createElement('div');
		dialogFooter.id = 'bm-modal-footer';

		dialog.appendChild(dialogTitle);
		dialog.appendChild(dialogBody);
		dialog.appendChild(dialogFooter);
		overlay.appendChild(dialog);
		document.body.appendChild(overlay);

		// Close on overlay click for alert-type (no confirm needed)
		overlay.addEventListener('click', function (e) {
			if (e.target === overlay && !overlay.getAttribute('data-confirm')) {
				closeModal();
			}
		});

		// ESC key
		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && overlay.classList.contains('is-open')) {
				if (!overlay.getAttribute('data-confirm')) closeModal();
				else respond(false);
			}
		});
	}

	function openModal(type, message, title) {
		buildDOM();
		overlay.setAttribute('data-confirm', type === 'confirm' ? '1' : '');
		dialogTitle.textContent = title || (type === 'confirm' ? 'Potwierdź' : 'Informacja');
		dialogTitle.style.display = title === false ? 'none' : '';
		dialogBody.textContent = message;
		dialogFooter.innerHTML = '';

		if (type === 'confirm') {
			var btnOk = btn('Tak', 'button button-primary', function () { respond(true); });
			var btnCancel = btn('Anuluj', 'button', function () { respond(false); });
			dialogFooter.appendChild(btnCancel);
			dialogFooter.appendChild(btnOk);
		} else {
			var btnClose = btn('OK', 'button button-primary', function () { closeModal(); });
			dialogFooter.appendChild(btnClose);
		}

		overlay.classList.add('is-open');
		dialogFooter.querySelector('.button-primary').focus();
	}

	function closeModal() {
		if (overlay) overlay.classList.remove('is-open');
	}

	function respond(result) {
		closeModal();
		var cb = _resolveQueue.shift();
		if (typeof cb === 'function') cb(result);
	}

	function btn(text, cls, handler) {
		var b = document.createElement('button');
		b.type = 'button';
		b.className = cls;
		b.textContent = text;
		b.addEventListener('click', handler);
		return b;
	}

	// ── Public API ────────────────────────────────────────────────────────────

	function alert(message, title) {
		openModal('alert', message, title);
	}

	function confirm(message, callback, title) {
		_resolveQueue.push(callback);
		openModal('confirm', message, title);
	}

	// ── Declarative handlers ──────────────────────────────────────────────────

	document.addEventListener('DOMContentLoaded', function () {
		injectStyles();

		// data-bm-confirm on <a> or <button>
		document.addEventListener('click', function (e) {
			var target = e.target.closest('[data-bm-confirm]');
			if (!target) return;
			e.preventDefault();
			e.stopPropagation();
			var message = target.getAttribute('data-bm-confirm');
			confirm(message, function (ok) {
				if (!ok) return;
				// Navigate or submit
				if (target.tagName === 'A') {
					window.location.href = target.href;
				} else if (target.tagName === 'BUTTON' || target.tagName === 'INPUT') {
					target.removeAttribute('data-bm-confirm');
					target.click();
				} else if (target.closest('form')) {
					target.closest('form').submit();
				}
			});
		});

		// data-bm-alert
		document.addEventListener('click', function (e) {
			var target = e.target.closest('[data-bm-alert]');
			if (!target) return;
			e.preventDefault();
			alert(target.getAttribute('data-bm-alert'));
		});
	});

	function injectStyles() {
		if (document.getElementById('bm-modal-styles')) return;
		var style = document.createElement('style');
		style.id = 'bm-modal-styles';
		style.textContent = [
			'#bm-modal-overlay{display:none;position:fixed;inset:0;background:rgba(0,0,0,.55);z-index:999999;align-items:center;justify-content:center;}',
			'#bm-modal-overlay.is-open{display:flex;}',
			'#bm-modal-dialog{background:#fff;border-radius:6px;box-shadow:0 8px 32px rgba(0,0,0,.22);min-width:320px;max-width:480px;width:90%;animation:bmModalIn .15s ease;}',
			'@keyframes bmModalIn{from{opacity:0;transform:translateY(-12px)}to{opacity:1;transform:none}}',
			'#bm-modal-title{font-size:15px;font-weight:600;padding:16px 20px 0;color:#1d2327;border-bottom:0;}',
			'#bm-modal-body{padding:14px 20px 16px;font-size:14px;color:#3c434a;line-height:1.5;}',
			'#bm-modal-footer{padding:0 20px 16px;display:flex;gap:8px;justify-content:flex-end;}',
			'#bm-modal-footer .button{min-width:80px;}',
		].join('');
		document.head.appendChild(style);
	}

	return { alert: alert, confirm: confirm };
})();
