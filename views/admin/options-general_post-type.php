<?php foreach ($taxonomies as $tax) : ?>
	<label>
		<?php echo $tax->label; ?>
		<input type="checkbox" name="taxonomy_taxi[<?php echo esc_attr($post_type); ?>][]" value="<?php echo esc_attr($tax->name); ?>" <?php checked($tax->checked); ?> />
	</label>
<?php endforeach; ?>