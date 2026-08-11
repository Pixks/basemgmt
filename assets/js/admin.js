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

    /* ── Bootstrap ───────────────────────────────────────────────────────────  */
    $(function () {
        initCampTabs();
        initAddTaskRow();
        initSortable();
        initCalendar();
    });

}(jQuery));
