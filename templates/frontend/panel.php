<?php
/**
 * Frontend panel template.
 * Shows access screen when unauthenticated; panel when session is valid.
 * Bootstraps JavaScript with all required data attributes.
 */
defined('ABSPATH') || exit;

use BaseMgmt\Auth\SessionManager;
use BaseMgmt\Modules\Camps\StaffRepository;

$session      = SessionManager::current();
$authenticated = $session !== null;
?>

<?php if ( ! $authenticated ) : ?>
	<?php include __DIR__ . '/access.php'; ?>
<?php else :
	$staff        = StaffRepository::get((int) $session->staff_id);
	$display_name = $staff ? esc_html($staff->first_name . ' ' . $staff->last_name) : '';
?>
<div id="bm-panel"
	 class="bm-container bm-panel"
	 data-camp-id="<?php echo esc_attr($session->camp_id); ?>"
	 data-staff-id="<?php echo esc_attr($session->staff_id); ?>"
	 data-nonce="<?php echo esc_attr(wp_create_nonce('bm_panel')); ?>">

	<header class="bm-panel__header">
		<div class="bm-panel__title">
			<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
			<span id="bm-camp-name"><?php esc_html_e('Ładowanie…', 'basemgmt'); ?></span>
		</div>
		<div class="bm-panel__user">
			<span class="bm-panel__username"><?php echo esc_html($display_name); ?></span>
			<button id="bm-logout-btn" class="bm-btn bm-btn--ghost bm-btn--sm">
				<?php esc_html_e('Wyloguj', 'basemgmt'); ?>
			</button>
		</div>
	</header>

	<nav class="bm-panel__tabs" role="tablist">
		<button class="bm-tab bm-tab--active" role="tab" aria-selected="true"  aria-controls="bm-tab-overview"      data-tab="overview">
			<?php esc_html_e('Przegląd', 'basemgmt'); ?>
		</button>
		<button class="bm-tab" role="tab" aria-selected="false" aria-controls="bm-tab-count"         data-tab="count">
			<?php esc_html_e('Liczebność', 'basemgmt'); ?>
		</button>
		<button class="bm-tab" role="tab" aria-selected="false" aria-controls="bm-tab-announcements" data-tab="announcements">
			<?php esc_html_e('Ogłoszenia', 'basemgmt'); ?>
			<span id="bm-pending-badge" class="bm-badge bm-badge--urgent" style="display:none"></span>
		</button>
	</nav>

	<!-- TAB: Overview -->
	<section id="bm-tab-overview" class="bm-tab-content" role="tabpanel">
		<div id="bm-overview-content">
			<p class="bm-loading"><?php esc_html_e('Ładowanie danych obozu…', 'basemgmt'); ?></p>
		</div>
	</section>

	<!-- TAB: Daily Count -->
	<section id="bm-tab-count" class="bm-tab-content" style="display:none" role="tabpanel">
		<h3><?php esc_html_e('Dzienny stan liczebności', 'basemgmt'); ?></h3>
		<div id="bm-count-submitted-notice" class="bm-alert bm-alert--success" style="display:none">
			<?php esc_html_e('Stan liczebności na dziś został już zapisany. Możesz go zaktualizować poniżej.', 'basemgmt'); ?>
		</div>
		<form id="bm-count-form" class="bm-form">
			<div class="bm-form__row">
				<div class="bm-form__group">
					<label for="bm-participants" class="bm-form__label">
						<?php esc_html_e('Uczestnicy', 'basemgmt'); ?>
					</label>
					<input type="number" id="bm-participants" name="participants" class="bm-form__input bm-form__input--number"
						   min="0" value="0" required>
				</div>
				<div class="bm-form__group">
					<label for="bm-staff" class="bm-form__label">
						<?php esc_html_e('Kadra', 'basemgmt'); ?>
					</label>
					<input type="number" id="bm-staff" name="staff" class="bm-form__input bm-form__input--number"
						   min="0" value="0" required>
				</div>
				<div class="bm-form__group">
					<label for="bm-workers" class="bm-form__label">
						<?php esc_html_e('Pracownicy', 'basemgmt'); ?>
					</label>
					<input type="number" id="bm-workers" name="workers" class="bm-form__input bm-form__input--number"
						   min="0" value="0" required>
				</div>
			</div>
			<div class="bm-form__group">
				<p class="bm-total-preview">
					<?php esc_html_e('Łącznie:', 'basemgmt'); ?>
					<strong id="bm-count-total">0</strong>
				</p>
			</div>
			<div class="bm-form__group">
				<label for="bm-count-notes" class="bm-form__label"><?php esc_html_e('Uwagi', 'basemgmt'); ?></label>
				<textarea id="bm-count-notes" name="notes" class="bm-form__textarea" rows="2"></textarea>
			</div>
			<div id="bm-count-error"  class="bm-alert bm-alert--error"   style="display:none"></div>
			<div id="bm-count-success" class="bm-alert bm-alert--success" style="display:none"></div>
			<button type="submit" class="bm-btn bm-btn--primary">
				<?php esc_html_e('Zapisz stan liczebności', 'basemgmt'); ?>
			</button>
		</form>
	</section>

	<!-- TAB: Announcements -->
	<section id="bm-tab-announcements" class="bm-tab-content" style="display:none" role="tabpanel">
		<div class="bm-announcements-layout">
			<!-- Board -->
			<div class="bm-announcements-board">
				<h3><?php esc_html_e('Aktywne ogłoszenia', 'basemgmt'); ?></h3>
				<div id="bm-announcements-active">
					<p class="bm-loading"><?php esc_html_e('Ładowanie ogłoszeń…', 'basemgmt'); ?></p>
				</div>

				<h3><?php esc_html_e('Archiwum', 'basemgmt'); ?></h3>
				<div id="bm-announcements-archived"></div>

				<h3><?php esc_html_e('Moje ogłoszenia', 'basemgmt'); ?></h3>
				<div id="bm-announcements-own"></div>
			</div>

			<!-- Submit form -->
			<div class="bm-announcements-form">
				<h3><?php esc_html_e('Dodaj ogłoszenie', 'basemgmt'); ?></h3>
				<p class="description"><?php esc_html_e('Ogłoszenie trafi do akceptacji administratora.', 'basemgmt'); ?></p>
				<form id="bm-ann-form" class="bm-form">
					<div class="bm-form__group">
						<label for="bm-ann-title" class="bm-form__label"><?php esc_html_e('Tytuł', 'basemgmt'); ?> *</label>
						<input type="text" id="bm-ann-title" name="title" class="bm-form__input" required>
					</div>
					<div class="bm-form__group">
						<label for="bm-ann-content" class="bm-form__label"><?php esc_html_e('Treść', 'basemgmt'); ?></label>
						<textarea id="bm-ann-content" name="content" class="bm-form__textarea" rows="4"></textarea>
					</div>
					<div class="bm-form__row">
						<div class="bm-form__group">
							<label for="bm-ann-from" class="bm-form__label"><?php esc_html_e('Od', 'basemgmt'); ?> *</label>
							<input type="date" id="bm-ann-from" name="valid_from" class="bm-form__input" required>
						</div>
						<div class="bm-form__group">
							<label for="bm-ann-until" class="bm-form__label"><?php esc_html_e('Do', 'basemgmt'); ?> *</label>
							<input type="date" id="bm-ann-until" name="valid_until" class="bm-form__input" required>
						</div>
					</div>
					<div class="bm-form__group">
						<label for="bm-ann-attach" class="bm-form__label"><?php esc_html_e('URL załącznika', 'basemgmt'); ?></label>
						<input type="url" id="bm-ann-attach" name="attachment_url" class="bm-form__input"
							   placeholder="https://…">
					</div>
					<div id="bm-ann-error"   class="bm-alert bm-alert--error"   style="display:none"></div>
					<div id="bm-ann-success" class="bm-alert bm-alert--success" style="display:none"></div>
					<button type="submit" class="bm-btn bm-btn--primary">
						<?php esc_html_e('Wyślij do zatwierdzenia', 'basemgmt'); ?>
					</button>
				</form>
			</div>
		</div>
	</section>

</div>
<?php endif; ?>
