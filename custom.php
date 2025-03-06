<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' ); 

$args = array(
    'public'   => $type == 'web-story' ? false : true,
    '_builtin' => false,
    'object_type' => [
        sanitize_text_field($type)
    ]
); 

$output = 'objects';
$operator = 'and';
$taxonomies = get_taxonomies( $args, $output, $operator ); 

if ( $taxonomies ) {
    foreach ( $taxonomies as $taxonomy ) {
?>
        <tr valign="top">
            <th scope="row"><label for="tax_<?php echo esc_attr($taxonomy->name); ?>"><?php echo esc_html__( 'Select ' . $taxonomy->label, 'bulk-post-update-date' ); ?></label></th>
            <td>
                <select multiple="multiple" id="tax_<?php echo esc_attr($taxonomy->name); ?>" name="tax[<?php echo esc_attr($taxonomy->name); ?>][]">
                    <?php
                    $args = array(
                        'orderby' => 'name',
                        'taxonomy' => $taxonomy->name,
                        'hide_empty' => false
                    );
                    
                    $terms = get_terms( $args );
                    
                    if (!is_wp_error($terms) && !empty($terms)) {
                        foreach ( $terms as $term ) { 
                    ?>
                            <option value="<?php echo intval($term->term_id); ?>">
                                <?php echo esc_html($term->name . ' (' . $term->count . ')'); ?>
                            </option>
                    <?php 
                        }
                    } 
                    ?>
                </select>
                <p class="description">
                    <?php echo esc_html__( 'Will apply on all posts if no '. $taxonomy->labels->singular_name .' is selected. Select multiple '. $taxonomy->label .' by holding Ctrl or Command key while selecting.', 'bulk-post-update-date' ); ?>
                </p>
            </td>
        </tr>
<?php 
    } 

    if (count($taxonomies) > 1) {
?>
        <tr id="tax_relation_row" valign="top">
            <th scope="row"><label for="tax_relation"><?php _e( 'Taxonomies relation', 'bulk-post-update-date' ); ?></label></th>
            <td>
                <input type="radio" id="OR" name="tax_relation" value="OR" checked>
                <label for="OR"><?php _e( 'OR', 'bulk-post-update-date' ); ?></label>
                
                <input type="radio" id="AND" name="tax_relation" value="AND">
                <label for="AND"><?php _e( 'AND', 'bulk-post-update-date' ); ?></label>

                <p class="description">
                    <?php _e( 'OR will include any posts having any one parameter. AND will include only posts which have all taxonomies', 'bulk-post-update-date' ); ?>
                </p>
            </td>
        </tr>
<?php 
    }
}
?>