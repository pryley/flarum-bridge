<?php defined( 'WPINC' ) || die; ?>

<div class="wrap">
	<h1><?= $title; ?></h1>

	<form method="post" action="options.php" enctype="multipart/form-data">

		<?php settings_fields( $id ); ?>

		<table class="form-table" id="settings">
			<tbody>
				<tr>
					<th scope="row">
						<label for="flarum_url"><?= __( 'Flarum URL', 'flarum-bridge' ); ?></label>
					</th>
					<td>
						<input type="text" id="flarum_url" name="<?= $id; ?>[flarum_url]" class="regular-text code" aria-describedby="flarum_url_description" value="<?= $settings->flarum_url; ?>" placeholder="<?= $defaults->flarum_url; ?>">
						<p id="flarum_url_description" class="description"><?= __( 'The relative URL to your Flarum installation', 'flarum-bridge' ) ?></p>
					</td>
				</tr>
				<tr>
					<th scope="row">
						<label for="flarum_api_key"><?= __( 'Flarum API Key', 'flarum-bridge' ); ?></label>
					</th>
					<td>
						<input type="text" id="flarum_api_key" name="<?= $id; ?>[flarum_api_key]" class="regular-text code" aria-describedby="flarum_api_key_description" value="<?= $settings->flarum_api_key; ?>" placeholder="<?= $defaults->flarum_api_key; ?>">
						<p id="flarum_api_key_description" class="description"><?= __( 'Create a random key in the api_keys table of your Flarum forum and enter it here', 'flarum-bridge' ) ?></p>
					</td>
				</tr>
			</tbody>
		</table>

		<p class="submit">
			<input type="submit" name="submit" id="submit" class="button button-primary" value="Save Changes">
		</p>

	</form>
</div>
