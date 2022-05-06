<?php 
    $meta = get_post_meta( $post->ID );
    $id_text = get_post_meta( $post->ID, 'id_text', true );
    $region = get_post_meta( $post->ID, 'region_select', true );
?>
<table class="form-table summoner-metabox"> 
<input type="hidden" name="summoner_nonce" value="<?php echo wp_create_nonce( "summoner_nonce" ); ?>">
    <tr>
        <th>
            <label for="id_text">Summoner ID</label>
        </th>
        <td>
            <input 
                type="text" 
                name="id_text" 
                id="id_text" 
                class="regular-text link-text"
                value="<?php echo ( isset( $id_text ) ) ? esc_html( $id_text ) : ''; ?>"
                required
            >
        </td>
    </tr>
    <tr>
        <th>
            <label for="mv_slider_link_url">Region Select</label>
        </th>
        <td>
            <select name="region_select" id="region_select" required>  
                <option value="na1" <?php selected( $region, 'na1' ); ?>>North America</option>  
                <option value="kr" <?php selected( $region, 'kr' ); ?>>Korea</option>               
            </select>
        </td>
    </tr>               
</table>