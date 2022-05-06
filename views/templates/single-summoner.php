<?php

get_header();

if(have_posts()) : while(have_posts()) : the_post();

    require_once(SUMMONER_TRACKER_PATH . '/functions/class.summoner-helper.php');
    $summoner = new Summoner_Helper($post);

    echo '<div class="summoner-archive-item">';
    echo '<div class="summoner-header-wrapper">';
    echo '<img class = "summoner-icon" src="'.$summoner->summoner_icon().'"/>';
    echo '<div class="summoner-details-wrapper">';
    the_title('<h2 class="summoner-title">', '</h2>' );
    echo '<div class="summoner-level">Level ' . $summoner->summoner_level() . '</div>';
    echo '</div>';
    echo '</div>';
    echo '<div class="summoner-stats-wrapper">';
    echo '<div class="summoner-wins">Win Percentage: <span class = "win-rate" style = "color: ' . $summoner->win_rate_color() . ' ">' . $summoner->win_rate() . '%</span></div>';
    echo '<div class="summoner-champ">Main Champion: </div><img class = "champ-pic" src = "http://ddragon.leagueoflegends.com/cdn/12.8.1/img/champion/' . $summoner->favorite_champ_id() . '.png"/><div><span class = "champ">' . $summoner->favorite_champ() . '</span></div>';
    echo '</div>';
    $counter = 0;
    echo '<div class="summoner-champ-list">';
    foreach($summoner->favorite_champ_list() as $champ){
        echo '<div class = "champ-list-item"><div class="champ - '. $counter++ . ' ">' . ' </div><img class = "champ-pic" src = "http://ddragon.leagueoflegends.com/cdn/12.8.1/img/champion/' . $summoner->get_champ_id($champ) . '.png"/><div><span class = "champ">' . $champ . '</span></div></div>';
    }
    echo '</div>';
    $nonce = wp_create_nonce("summoner_update_nonce");
    $link = admin_url('admin-ajax.php?action=summoner_update_button&post_id=' . $post->ID . '&nonce=' . $nonce);
    echo '<a class="summoner_update_button" data-nonce="' . $nonce . '" data-post_id="' . $post->ID . '" href="' . $link . '">update</a>';
    echo '</div>';

endwhile; endif;

get_footer();

?>

