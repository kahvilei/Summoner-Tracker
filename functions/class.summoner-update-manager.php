<?php
if (!class_exists('Summoner_Update_Manager')) {
    class Summoner_Update_Manager
    {
        private $summoner = '';
        private $current_time = 0;
        private $connector = '';

        function __construct($post)
        {
            require_once(SUMMONER_TRACKER_PATH . 'functions/class.summoner-helper.php');
            require_once(SUMMONER_TRACKER_PATH . 'functions/class.riot-connector.php');
            $this->summoner = new Summoner_Helper($post);
            $this->connector = new Lol_Connector($this->summoner->summoner_region());
            $this->current_time = (int)current_time('timestamp');
        }

        public function run_summoner_update()
        {
            $this->level_update();
            $this->icon_update();
            $this->match_update();
            $this->stamp_update();
        }

        //updates only in case of timing, should update on load every minute
        public function conditional_summoner_update()
        {
            if ($this->summoner->time_since_last_edit() >= 3600) {
                $this->level_update();
                $this->icon_update();
                $this->match_update();
                $this->stamp_update();
            }
        }

        private function level_update()
        {
            update_post_meta($this->summoner->summoner_id(), 'summoner_level', $this->connector->getSummonerLevel($this->summoner->summoner_name()));
        }

        private function stamp_update()
        {
            update_post_meta($this->summoner->summoner_id(), 'last_query_stamp', (int)current_time('timestamp'));
        }

        private function icon_update()
        {
            $icon = 'https://raw.communitydragon.org/latest/game/assets/ux/summonericons/profileicon' . $this->connector->getSummonerAccountIcon($this->summoner->summoner_name()) . '.png';
            update_post_meta($this->summoner->summoner_id(), 'summoner_icon', $icon);
        }

        public function match_update(){
            global $wpdb;
            $summoner_name = $this->summoner->summoner_name();
            $connector = $this->connector;

            $most_recent_match_time = $this->summoner->last_game_time();

            if($most_recent_match_time == null){
                $matchlist = $connector->getRecentMatchListByNameAndTime($summoner_name, 1650417825);
            }else{
                $matchlist = $connector->getRecentMatchListByNameAndTime($summoner_name,  $most_recent_match_time);
            }
            foreach ($matchlist as $match) {
                $match_data = $connector->getMatch($match, false);

                $puuid = $connector->getSummonerPuuId($summoner_name);
                $participant_id = $connector->getParticipantId($match_data, $puuid);
                $champ_name = $connector->getChampName(($match_data['info']['participants'][$participant_id]['championId']));
                $champ = $connector->getChampID(($match_data['info']['participants'][$participant_id]['championId']));
                $timestamp = substr($match_data['info']['gameEndTimestamp'],0,-3);

                $table = 'wp_lol_match_table';
                $data = array(
                    'match_id' => $match,
                    'summoner_name' => $summoner_name,
                    'win-loss' => $match_data['info']['participants'][$participant_id]['win'],
                    'champion_id' => $champ,
                    'champion_name' => $champ_name,
                    'vision_score' => $match_data['info']['participants'][$participant_id]['visionScore'],
                    'match_type' => $match_data['info']['gameMode'],
                    'match_time' => $timestamp + 10,
                    'unique_id' => $puuid . $match

                );
                $format = array(
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%s',
                    '%d',
                    '%s',
                    '%d',
                    '%s'

                );
                $success = $wpdb->insert($table, $data, $format);
                if ($success) {
                    echo 'success';
                } else {
                    echo 'failure';
                }
            }
        }



    }

}