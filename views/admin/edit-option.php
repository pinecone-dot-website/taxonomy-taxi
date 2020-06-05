<option class="level-<?php echo esc_attr($depth); ?>" value="<?php echo esc_attr($term->slug); ?>" <?php selected($term->slug, $selected); ?>>
    <?php echo esc_html($display_name); ?>
</option>