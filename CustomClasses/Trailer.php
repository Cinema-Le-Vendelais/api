<?php

ini_set("display_errors", 1);

class CustomController extends Controller
{

    public function __construct($method, $param, $data)
    {
        parent::__construct($method, $param, $data);
    }

    function fetchFilmById($filmId){
        $film = array_values(array_filter(json_decode(file_get_contents("https://api.levendelaiscinema.fr/movies"), true)["data"],
            function ($movie) use ($filmId) {
                return $movie["id"] == $filmId;
            }
        ))[0] ?? null;

        return $film;
    }

    function getTrailerFromYouTube($movieName, $releaseYear) {
        $date = new DateTime($releaseYear);
        $year = $date->format("Y");

        $searchQuery = urlencode($movieName . " bande annonce vf " . $year);
        $url = "https://www.googleapis.com/youtube/v3/search?part=snippet&q=$searchQuery&type=video&key=".$_ENV["YOUTUBE_API_KEY"];
      
        $response = file_get_contents($url);
        $data = json_decode($response, true);
      
        if (!empty($data['items'])) {
            $videoId = $data['items'][0]['id']['videoId'];
            return $videoId;
        }
      
        return false;
    }

    function fetchTrailer($film){
        $release_date = array_key_exists("release_date", $film) ? $film["release_date"] : date("Y")."-01-01";
        $url = "https://api.themoviedb.org/3/search/movie?api_key=".$_ENV["TMDB_API_KEY"]."&query=".urlencode($film["title"])."&primary_release_year=".explode("-", $release_date)[0];
        $fetchFilms = json_decode(file_get_contents($url), true)["results"];
      
        if(count($fetchFilms) > 0)
        {
          $fetchFilmId = $fetchFilms[0]["id"];
          $trailers = json_decode(
            file_get_contents(
                "https://api.themoviedb.org/3/movie/".$fetchFilmId."/videos?api_key=".$_ENV["TMDB_API_KEY"]."&include_video_language=fr"),  
            true)["results"];
        
          $trailerKey = array_values(array_filter(
            $trailers, 
            function ($video) { 
                return str_contains(strtolower($video["name"]), "vf");
          }));
      
          if(empty($trailerKey)) return $this->getTrailerFromYouTube($film["title"], $release_date);
        
          if(count($trailerKey) == 0) return $trailers[0]["key"];
        
        
          return $trailerKey[0]["key"];
        }
        else{
          return $this->getTrailerFromYouTube($film["title"], $release_date);
        }
    }

    function loadTrailer($filmId){
        $currentFilm = $this->fetchFilmById($filmId);
        $trailer = null;

        try{
            $redis = new Redis();
            $redis->connect($_ENV["REDIS_HOST"], $_ENV["REDIS_PORT"], 1);
            $redis->auth($_ENV["REDIS_PASSWORD"]);
            
              if ($redis->ping() == '+PONG') {
                if ($redis->exists("levendel-trailer-".$filmId)) {
                    $trailer = json_decode($redis->get('levendel-trailer-'.$filmId), true);
                } else {
                  $trailer = $this->fetchTrailer($currentFilm);
                  
                  if($trailer){
                    $redis->set('levendel-trailer-'.$filmId, json_encode($trailer));
                    $redis->expire("levendel-trailer-".$filmId, 60*60*24*7);
                  }
                    
                }
            }
            else{
              $trailer = $this->fetchTrailer($currentFilm);
            }
          }
          catch (Exception $e) {
            $trailer = $this->fetchTrailer($currentFilm);
        }

        return $trailer;
    }

    

    function loadMethods()
    {
        switch ($this->method) {
            case "GET":
                if (!empty($this->param)) {
                    $element = $this->loadTrailer($this->param);

                    if ($element) {
                        return $this->Response($this->returnResponse($element));
                    } 
                }

                return $this->Response($this->notFoundResponse());
                break;
        }
    }
}
