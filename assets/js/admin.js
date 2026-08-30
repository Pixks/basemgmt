/* Baza Obozowa – Admin JS */
(function ($) {
    'use strict';

    /* ── Camp dashboard tabs ──────────────────────────────────────────────── */
    var HASH_MAP = {
        'bm-section-overview'    : 'panel',
        'bm-section-process'     : 'panel',
        'bm-section-workcenter'  : 'workcenter',
        'bm-section-checklist'   : 'workcenter',
        'bm-section-organizer'   : 'organizer',
        'bm-section-documents'   : 'documents',
        'bm-section-equipment'   : 'equipment',
        'bm-section-finance'     : 'finance',
    };

    function activateCampTab(tabName) {
        var $nav    = $('#bm-camp-tab-nav');
        var $panels = $('.bm-tab-panel');
        if (!$nav.length) return;

        $nav.find('.nav-tab').removeClass('nav-tab-active');
        $nav.find('[data-tab="' + tabName + '"]').addClass('nav-tab-active');

        $panels.removeClass('is-active');
        $panels.filter('[data-tab="' + tabName + '"]').addClass('is-active');

        history.replaceState(null, '', window.location.pathname + window.location.search + '#bm-tab-' + tabName);
    }

    function initCampTabs() {
        var $nav = $('#bm-camp-tab-nav');
        if (!$nav.length) return;

        // Determine initial tab from URL hash.
        var hash    = window.location.hash.replace('#', '');
        var initial = 'panel';

        if (hash.indexOf('bm-tab-') === 0) {
            initial = hash.replace('bm-tab-', '');
        } else if (HASH_MAP[hash]) {
            initial = HASH_MAP[hash];
        }

        activateCampTab(initial);

        $nav.on('click', '.nav-tab', function (e) {
            e.preventDefault();
            activateCampTab($(this).data('tab'));
        });
    }

    /* ── Add task row ─────────────────────────────────────────────────────── */
    function initAddTaskRow() {
        $(document).on('click', '#bm-add-task-row', function () {
            var tpl = document.getElementById('bm-task-row-template');
            if (!tpl) return;
            var clone = document.importNode(tpl.content, true);
            document.getElementById('bm-checklist-tbody').appendChild(clone);
        });
    }

    /* ── Sortable.js: plan item drag-and-drop reorder ─────────────────────── */
    function initSortable() {
        var el = document.getElementById('bm-plan-items-tbody');
        if (!el || typeof Sortable === 'undefined') return;

        Sortable.create(el, {
            handle: '.bm-drag-handle',
            animation: 150,
            onEnd: function () {
                var order = [];
                el.querySelectorAll('tr[data-item-id]').forEach(function (tr) {
                    order.push(parseInt(tr.getAttribute('data-item-id'), 10));
                });
                $.post(ajaxurl, {
                    action : 'bm_reorder_plan_items',
                    nonce  : bmAdmin.reorderNonce,
                    order  : order,
                }, function (res) {
                    if (!res.success) {
                        console.error('Reorder failed', res);
                    }
                });
            }
        });
    }

    /* ── FullCalendar: reservations calendar view ─────────────────────────── */
    function initCalendar() {
        var calEl = document.getElementById('bm-reservations-calendar');
        if (!calEl || typeof FullCalendar === 'undefined') return;

        var resourceSelect = document.getElementById('bm-calendar-resource');
        function getResourceId() {
            return resourceSelect ? parseInt(resourceSelect.value || '0', 10) : 0;
        }

        var calendar = new FullCalendar.Calendar(calEl, {
            initialView : 'dayGridMonth',
            locale      : 'pl',
            height      : 'auto',
            eventSources: [{
                url   : ajaxurl,
                method: 'GET',
                extraParams: function () {
                    return {
                        action     : 'bm_calendar_events',
                        nonce      : bmAdmin.calendarNonce,
                        resource_id: getResourceId(),
                    };
                },
                failure: function () {
                    console.error('FullCalendar: failed to load events');
                },
            }],
            eventClick: function (info) {
                var p = info.event.extendedProps;
                alert(
                    info.event.title + '\n' +
                    'Status: ' + p.status + '\n' +
                    'Cel: ' + p.purpose
                );
            },
        });
        calendar.render();

        if (resourceSelect) {
            resourceSelect.addEventListener('change', function () {
                calendar.refetchEvents();
            });
        }
    }

    /* ── Style preview (Wygląd tab) ──────────────────────────────────────── */
    function initStylePreview() {
        if (!$('[data-bm-preset]').length) return;

        var SHADOWS = {
            none: 'none',
            sm:   '0 1px 4px rgba(0,0,0,.08)',
            md:   '0 2px 12px rgba(0,0,0,.10)',
            lg:   '0 4px 24px rgba(0,0,0,.14)'
        };
        var FONTS = {
            'lato':      'Lato, "Open Sans", system-ui, sans-serif',
            'open-sans': '"Open Sans", Lato, system-ui, sans-serif',
            'roboto':    'Roboto, "Open Sans", system-ui, sans-serif',
            'nunito':    'Nunito, "Open Sans", system-ui, sans-serif',
            'system':    'system-ui, -apple-system, sans-serif',
            'custom':    'sans-serif'
        };

        function css(prop, val) {
            var p = document.getElementById('bm-style-preview');
            if (p) p.style.setProperty(prop, val);
        }
        function updateHeader() {
            var isGrad  = $('[name=bm_ui_header_gradient]').prop('checked');
            var primary = $('#bm-ui-primary-color').val() || '';
            var hover   = $('#bm-ui-primary-hover-color').val() || '';
            css('--bm-header-bg', isGrad ? 'linear-gradient(135deg,' + primary + ',' + hover + ')' : primary);
        }
        function bindColor(id, prop) {
            $('#' + id).on('input', function () {
                css(prop, this.value);
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

        var $rng     = $('#bm-ui-radius');
        var $rvLabel = $('#bm-radius-val');
        $rng.on('input', function () {
            var v = parseInt(this.value, 10);
            css('--bm-radius', v + 'px');
            css('--bm-radius-sm', Math.max(0, v - 2) + 'px');
            $rvLabel.text(this.value);
        });

        var $btnRng    = $('#bm-ui-btn-radius');
        var $btnActual = $('#bm-ui-btn-radius-actual');
        var $btnLabel  = $('#bm-btn-radius-val');
        function updateBtnRadius() {
            var v = parseInt($btnRng.val(), 10), actual = (v >= 32) ? 999 : v;
            $btnActual.val(actual);
            $btnLabel.text(v >= 32 ? 'Pill' : v);
            css('--bm-radius-pill', actual + 'px');
        }
        $btnRng.on('input', updateBtnRadius);

        var $shd = $('#bm-ui-shadow');
        $shd.on('change', function () { css('--bm-shadow', SHADOWS[this.value] || 'none'); });

        var $fnt = $('#bm-ui-font-family'), $fontName = $('#bm-ui-custom-font-name');
        function updateFont() {
            var isCustom = $fnt.val() === 'custom';
            $('#bm-row-custom-font-url, #bm-row-custom-font-name').toggle(isCustom);
            css('--bm-font', (isCustom && $fontName.val())
                ? ('"' + $fontName.val() + '", sans-serif')
                : (FONTS[$fnt.val()] || 'sans-serif'));
        }
        $fnt.on('change', updateFont);
        $fontName.on('input', updateFont);
        $('[name=bm_ui_header_gradient]').on('change', updateHeader);

        $(document).on('click', '[data-bm-preset]', function (e) {
            e.preventDefault(); e.stopImmediatePropagation();
            var $btn = $(this), d;
            try { d = JSON.parse($btn.attr('data-bm-preset') || '{}'); } catch (ex) { return; }
            var cmap = {
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
            $.each(cmap, function (id, key) {
                if (d[key] !== undefined) { $('#' + id).val(d[key]).trigger('input'); }
            });
            if (d.radius !== undefined)     { $rng.val(d.radius).trigger('input'); }
            if (d.btn_radius !== undefined) { $btnRng.val(Math.min(32, parseInt(d.btn_radius, 10))); updateBtnRadius(); }
            if (d.shadow)      { $shd.val(d.shadow).trigger('change'); }
            if (d.font_family) { $fnt.val(d.font_family).trigger('change'); }
            $('[name=bm_ui_header_gradient]').prop('checked', d.header_gradient === '1').trigger('change');
            if (d.key) { $('#bm-ui-style-preset').val(d.key); }
            $('[data-bm-preset]').removeClass('button-primary').addClass('button');
            $btn.addClass('button-primary').removeClass('button');
        });

        $(document).on('click', '.bm-prev-tab', function (e) {
            e.preventDefault(); e.stopImmediatePropagation();
            $('.bm-prev-tab').removeClass('active');
            $('.bm-prev-pane').removeClass('active');
            $(this).addClass('active');
            var pane = $(this).data('pane');
            if (pane) { $('#' + pane).addClass('active'); }
        });

        $(document).on('click', '[data-bm-preview-link]', function (e) { e.preventDefault(); });

        // Initialize preview with current form values.
        if ($('#bm-style-preview').length) {
            $.each({
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
            }, function (id, prop) { var v = $('#' + id).val(); if (v) css(prop, v); });
            if ($rng.length) {
                var rv = parseInt($rng.val(), 10);
                css('--bm-radius', rv + 'px');
                css('--bm-radius-sm', Math.max(0, rv - 2) + 'px');
            }
            updateBtnRadius();
            if ($shd.length) css('--bm-shadow', SHADOWS[$shd.val()] || 'none');
            updateFont();
            updateHeader();
        } else {
            updateFont();
        }
    }

    /* ── Bootstrap ───────────────────────────────────────────────────────────  */
    $(function () {
        initCampTabs();
        initAddTaskRow();
        initSortable();
        initCalendar();
        initStylePreview();
    });

}(jQuery));
