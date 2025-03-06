<?php defined( 'ABSPATH' ) or die( 'No script kiddies please!' ); ?>
                <tr valign="top">
                    <th scope="row"><label for="categories"><?php _e( 'Select Categories', 'bulk-post-update-date' ); ?></label></th>
                    <td>
                        <select multiple="multiple" id="categories" name="categories[]">
		                    <?php
		                    $args = array(
			                    'orderby' => 'name',
                                'hide_empty' => false
		                    );
		                    $categories = get_categories( $args );
                            
                            if (!empty($categories)) {
                                foreach ( $categories as $category ) { 
                            ?>
                                <option value="<?php echo intval($category->term_id); ?>">
				                    <?php echo esc_html($category->cat_name . ' (' . $category->category_count . ')'); ?>
                                </option>
		                    <?php 
                                }
                            }
                            ?>
                        </select>
                        <p class="description">
                            <?php _e( 'Will apply on all posts if no category is selected. Select multiple categories by holding Ctrl or Command key while selecting.', 'bulk-post-update-date' ); ?>
                        </p>
                    </td>
                </tr>

                <?php
//                Do not show tags option if there are more than 500 tags to save memory
                    $total_tags = wp_count_terms( 'post_tag' );
                    if ( $total_tags < 500 ) :
                ?>
                <tr valign="top">
                    <th scope="row"><label for="tags"><?php _e( 'Select Tags', 'bulk-post-update-date' ); ?></label></th>
                    <td>
                        <select multiple="multiple" id="tags" name="tags[]">
                            <?php
                            $args = array(
                                'orderby' => 'name',
                                'hide_empty' => 0
                            );
                            $tags = get_tags( $args );
                            
                            if (!empty($tags)) {
                                foreach ( $tags as $tag ) { 
                            ?>
                                <option value="<?php echo esc_attr($tag->slug); ?>">
                                    <?php echo esc_html($tag->name . ' (' . $tag->count . ')'); ?>
                                </option>
                            <?php 
                                }
                            }
                            ?>
                        </select>
                            <p class="description">
                            <?php _e( 'Will apply on all posts if no tag is selected. Select multiple tags by holding Ctrl or Command key while selecting.', 'bulk-post-update-date' ); ?>
                        </p>
                    </td>
                </tr>
                <?php endif; ?>