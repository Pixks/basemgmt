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
        var presets = document.querySelectorAll('[data-bm-preset]');
        if (!presets.length) return;

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

        // Live-preview: inject a <style> tag with high-specificity selector so that
        // dynamically-set CSS custom properties WIN over the `.bm-ui { --var: saved }` rule
        // in bm-shortcodes.css (which also matches child .bm-ui elements in the preview).
        var _previewVars = {};
        var _previewStyleEl = null;
        function css(prop, val) {
            _previewVars[prop] = val;
            if (!_previewStyleEl) {
                _previewStyleEl = document.createElement('style');
                _previewStyleEl.id = 'bm-preview-live-vars';
                document.head.appendChild(_previewStyleEl);
            }
            var decls = Object.keys(_previewVars).map(function (p) {
                return p + ':' + _previewVars[p];
            }).join(';');
            // #bm-style-preview has ID specificity (1,0,0) which beats .bm-ui (0,1,0)
            // on all matched elements – both the root and nested .bm-ui children.
            _previewStyleEl.textContent =
                '#bm-style-preview,#bm-style-preview .bm-ui{' + decls + '}';
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

        // Use native addEventListener on document for maximum compatibility.
        document.addEventListener('click', function (e) {
            // Preset buttons
            var presetBtn = e.target.closest('[data-bm-preset]');
            if (presetBtn) {
                e.preventDefault();
                var d;
                try { d = JSON.parse(presetBtn.getAttribute('data-bm-preset') || '{}'); } catch (ex) { return; }
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
                Object.keys(cmap).forEach(function (id) {
                    var key = cmap[id], el = document.getElementById(id);
                    if (el && d[key] !== undefined) { el.value = d[key]; $(el).trigger('input'); }
                });
                if (d.radius !== undefined)     { $rng.val(d.radius).trigger('input'); }
                if (d.btn_radius !== undefined) { $btnRng.val(Math.min(32, parseInt(d.btn_radius, 10))); updateBtnRadius(); }
                if (d.shadow)      { $shd.val(d.shadow).trigger('change'); }
                if (d.font_family) { $fnt.val(d.font_family).trigger('change'); }
                $('[name=bm_ui_header_gradient]').prop('checked', d.header_gradient === '1').trigger('change');
                if (d.key) { $('#bm-ui-style-preset').val(d.key); }
                document.querySelectorAll('[data-bm-preset]').forEach(function (b) {
                    b.classList.remove('button-primary'); b.classList.add('button');
                });
                presetBtn.classList.add('button-primary'); presetBtn.classList.remove('button');
                return;
            }
            // Preview tab buttons
            var tabBtn = e.target.closest('.bm-prev-tab');
            if (tabBtn) {
                e.preventDefault();
                document.querySelectorAll('.bm-prev-tab').forEach(function (b) { b.classList.remove('active'); });
                document.querySelectorAll('.bm-prev-pane').forEach(function (p) { p.classList.remove('active'); });
                tabBtn.classList.add('active');
                var pane = tabBtn.getAttribute('data-pane');
                if (pane) { var el = document.getElementById(pane); if (el) el.classList.add('active'); }
                return;
            }
            // Preview links – block navigation
            if (e.target.closest('[data-bm-preview-link]')) { e.preventDefault(); }
        }, true); // capture phase

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

    /* ── Collapsible sidebar sub-groups ──────────────────────────────────────
     * Groups of related sub-pages (e.g. Organizacja, Plan dnia, Jadłospis)
     * are collapsed/expanded via an arrow toggle. State is persisted in
     * localStorage so that WP sidebar refreshes don't lose the position.
     * Default state: COLLAPSED. localStorage stores the list of EXPANDED groups.
     * ─────────────────────────────────────────────────────────────────────── */
    function initCollapsibleMenu() {
        var GROUPS = [
            {
                id: 'org',
                parentSlug: 'basemgmt-org',
                childSlugs: [
                    'basemgmt-org-documents',
                    'basemgmt-org-doc-templates',
                    'basemgmt-org-declarations',
                    'basemgmt-org-finance',
                    'basemgmt-org-tasks',
                    'basemgmt-org-accommodations',
                    'basemgmt-org-diets',
                ],
            },
            {
                id: 'schedule',
                parentSlug: 'basemgmt-schedule',
                childSlugs: ['basemgmt-plan-templates'],
            },
            {
                id: 'meal',
                parentSlug: 'basemgmt-menu',
                childSlugs: ['basemgmt-meal-templates', 'basemgmt-meal-options'],
            },
        ];

        // localStorage stores IDs of EXPANDED groups. Default = collapsed.
        var STORAGE_KEY = 'bm_menu_expanded';
        var topMenu = document.querySelector('#toplevel_page_basemgmt > ul.wp-submenu');
        if (!topMenu) return;

        function loadState() {
            try { return JSON.parse(localStorage.getItem(STORAGE_KEY) || '[]'); } catch (e) { return []; }
        }
        function saveState(expanded) {
            try { localStorage.setItem(STORAGE_KEY, JSON.stringify(expanded)); } catch (e) {}
        }

        function findItem(slug) {
            return topMenu.querySelector('li > a[href*="page=' + slug + '"]');
        }

        var currentPage = (function () {
            var m = window.location.search.match(/[?&]page=([^&]+)/);
            return m ? decodeURIComponent(m[1]) : '';
        }());

        GROUPS.forEach(function (g) {
            var parentA = findItem(g.parentSlug);
            if (!parentA) return;
            var parentLi = parentA.parentElement;

            var childLis = [];
            g.childSlugs.forEach(function (slug) {
                var a = findItem(slug);
                if (a) childLis.push(a.parentElement);
            });
            if (!childLis.length) return;

            parentLi.setAttribute('data-bm-group', g.id);
            childLis.forEach(function (li) { li.setAttribute('data-bm-group-child', g.id); });

            var arrow = document.createElement('span');
            arrow.className = 'bm-menu-arrow';
            arrow.setAttribute('aria-hidden', 'true');
            parentA.appendChild(arrow);

            // Group is forced open when one of its pages is currently active.
            var groupIsActive = currentPage === g.parentSlug ||
                g.childSlugs.indexOf(currentPage) !== -1;

            // Default: collapsed. Open only if localStorage says expanded OR group is active.
            var expanded = loadState();
            var isCollapsed = !groupIsActive && expanded.indexOf(g.id) === -1;

            function applyState() {
                if (isCollapsed) {
                    parentLi.setAttribute('data-bm-collapsed', '1');
                    arrow.setAttribute('data-bm-collapsed', '1');
                    childLis.forEach(function (li) { li.setAttribute('data-bm-hidden', '1'); });
                } else {
                    parentLi.removeAttribute('data-bm-collapsed');
                    arrow.removeAttribute('data-bm-collapsed');
                    childLis.forEach(function (li) { li.removeAttribute('data-bm-hidden'); });
                }
            }

            applyState();

            parentA.addEventListener('click', function (e) {
                if (g.parentSlug === 'basemgmt-org' || e.target === arrow || e.target.classList.contains('bm-menu-arrow')) {
                    e.preventDefault();
                    isCollapsed = !isCollapsed;
                    applyState();
                    var st = loadState();
                    if (isCollapsed) {
                        // Remove from expanded list
                        st = st.filter(function (x) { return x !== g.id; });
                    } else {
                        // Add to expanded list
                        if (st.indexOf(g.id) === -1) st.push(g.id);
                    }
                    saveState(st);
                }
            });
        });
    }

    /* ── Bootstrap ───────────────────────────────────────────────────────────  */
    $(function () {
        initCampTabs();
        initAddTaskRow();
        initSortable();
        initCalendar();
        initStylePreview();
        initCollapsibleMenu();
    });

}(jQuery));
