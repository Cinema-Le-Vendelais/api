<?php

class CustomController extends Controller
{
    private $apiKey;

    public function __construct($method, $param, $data)
    {
        $this->apiKey = $_ENV["TMDB_API_KEY"];
        parent::__construct($method, $param, $data);
    }

    function searchMovie($name)
    {
        $data = @file_get_contents("https://api.themoviedb.org/3/search/movie?api_key=".$this->apiKey."&language=fr&query=".htmlentities($name));
        
        if ($data === false) {
            return $this->notFoundResponse();
        }
        
        $decoded = json_decode($data, true);
        return $this->returnResponse($decoded["results"]);
    }

    function movieData($id)
    {
        $data = @file_get_contents("https://api.themoviedb.org/3/movie/".$id."?api_key=".$this->apiKey."&language=fr");
        
        if ($data === false) {
            return $this->notFoundResponse();
        }
        
        $decoded = json_decode($data, true);
        return $this->returnResponse($decoded);
    }

    function loadMethods()
    {
        switch ($this->method) {
            case "GET":
                if (!empty($this->param)) {
                    switch($this->param) {
                        case "search":
                            return $this->Response($this->searchMovie($_GET["name"]));
                            break;
                        case "movie":
                            return $this->Response($this->movieData($_GET["id"]));
                            break;
                    }
                } else {
                    return $this->Response(500, []);
                }

                break;
        }
    }
}
