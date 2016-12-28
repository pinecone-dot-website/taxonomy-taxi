<?php //dbug( $taxonomies ); ?>

<?php foreach( $taxonomies as $tax => $props ): ?>
	<?php //dbug( $props ); ?>

	<label>
	<?php echo $props->label; ?>
		<input type="checkbox" name="taxonomy_taxi[<?php echo esc_attr( $post_type ); ?>][]" value="<?php echo esc_attr($tax); ?>"/>
	</label>
<?php endforeach; ?>