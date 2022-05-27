<?php
require('/Users/katie/Local Sites/kadie-friends-stat-zone/app/public/vendor/autoload.php');

use RiotAPI\LeagueAPI\LeagueAPI;
use RiotAPI\LeagueAPI\Objects;
use RiotAPI\LeagueAPI\Objects\MatchDto;
use RiotAPI\LeagueAPI\Objects\MatchlistDto;
use RiotAPI\Base\Definitions\Region;
use RiotAPI\Base\BaseAPI;
use RiotAPI\Base\Objects\IApiObject;
use RiotAPI\Base\Objects\IApiObjectExtension;

/*
PHP Riot API 
Kevin Ohashi (http://kevinohashi.com)
http://github.com/kevinohashi/php-riot-api
The MIT License (MIT)
Copyright (c) 2013 Kevin Ohashi
Permission is hereby granted, free of charge, to any person obtaining a copy of
this software and associated documentation files (the "Software"), to deal in
the Software without restriction, including without limitation the rights to
use, copy, modify, merge, publish, distribute, sublicense, and/or sell copies of
the Software, and to permit persons to whom the Software is furnished to do so,
subject to the following conditions:
The above copyright notice and this permission notice shall be included in all
copies or substantial portions of the Software.
THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS
FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR
COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER
IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN
CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/


class Lol_Connector {
	
	const API_URL_PLATFORM_3 = "https://{platform}.api.riotgames.com/lol/platform/v3/";
	const API_URL_MATCH_5 = 'https://{region}.api.riotgames.com/lol/match/v5/';
	const API_URL_LEAGUE_4 = 'https://{platform}.api.riotgames.com/lol/league/v4/';
	const API_URL_SUMMONER_4 = 'https://{platform}.api.riotgames.com/lol/summoner/v4/';
    const API_URL_SPECTATOR_4 = 'https://{platform}.api.riotgames.com/lol/spectator/v4/';
    const API_URL_DD = 'http://ddragon.leagueoflegends.com/cdn/12.6.1/data/en_US/champion.json';


	// Rate limit for 10 minutes
	const LONG_LIMIT_INTERVAL = 600;
	const RATE_LIMIT_LONG = 500;

	// Rate limit for 10 seconds'
	const SHORT_LIMIT_INTERVAL = 10;
	const RATE_LIMIT_SHORT = 10;

	// Cache variables
	const CACHE_LIFETIME_MINUTES = 60;
	private $cache;

	private $PLATFORM;
    private $REGION;
	//variable to retrieve last response code
	private $responseCode; 


	private static $errorCodes = array(0   => 'NO_RESPONSE',
									   400 => 'BAD_REQUEST',
									   401 => 'UNAUTHORIZED',
									   404 => 'NOT_FOUND',
									   429 => 'RATE_LIMIT_EXCEEDED',
									   500 => 'SERVER_ERROR',
									   503 => 'UNAVAILABLE');




	// Whether or not you want returned queries to be JSON or decoded JSON.
	// honestly I think this should be a public variable initalized in the constructor, but the style before me seems definitely to use const's.
	// Remove this commit if you want. - Ahubers
	const DECODE_ENABLED = TRUE;

	public function __construct($platform, $cache = null)
	{
		$this->PLATFORM = $platform;

        $region = 'americas';
        if($platform == 'na1' || $platform == 'na1'){
            $region = 'americas';
        }else if($platform == 'kr'|| $platform == 'jp'){
            $region = 'asia';
        }else if($platform == 'euw1' || $platform == 'eun1'){
            $region = 'europe';
        }

        $this->REGION = $region;

		$this->shortLimitQueue = new SplQueue();
		$this->longLimitQueue = new SplQueue();

		$this->cache = $cache;

        $this->api_key = get_site_option('summoner_tracker_api_key');
	}


	//gets current game information for player
	public function getCurrentGame($id){
		$call = self::API_URL_SPECTATOR_4 . 'active-games/by-summoner/' . $id;
		return $this->request($call);
	}

	//New to my knowledge. Returns match details.
	//Now that timeline is a separated call, when includedTimeline is true, two calls are done at the same time.
	//Data is then processed to match the old structure, with timeline data included in the match data
	public function getMatch($matchId, $includeTimeline = true) {
		$call = self::API_URL_MATCH_5  . 'matches/' . $matchId;
		
		if(!$includeTimeline)
			return $this->request($call);
		
		else
			$timelineCall =  self::API_URL_MATCH_5  . 'timelines/by-match/' . $matchId;
			$data = $this->requestMultiple(array(
				"data"=>$call,
				"timeline"=>$timelineCall
				));
			$data["data"]["timeline"] = $data["timeline"];
			return $data["data"];
	}

    public function getParticipantID($match, $puuid){
        foreach($match['info']['participants'] as $participant){
            if($participant['puuid'] == $puuid){
                return $participant['participantId'] - 1;
            }
        }
        return 0;
    }
	
	//Returns timeline of a match
	public function getTimeline($matchId){
		$call =  self::API_URL_MATCH_5  . 'timelines/by-match/' . $matchId;
		
		return $this->request($call);
	}

	//Returns a user's matchList given their account id.
	public function getMatchList($accountId,$params=null) {
		if($params==null){
			$call = self::API_URL_MATCH_5  . 'matchlists/by-account/' . $accountId;
		}else{
			$call = self::API_URL_MATCH_5  . 'matchlists/by-account/' . $accountId .'?';
			
			//You can pass params either as an array or as string
			if(is_array($params))
				foreach($params as $key=>$param){
					//each param can also be an array, a list of champions, queues or seasons
					//refer to API doc to get details about params
					if(is_array($param))
						foreach($param as $p)
							$call .= $key . '=' . $p . '&';
							
					else
						$call .= $key . '=' . $param . '&';
				}

			else
				$call .= $params . '&';
		}
		
		return $this->request($call);
	}
	
	//Returns a user's recent matchList given their account id.
	public function getRecentMatchList($accountId) {
		$call = self::API_URL_MATCH_5  . 'matches/by-puuid/' . $accountId . '/ids?count=20';
		
		return $this->request($call);
	}
    //Returns a user's recent matchList given their account name, and the number of matches requested
    public function getRecentMatchListByName($name, $number) {
		$call = self::API_URL_MATCH_5  . 'matches/by-puuid/' . $this->getSummonerPuuId($name) . '/ids?count='. $number;
		
		return $this->request($call);
	}

    public function getRecentMatchListByNameAndTime($name, $time) {
        $call = self::API_URL_MATCH_5  . 'matches/by-puuid/' . $this->getSummonerPuuId($name) . '/ids?startTime='. $time . '&count=10';

        return $this->request($call);
    }

	//Returns the league of a given summoner.
	public function getLeague($id){
		$call = 'leagues/by-summoner/' . $id;

		//add API URL to the call
		$call = self::API_URL_LEAGUE_4 . $call;

		return $this->request($call);
	}
	
	//Returns the league position of a given summoner.
	//Similar to the old league /entry
	public function getLeaguePosition($id){
		$call = 'positions/by-summoner/' . $id;

		//add API URL to the call
		$call = self::API_URL_LEAGUE_4 . $call;

		return $this->request($call);
	}

    //Returns the champ name of a given champ ID.
	//Similar to the old league /entry
	public function getChampName($id){
		$call = self::API_URL_DD;

        $champs = $this->request($call)['data'];
        foreach ($champs as $champ){
            if(array_search($id,$champ)){
                return $champ['name'];
            }
        }
        return 'invalid';
	}

    //Returns the champ true ID of a given champ ID (key).
    //Similar to the old league /entry
    public function getChampID($id){
        $call = self::API_URL_DD;

        $champs = $this->request($call)['data'];
        foreach ($champs as $champ){
            if(array_search($id,$champ)){
                return $champ['id'];
            }
        }
        return 'invalid';
    }
	
	//returns a summoner's id
	public function getSummonerId($name) {
			$name = strtolower($name);
			$summoner = $this->getSummonerByName($name);
			if (self::DECODE_ENABLED) {
				return $summoner['id'];
			}
			else {
				$summoner = json_decode($summoner, true);
				return $summoner['id'];
			}
	}		
	
	//returns an account id
	public function getSummonerAccountId($name) {
			$name = strtolower($name);
			$summoner = $this->getSummonerByName($name);
			if (self::DECODE_ENABLED) {
				return $summoner['accountId'];
			}
			else {
				$summoner = json_decode($summoner, true);
				return $summoner['accountId'];
			}
	}	

    public function getSummonerPuuId($name) {
        $name = strtolower($name);
        $summoner = $this->getSummonerByName($name);
        if (self::DECODE_ENABLED) {
            return $summoner['puuid'];
        }
        else {
            $summoner = json_decode($summoner, true);
            return $summoner['puuid'];
        }
}
    
    //returns an account icon
	public function getSummonerAccountIcon($name) {
        $name = strtolower($name);
        $summoner = $this->getSummonerByName($name);
        if (self::DECODE_ENABLED) {
            return $summoner['profileIconId'];
        }
        else {
            $summoner = json_decode($summoner, true);
            return $summoner['profileIconId'];
        }
    }

    //returns an account LEVEL
	public function getSummonerLevel($name) {
        $name = strtolower($name);
        $summoner = $this->getSummonerByName($name);
        if (self::DECODE_ENABLED) {
            return $summoner['summonerLevel'];
        }
        else {
            $summoner = json_decode($summoner, true);
            return $summoner['summonerLevel'];
        }
    }

	//Returns summoner info given summoner id or account id.
	public function getSummoner($id,$accountId = false){
		$call = 'summoners/';
		if ($accountId) {
			$call .= 'by-account/';
		}
		$call .= $id;
		
		//add API URL to the call
		$call = self::API_URL_SUMMONER_4 . $call;

		return $this->request($call);
	}

	//Gets a summoner's info given their name, instead of id.
	public function getSummonerByName($name){
		$call = 'summoners/by-name/' . rawurlencode($name);
		
		//add API URL to the call
		$call = self::API_URL_SUMMONER_4 . $call;

		return $this->request($call);
	}

	//Gets a summoner's masteries.
	public function getMasteries($id){
		$call = 'masteries/by-summoner/' . $id;
		
		//add API URL to the call
		$call = self::API_URL_PLATFORM_3 . $call;

		return $this->request($call);
	}

	//Gets a summoner's runes.
	public function getRunes($id){
		$call = 'runes/by-summoner/' . $id;
		
		//add API URL to the call
		$call = self::API_URL_PLATFORM_3 . $call;

		return $this->request($call);
	}

	//Gets data of matches, given array of id.
	public function getMatches($ids, $includeTimeline = true){
		
		$calls=array();
		
		foreach($ids as $matchId){
			$call = self::API_URL_MATCH_5  . 'matches/' . $matchId;
			$calls["match-".$matchId] = $call;
			
			if($includeTimeline)
				$calls["timeline-".$matchId] = self::API_URL_MATCH_5  . 'timelines/by-match/' . $matchId;
		}
		
		if(!$includeTimeline)
			return $this->requestMultiple($calls);
		
		$results = array();
		
		$data = $this->requestMultiple($calls);
		
		foreach($data as $k=>$d){
			$e = explode("-", $k);
			
			//Check if it's match data
			if($e[0]=="match"){
				//Check if the timeline exists
				//Timeline is only stored by Riot for one year, too old games may not have it
				if(isset($data["timeline-".$e[1]]["frames"]))
					//add the matching timeline
					$d["timeline"] = $data["timeline-".$e[1]];
				array_push($results, $d);
			}
		}
		
		return $results;
	}

	private function updateLimitQueue($queue, $interval, $call_limit){
		
		while(!$queue->isEmpty()){
			
			/* Three possibilities here.
			1: There are timestamps outside the window of the interval,
			which means that the requests associated with them were long
			enough ago that they can be removed from the queue.
			2: There have been more calls within the previous interval
			of time than are allowed by the rate limit, in which case
			the program blocks to ensure the rate limit isn't broken.
			3: There are openings in window, more requests are allowed,
			and the program continues.*/

			$timeSinceOldest = time() - $queue->bottom();
			// I recently learned that the "bottom" of the
			// queue is the beginning of the queue. Go figure.

			// Remove timestamps from the queue if they're older than
			// the length of the interval
			if($timeSinceOldest > $interval){
					$queue->dequeue();
			}
			
			// Check to see whether the rate limit would be broken; if so,
			// block for the appropriate amount of time
			elseif($queue->count() >= $call_limit){
				if($timeSinceOldest < $interval){ //order of ops matters
					echo("sleeping for".($interval - $timeSinceOldest + 1)." seconds\n");
					sleep($interval - $timeSinceOldest);
				}
			}
			// Otherwise, pass through and let the program continue.
			else {
				break;
			}
		}

		// Add current timestamp to back of queue; this represents
		// the current request.
		$queue->enqueue(time());
	}

	private function request($call, $static = false) {
				//format the full URL
				
		$url = $this->format_url($call);
		//echo $url;
		//caching
		if($this->cache !== null && $this->cache->has($url)){
			$result = $this->cache->get($url);
		} else {
			// Check rate-limiting queues if this is not a static call.
			// if (!$static) {
			// 	$this->updateLimitQueue($this->longLimitQueue, self::LONG_LIMIT_INTERVAL, self::RATE_LIMIT_LONG);
			// 	$this->updateLimitQueue($this->shortLimitQueue, self::SHORT_LIMIT_INTERVAL, self::RATE_LIMIT_SHORT);
			// }

			//call the API and return the result
			$ch = curl_init($url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'X-Riot-Token: '. $this->api_key
				));			
			$result = curl_exec($ch);
			$this->responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);


			if($this->responseCode == 200) {
				if($this->cache !== null){
					$this->cache->put($url, $result, self::CACHE_LIFETIME_MINUTES * 60);
				}
			} else {
				throw new Exception(self::$errorCodes[$this->responseCode] . $url);
			}
		}
		if (self::DECODE_ENABLED) {
$result = json_decode($result, true);
		}
		return $result;
	}
	
	private function requestMultiple($calls) {
		
		$urls=array();
		$results=array();
		
		foreach($calls as $k=>$call){
			$url = $this->format_url($call);
			//Put cached data in resulsts and urls to call in urls
			if($this->cache !== null && $this->cache->has($url)){
				
				if (self::DECODE_ENABLED) {
					$results[$k] = json_decode($this->cache->get($url), true);
				}else{
					$results[$k] = $this->cache->get($url);
				}
				
			} else {
				$urls[$k] = $url;
			}
		}
		
		$callResult=$this->multiple_threads_request($urls);
		
		foreach($callResult as $k=>$result){
			if($this->cache !== null){
				$this->cache->put($urls[$k], $result, self::CACHE_LIFETIME_MINUTES * 60);
			}
			if (self::DECODE_ENABLED) {
				$results[$k] = json_decode($result, true);
			}else{
				$results[$k] = $result;
			}
		}
		
		return array_merge($results);
	}
	
	//creates a full URL you can query on the API
	private function format_url($call){
		return str_replace('{region}', $this->REGION,str_replace('{platform}', $this->PLATFORM, $call));
	}


	public function getLastResponseCode(){
		return $this->responseCode;
	}

	public function debug($message) {
		echo "<pre>";
		print_r($message);
		echo "</pre>";
	}
	
	
	public function setPlatform($platform) {
		$this->PLATFORM = $platform;
	}
	
	private function multiple_threads_request($nodes){
		$mh = curl_multi_init();
		$curl_array = array();
		foreach($nodes as $i => $url)
		{
			$curl_array[$i] = curl_init($url);
			curl_setopt($curl_array[$i], CURLOPT_RETURNTRANSFER, true);
			curl_setopt($curl_array[$i], CURLOPT_HTTPHEADER, array(
				'X-Riot-Token: '. $this->api_key
				));
			curl_multi_add_handle($mh, $curl_array[$i]);
		}
		$running = NULL;
		do {
			usleep(10000);
			curl_multi_exec($mh,$running);
		} while($running > 0);
	   
		$res = array();
		foreach($nodes as $i => $url)
		{
			$res[$i] = curl_multi_getcontent($curl_array[$i]);
		}
	   
		foreach($nodes as $i => $url){
			curl_multi_remove_handle($mh, $curl_array[$i]);
		}
		curl_multi_close($mh);       
		return $res;
}
}	
