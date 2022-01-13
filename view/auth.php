<?php
/**
 * Authenticate
 */

?>

<form autocomplete="x" method="post">
<div class="auth-wrap">
<div class="card">

	<h1 class="card-header"><?= h($data['Page']['title']) ?></h1>
	<div class="card-body">

<div class="form-group">
	<label>CRE:</label>
	<select class="form-control" id="cre" name="cre">
	<option value="">- Select CRE -</option>
	<?php
	foreach ($data['cre_list'] as $cre_stub => $cre_data) {
		$sel = ($cre_stub == $data['cre_code'] ? ' selected ' : null);
	?>
		<option data-engine="<?= $cre_data['engine'] ?>" <?= $sel ?> value="<?= $cre_stub ?>">
			<?= h($cre_data['name'] ?: $cre_stub )?>
		</option>
	<?php
	}
	?>
	</select>
</div>

<div class="form-group" data-ccrs="true" data-metrc="true">
	<label>Service API Key:</label>
	<input autocomplete="x"
		class="form-control" name="service-key"
		placeholder="CCRS, METRC" value="<?= h($data['cre_service_key']) ?>">
</div>

<div class="form-group" data-ccrs="true" data-biotrack="true" data-leafdata="true">
	<label>Company:</label>
	<input autocomplete="x"
		class="form-control company-autocomplete" name="company"
		placeholder="Company ID, like a UBI or something - CCRS, BioTrack, LeafData" value="<?= h($data['cre_company']) ?>">
</div>

<div class="form-group" data-ccrs="true" data-leafdata="true" data-metrc="true">
	<label>License:</label>
	<input autocomplete="x"
		class="form-control license-autocomplete" name="license"
		placeholder="CCRS, LeafData, METRC*" value="<?= h($data['cre_license']) ?>">
</div>

<div class="form-group" data-leafdata="true" data-metrc="true">
	<label>License API Key:</label>
	<input autocomplete="x"
		class="form-control" name="license-key"
		placeholder="LeafData, METRC" value="<?= h($data['cre_license_key']) ?>">
</div>

<div class="form-group" data-biotrack="true">
	<label>Username:</label>
	<input autocomplete="x"
		class="form-control" name="username"
		placeholder="BioTrack" value="<?= h($data['cre_username']) ?>">
</div>

<div class="form-group" data-biotrack="true">
	<label>Password:</label>
	<input autocomplete="x"
		class="form-control" name="password"
		placeholder="BioTrack" value="<?= h($data['cre_password']) ?>">
</div>
<?php
if (!empty($data['google_recaptcha_v2'])) {
?>
	<div class="form-group">
		<div class="g-recaptcha" data-sitekey="<?= $data['google_recaptcha_v2']['public'] ?>"></div>
	</div>
	<script src="https://www.google.com/recaptcha/api.js"></script>
<?php
} elseif (!empty($data['google_recaptcha_v3'])) {
?>
	<script src="https://www.google.com/recaptcha/api.js?render=<?= $data['google_recaptcha_v3']['public'] ?>"></script>
	<script>
	grecaptcha.ready(function() {
		grecaptcha.execute('<?= $data['google_recaptcha_v3']['public'] ?>', {action: 'login'}).then(function(token) {
			$('').val(token);
		});
	});
  </script>
<?php
}
?>
	</div>

	<div class="card-footer">
		<button class="btn btn-lg btn-outline-primary" name="a" type="submit" value="auth-web">Connect</button>
	</div>
</div>
</div>
</form>


<script>
$(function() {

	$('#cre').on('change', function() {

		var $opt = $(this).find(':selected');
		var eng = $opt.data('engine');

		$('div[data-biotrack="true"]').hide();
		$('div[data-leafdata="true"]').hide();
		$('div[data-metrc="true"]').hide();

		var sel = 'div[data-' + eng + '="true"]';
		$(sel).show();

	});

	$('.company-autocomplete').autocomplete({
		source: 'https://<?= $data['OpenTHC']['dir']['hostname'] ?>/api/autocomplete/company',
	});

	$('.license-autocomplete').autocomplete({
		source: 'https://<?= $data['OpenTHC']['dir']['hostname'] ?>/api/autocomplete/license',
	});

});
</script>
