<?php
/**
 * Frontend access screen template.
 * Rendered when [camp_panel] shortcode is used but no valid session exists.
 */
defined('ABSPATH') || exit;
?>
<div id="bm-access-screen" class="bm-container bm-access">
	<div class="bm-access__card">
		<div class="bm-access__logo">
			<span class="dashicons dashicons-calendar-alt" aria-hidden="true"></span>
		</div>
		<h2 class="bm-access__title"><?php esc_html_e('Panel Obozu', 'basemgmt'); ?></h2>
		<p class="bm-access__subtitle"><?php esc_html_e('Zaloguj się, aby wejść do panelu swojego obozu.', 'basemgmt'); ?></p>

		<form id="bm-login-form" class="bm-form" novalidate>

			<div class="bm-form__group">
				<label for="bm-camp-select" class="bm-form__label">
					<?php esc_html_e('Twój obóz', 'basemgmt'); ?> <span aria-hidden="true">*</span>
				</label>
				<select id="bm-camp-select" name="camp_id" class="bm-form__select" required aria-required="true">
					<option value=""><?php esc_html_e('— Wybierz obóz —', 'basemgmt'); ?></option>
				</select>
			</div>

			<div id="bm-staff-group" class="bm-form__group" style="display:none" aria-hidden="true">
				<label for="bm-staff-select" class="bm-form__label">
					<?php esc_html_e('Twoje imię i nazwisko', 'basemgmt'); ?> <span aria-hidden="true">*</span>
				</label>
				<select id="bm-staff-select" name="staff_id" class="bm-form__select" required aria-required="true">
					<option value=""><?php esc_html_e('— Wybierz siebie —', 'basemgmt'); ?></option>
				</select>
			</div>

			<div id="bm-code-group" class="bm-form__group" style="display:none" aria-hidden="true">
				<label for="bm-security-code" class="bm-form__label">
					<?php esc_html_e('Kod bezpieczeństwa', 'basemgmt'); ?> <span aria-hidden="true">*</span>
				</label>
				<input type="password" id="bm-security-code" name="security_code"
					   class="bm-form__input" autocomplete="off"
					   placeholder="<?php esc_attr_e('Wpisz swój kod', 'basemgmt'); ?>"
					   aria-required="true">
			</div>

			<div id="bm-login-error" class="bm-alert bm-alert--error" role="alert" style="display:none"></div>

			<div id="bm-submit-group" class="bm-form__group" style="display:none">
				<button type="submit" id="bm-login-btn" class="bm-btn bm-btn--primary bm-btn--full">
					<?php esc_html_e('Wejdź do panelu', 'basemgmt'); ?>
				</button>
			</div>
		</form>
	</div>
</div>
