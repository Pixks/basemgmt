<?php
defined('ABSPATH') || exit;
/**
 * @var array  $summary        – active_summary() result
 * @var int    $pending        – pending announcements count
 * @var object $report_counts  – {none, draft, submitted}
 * @var object $report_totals  – {total_participants, total_staff, total_workers}
 * @var array  $missing_camps  – camps without submitted report today
 * @var array  $active_alerts  – active weather alerts
 * @var string $today          – Y-m-d
 * @var array  $today_plans       – plan headers for today
 * @var int    $today_item_count  – total items in today's plan
 * @var int    $today_changed_count – items flagged as changed today
 * @var int    $pending_reservations – count of pending reservations
 * @var array  $upcoming_reservations – next approved reservations
 */
?>
<div class="wrap bm-admin-wrap">
	<h1><?php esc_html_e('Baza Obozowa – Dashboard', 'basemgmt'); ?></h1>

	<!-- Quick action buttons – top of dashboard -->
	<div class="bm-dashboard-links" style="margin-bottom:20px;">
		<a class="button button-primary" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-camps&action=new')); ?>">
			<?php esc_html_e('+ Nowy obóz', 'basemgmt'); ?>
		</a>
		<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-staff&action=new')); ?>">
			<?php esc_html_e('+ Nowa osoba kadry', 'basemgmt'); ?>
		</a>
		<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-announcements&action=new')); ?>">
			<?php esc_html_e('+ Nowe ogłoszenie', 'basemgmt'); ?>
		</a>
		<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-reports&bm_action=view_day&date=' . $today)); ?>">
			<?php esc_html_e('Raport zbiorczy dziś', 'basemgmt'); ?>
		</a>
		<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-schedule&filter_date=' . $today)); ?>">
			<?php esc_html_e('Plan dnia dziś', 'basemgmt'); ?>
		</a>
		<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-menu&bm_action=new&date=' . $today)); ?>">
			<?php esc_html_e('Dodaj jadłospis dziś', 'basemgmt'); ?>
		</a>
		<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-pdf')); ?>">
			<?php esc_html_e('🖨 Drukuj / PDF', 'basemgmt'); ?>
		</a>
	</div>

	<!-- Camp overview -->
	<h2 style="margin-top:20px;border-bottom:1px solid #ddd;padding-bottom:6px;"><?php esc_html_e('Obozy', 'basemgmt'); ?></h2>
	<div class="bm-stats-grid">
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Aktywne obozy', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html((string) $summary['camps']); ?></span>
		</div>
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Uczestnicy', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html((string) $summary['participants']); ?></span>
		</div>
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Kadra', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html((string) $summary['staff']); ?></span>
		</div>
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Pracownicy', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html((string) $summary['workers']); ?></span>
		</div>
		<div class="bm-stat-card <?php echo $pending ? 'bm-stat-card--alert' : ''; ?>">
			<span class="bm-stat-label"><?php esc_html_e('Ogłoszenia oczekujące', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html((string) $pending); ?></span>
			<?php if ($pending) : ?>
			<a class="bm-stat-action" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-announcements&filter_status=pending')); ?>">
				<?php esc_html_e('Zatwierdź', 'basemgmt'); ?> &rarr;
			</a>
			<?php endif; ?>
		</div>
	</div>

	<!-- Reports widget -->
	<h2 style="margin-top:24px;border-bottom:1px solid #ddd;padding-bottom:6px;">
		<?php printf(esc_html__('Meldunki – %s', 'basemgmt'), esc_html(date_i18n('d.m.Y', strtotime($today)))); ?>
	</h2>
	<div class="bm-stats-grid">
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Wysłane', 'basemgmt'); ?></span>
			<span class="bm-stat-value" style="color:#155724;"><?php echo esc_html((string) $report_counts->submitted); ?></span>
		</div>
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Robocze', 'basemgmt'); ?></span>
			<span class="bm-stat-value" style="color:#856404;"><?php echo esc_html((string) $report_counts->draft); ?></span>
		</div>
		<div class="bm-stat-card <?php echo count($missing_camps) ? 'bm-stat-card--alert' : ''; ?>">
			<span class="bm-stat-label"><?php esc_html_e('Brakujące', 'basemgmt'); ?></span>
			<span class="bm-stat-value" style="color:#c0392b;"><?php echo esc_html((string) count($missing_camps)); ?></span>
			<?php if ($missing_camps): ?>
			<a class="bm-stat-action" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-reports&date=' . $today)); ?>">
				<?php esc_html_e('Szczegóły', 'basemgmt'); ?> &rarr;
			</a>
			<?php endif; ?>
		</div>
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Uczestnicy (wysłane)', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html((string) $report_totals->total_participants); ?></span>
		</div>
	</div>

	<?php if ($active_alerts): ?>
	<!-- Weather alerts -->
	<h2 style="margin-top:24px;border-bottom:1px solid #ddd;padding-bottom:6px;"><?php esc_html_e('Aktywne komunikaty pogodowe', 'basemgmt'); ?></h2>
	<?php foreach ($active_alerts as $alert): ?>
	<div style="background:<?php echo $alert->is_urgent ? '#f8d7da' : '#fff3cd'; ?>;border:1px solid <?php echo $alert->is_urgent ? '#f5c6cb' : '#ffeeba'; ?>;border-radius:4px;padding:10px 14px;margin-bottom:8px;">
		<?php if ($alert->is_urgent): ?><strong>🔴 <?php esc_html_e('PILNE:', 'basemgmt'); ?></strong> <?php endif; ?>
		<strong><?php echo esc_html($alert->title); ?></strong>
		<p style="margin:4px 0 0;"><?php echo esc_html($alert->message); ?></p>
	</div>
	<?php endforeach; ?>
	<?php endif; ?>

	<!-- Schedule today widget -->
	<h2 style="margin-top:24px;border-bottom:1px solid #ddd;padding-bottom:6px;">
		📅 <?php printf(esc_html__('Plan dnia – %s', 'basemgmt'), esc_html(date_i18n('d.m.Y', strtotime($today)))); ?>
	</h2>
	<div class="bm-stats-grid">
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Plany na dziś', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html((string) count($today_plans)); ?></span>
		</div>
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Pozycji w planie', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html((string) $today_item_count); ?></span>
		</div>
		<div class="bm-stat-card <?php echo $today_changed_count ? 'bm-stat-card--alert' : ''; ?>">
			<span class="bm-stat-label"><?php esc_html_e('Zmiany na dziś', 'basemgmt'); ?></span>
			<span class="bm-stat-value" style="<?php echo $today_changed_count ? 'color:#856404;' : ''; ?>"><?php echo esc_html((string) $today_changed_count); ?></span>
		</div>
	</div>
	<p style="margin-top:8px;">
		<a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-schedule&filter_date=' . $today)); ?>">
			<?php esc_html_e('Edytuj plan dziś', 'basemgmt'); ?> →
		</a>
	</p>

	<!-- Reservations widget -->
	<h2 style="margin-top:24px;border-bottom:1px solid #ddd;padding-bottom:6px;">
		🏕 <?php esc_html_e('Rezerwacje', 'basemgmt'); ?>
	</h2>
	<div class="bm-stats-grid">
		<div class="bm-stat-card <?php echo $pending_reservations ? 'bm-stat-card--alert' : ''; ?>">
			<span class="bm-stat-label"><?php esc_html_e('Oczekujące', 'basemgmt'); ?></span>
			<span class="bm-stat-value" style="<?php echo $pending_reservations ? 'color:#856404;' : ''; ?>"><?php echo esc_html((string) $pending_reservations); ?></span>
			<?php if ($pending_reservations): ?>
			<a class="bm-stat-action" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-reservations&filter_status=pending')); ?>"><?php esc_html_e('Rozpatrz', 'basemgmt'); ?> →</a>
			<?php endif; ?>
		</div>
	</div>
	<?php if ($upcoming_reservations): ?>
	<p style="font-weight:600;margin:12px 0 4px;"><?php esc_html_e('Najbliższe zatwierdzone:', 'basemgmt'); ?></p>
	<?php
	// Load resources for display.
	$res_map = [];
	foreach ( \BaseMgmt\Modules\Reservations\ResourceRepository::get_active() as $r ) { $res_map[(int)$r->id] = $r->name; }
	$camp_map = [];
	foreach ( \BaseMgmt\Modules\Camps\CampRepository::get_all() as $c ) { $camp_map[(int)$c->id] = $c->name; }
	?>
	<table class="wp-list-table widefat fixed striped" style="max-width:600px;">
		<thead><tr><th><?php esc_html_e('Data', 'basemgmt'); ?></th><th><?php esc_html_e('Zasób', 'basemgmt'); ?></th><th><?php esc_html_e('Obóz', 'basemgmt'); ?></th><th><?php esc_html_e('Godziny', 'basemgmt'); ?></th></tr></thead>
		<tbody>
		<?php foreach ($upcoming_reservations as $res): ?>
		<tr>
			<td><?php echo esc_html(date_i18n('d.m.Y', strtotime($res->res_date))); ?></td>
			<td><?php echo esc_html($res_map[(int)$res->resource_id] ?? '—'); ?></td>
			<td><?php echo esc_html($camp_map[(int)$res->camp_id] ?? '—'); ?></td>
			<td><?php echo esc_html($res->start_time . ' – ' . $res->end_time); ?></td>
		</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>



	<!-- Meal menu today widget -->
	<h2 style="margin-top:24px;border-bottom:1px solid #ddd;padding-bottom:6px;">
		🍽 <?php printf(esc_html__('Jadłospis – %s', 'basemgmt'), esc_html(date_i18n('d.m.Y', strtotime($today)))); ?>
	</h2>
	<?php if ($today_menu): ?>
	<p style="color:#155724;">✓ <?php printf(esc_html__('Jadłospis na dziś opublikowany – %d posiłki/ów.', 'basemgmt'), count($today_menu['items'])); ?></p>
	<?php else: ?>
	<p style="color:#856404;">⚠ <?php esc_html_e('Brak jadłospisu na dziś.', 'basemgmt'); ?></p>
	<?php endif; ?>

	<!-- Communication widget -->
	<h2 style="margin-top:24px;border-bottom:1px solid #ddd;padding-bottom:6px;">
		💬 <?php esc_html_e('Komunikacja', 'basemgmt'); ?>
	</h2>
	<div class="bm-stats-grid">
		<div class="bm-stat-card <?php echo $unread_messages ? 'bm-stat-card--alert' : ''; ?>">
			<span class="bm-stat-label"><?php esc_html_e('Nieprzeczytane wiadomości', 'basemgmt'); ?></span>
			<span class="bm-stat-value" style="<?php echo $unread_messages ? 'color:#c0392b;' : ''; ?>"><?php echo esc_html((string) $unread_messages); ?></span>
			<?php if ($unread_messages): ?>
			<a class="bm-stat-action" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-communication&filter_unread=1')); ?>"><?php esc_html_e('Czytaj', 'basemgmt'); ?> →</a>
			<?php endif; ?>
		</div>
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Otwarte wątki', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html((string) $open_threads); ?></span>
		</div>
	</div>

	<!-- Help widget -->
	<h2 style="margin-top:24px;border-bottom:1px solid #ddd;padding-bottom:6px;">
		📚 <?php esc_html_e('Baza pomocy', 'basemgmt'); ?>
	</h2>
	<div class="bm-stats-grid">
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Ważne / alarmowe wpisy', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html((string) $important_help); ?></span>
		</div>
	</div>
	<p>
		<a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-help&bm_action=new')); ?>">
			<?php esc_html_e('+ Nowy wpis pomocy', 'basemgmt'); ?> →
		</a>
	</p>

	<?php
	// ── Forms & Submissions widget ──────────────────────────────────────────
	$sub_statuses = \BaseMgmt\Modules\Forms\SubmissionRepository::STATUSES;
	$sub_cats     = \BaseMgmt\Modules\Forms\FormRepository::CATEGORIES;
	?>
	<h2 style="margin-top:24px;border-bottom:1px solid #ddd;padding-bottom:6px;">📋 <?php esc_html_e('Formularze i Zgłoszenia', 'basemgmt'); ?></h2>
	<div class="bm-stats-grid">
		<div class="bm-stat-card <?php echo $new_submissions > 0 ? 'bm-stat-card--alert' : ''; ?>">
			<span class="bm-stat-label"><?php esc_html_e('Nowe zgłoszenia', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html((string) $new_submissions); ?></span>
			<?php if ( $new_submissions > 0 ) : ?>
			<a class="bm-stat-action" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-forms&view=submissions&filter_status=new')); ?>"><?php esc_html_e('Zobacz', 'basemgmt'); ?> →</a>
			<?php endif; ?>
		</div>
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Otwarte zgłoszenia', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html((string) $open_submissions); ?></span>
		</div>
		<div class="bm-stat-card">
			<span class="bm-stat-label"><?php esc_html_e('Aktywne formularze', 'basemgmt'); ?></span>
			<span class="bm-stat-value"><?php echo esc_html((string) $active_forms); ?></span>
		</div>
	</div>

	<?php if ( ! empty($recent_submissions) ) : ?>
	<h3><?php esc_html_e('Ostatnie zgłoszenia', 'basemgmt'); ?></h3>
	<table class="wp-list-table widefat fixed striped" style="max-width:900px">
		<thead><tr>
			<th>#</th>
			<th><?php esc_html_e('Status', 'basemgmt'); ?></th>
			<th><?php esc_html_e('Kategoria', 'basemgmt'); ?></th>
			<th><?php esc_html_e('Data', 'basemgmt'); ?></th>
		</tr></thead>
		<tbody>
		<?php foreach ( $recent_submissions as $rs ) : ?>
			<tr>
				<td><a href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-forms&view=view_submission&id=' . (int)$rs->id)); ?>">#<?php echo esc_html($rs->id); ?></a></td>
				<td><?php echo esc_html($sub_statuses[$rs->status] ?? $rs->status); ?></td>
				<td><?php echo esc_html($sub_cats[$rs->category] ?? $rs->category); ?></td>
				<td><?php echo esc_html(date_i18n('d.m.Y H:i', strtotime($rs->created_at))); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>

	<p><a class="button button-small" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-forms')); ?>"><?php esc_html_e('Zarządzaj formularzami', 'basemgmt'); ?> →</a></p>

	<p class="description" style="margin-top:20px;">
		<?php printf(esc_html__('Baza Obozowa v%s', 'basemgmt'), esc_html(BASEMGMT_VERSION)); ?>
	</p>
</div>
