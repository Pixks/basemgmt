<?php defined('ABSPATH') || exit;
$is_edit = ! is_null($member);
$id      = $is_edit ? (int) $member->id : 0;
?>
<div class="wrap bm-admin-wrap">
	<h1><?php echo $is_edit ? esc_html__('Edytuj osobę kadry', 'basemgmt') : esc_html__('Nowa osoba kadry', 'basemgmt'); ?></h1>

	<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		<?php wp_nonce_field('bm_save_staff'); ?>
		<input type="hidden" name="action"   value="bm_save_staff">
		<input type="hidden" name="staff_id" value="<?php echo esc_attr($id); ?>">

		<table class="form-table" role="presentation">
			<tr>
				<th><label for="bm_camp"><?php esc_html_e('Obóz', 'basemgmt'); ?> <span class="required">*</span></label></th>
				<td>
					<select id="bm_camp" name="camp_id" required>
						<option value=""><?php esc_html_e('— Wybierz obóz —', 'basemgmt'); ?></option>
						<?php foreach ($camps as $c) : ?>
							<option value="<?php echo esc_attr($c->id); ?>" <?php selected($member->camp_id ?? '', $c->id); ?>>
								<?php echo esc_html($c->name); ?>
							</option>
						<?php endforeach; ?>
					</select>
				</td>
			</tr>
			<tr>
				<th><label for="bm_first"><?php esc_html_e('Imię', 'basemgmt'); ?> <span class="required">*</span></label></th>
				<td><input type="text" id="bm_first" name="first_name" class="regular-text" required
					   value="<?php echo esc_attr($member->first_name ?? ''); ?>"></td>
			</tr>
			<tr>
				<th><label for="bm_last"><?php esc_html_e('Nazwisko', 'basemgmt'); ?> <span class="required">*</span></label></th>
				<td><input type="text" id="bm_last" name="last_name" class="regular-text" required
					   value="<?php echo esc_attr($member->last_name ?? ''); ?>"></td>
			</tr>
			<tr>
				<th><label for="bm_role"><?php esc_html_e('Rola w obozie', 'basemgmt'); ?></label></th>
				<td>
					<input type="text" id="bm_role" name="role_in_camp" class="regular-text"
						   list="bm_roles_list"
						   value="<?php echo esc_attr($member->role_in_camp ?? ''); ?>">
					<datalist id="bm_roles_list">
						<option value="Komendant">
						<option value="Zastępca komendanta">
						<option value="Kwatermistrz">
						<option value="Wychowawca">
						<option value="Pielęgniarka">
						<option value="Kucharz">
						<option value="Kierowca">
					</datalist>
				</td>
			</tr>
			<tr>
				<th><label for="bm_email"><?php esc_html_e('Email', 'basemgmt'); ?></label></th>
				<td><input type="email" id="bm_email" name="email" class="regular-text"
					   value="<?php echo esc_attr($member->email ?? ''); ?>"></td>
			</tr>
			<tr>
				<th><label for="bm_phone"><?php esc_html_e('Telefon', 'basemgmt'); ?></label></th>
				<td><input type="tel" id="bm_phone" name="phone" class="regular-text"
					   value="<?php echo esc_attr($member->phone ?? ''); ?>"></td>
			</tr>
			<tr>
				<th><label for="bm_active"><?php esc_html_e('Aktywny', 'basemgmt'); ?></label></th>
				<td>
					<input type="checkbox" id="bm_active" name="is_active" value="1"
						   <?php checked((bool) ($member->is_active ?? true)); ?>>
					<label for="bm_active"><?php esc_html_e('Osoba może logować się do panelu', 'basemgmt'); ?></label>
				</td>
			</tr>
			<tr>
				<th><label for="bm_code"><?php esc_html_e('Kod bezpieczeństwa', 'basemgmt'); ?></label></th>
				<td>
					<input type="password" id="bm_code" name="security_code" class="regular-text" autocomplete="new-password"
						   placeholder="<?php echo $is_edit ? esc_attr__('Zostaw puste, aby nie zmieniać', 'basemgmt') : esc_attr__('Wymagany', 'basemgmt'); ?>"
						   <?php echo ! $is_edit ? 'required' : ''; ?>>
					<p class="description">
						<?php echo $is_edit
							? esc_html__('Wpisz nowy kod, aby go zmienić. Zostaw puste, żeby zachować obecny.', 'basemgmt')
							: esc_html__('Minimum 4 znaki. Zostanie zaszyfrowany.', 'basemgmt'); ?>
					</p>
				</td>
			</tr>
		</table>

		<p class="submit">
			<button type="submit" class="button button-primary">
				<?php echo $is_edit ? esc_html__('Zapisz zmiany', 'basemgmt') : esc_html__('Dodaj osobę', 'basemgmt'); ?>
			</button>
			<a class="button" href="<?php echo esc_url(admin_url('admin.php?page=basemgmt-staff')); ?>">
				<?php esc_html_e('Anuluj', 'basemgmt'); ?>
			</a>
		</p>
	</form>
</div>
