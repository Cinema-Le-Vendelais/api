<?php

class CustomController extends Controller
{

    public function __construct($method, $param, $data)
    {
        parent::__construct($method, $param, $data);
    }

    function fetchMovies()
    {
        $url = "https://".$_ENV["MONNAIE_SERVICES_USER"].":".$_ENV["MONNAIE_SERVICES_PASSWORD"]."@movies.monnaie-services.com/FR/prog/v0/?version=0";
        $json = json_decode(file_get_contents($url), true)["sites"][0]["events"];

        $data = array_map(function ($event) {
            if(array_key_exists("release_date", $event)) $event["release_date"] = DateTime::createFromFormat("Ymd", $event["release_date"])->format("Y-m-d");
            unset($event["trailer_url"]);
            foreach ($event["sessions"] as &$session) {
                $dateTime = DateTime::createFromFormat("YmdHi", $session["date"]);
                if ($dateTime) {
                    $session["date"] = $dateTime->format("Y-m-d H:i");
                }
            }
            return $event;
        }, $json);
        
        return $data;
    }

    function loadMovies(){
        $json = null;
        try{

            $redis = new Redis();
            $redis->connect($_ENV["REDIS_HOST"], $_ENV["REDIS_PORT"], 1);
            $redis->auth($_ENV["REDIS_PASSWORD"]);

            if ($redis->ping() == '+PONG') {
                if ($redis->exists("levendel-api-movies")) {
                    $json = json_decode($redis->get('levendel-api-movies'), true);
                } else {
                    $json = $this->fetchMovies();
                    $redis->set('levendel-api-movies', json_encode($json));
                    $redis->expire("levendel-api-movies", 60*11);
                }
            }
            else{
                $json = $this->fetchMovies();
            }
        }
        catch (Exception $e) {
            $json = $this->fetchMovies();
        }

        return $this->returnResponse($json);
    }


    function loadMethods()
    {
        switch ($this->method) {
            case "GET":
                return $this->Response($this->loadMovies());
                break;
        }
    }
}
