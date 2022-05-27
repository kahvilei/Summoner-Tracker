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
                <option value="br1" <?php selected( $region, 'br1' ); ?>>Brazil</option>
                <option value="eun1" <?php selected( $region, 'eun1' ); ?>>EU North</option>
                <option value="euw1" <?php selected( $region, 'euw1' ); ?>>EU West</option>
                <option value="jp1" <?php selected( $region, 'jp1' ); ?>>Japan</option>
                <option value="kr" <?php selected( $region, 'kr' ); ?>>Korea</option>
                <option value="la1" <?php selected( $region, 'la1' ); ?>>Latin America 1</option>
                <option value="la2" <?php selected( $region, 'la2' ); ?>>Latin America 2</option>
                <option value="na1" <?php selected( $region, 'na1' ); ?>>North America</option>
                <option value="oc1" <?php selected( $region, 'oc1' ); ?>>Oceana</option>
                <option value="tr1" <?php selected( $region, 'tr1' ); ?>>Turkey</option>
                <option value="ru" <?php selected( $region, 'ru' ); ?>>Russia</option>
            </select>
        </td>
    </tr>               
</table>