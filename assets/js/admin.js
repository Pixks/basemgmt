/* Baza Obozowa – Admin JS */
(function ($) {
    'use strict';

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
        initSortable();
        initCalendar();
    });

}(jQuery));
