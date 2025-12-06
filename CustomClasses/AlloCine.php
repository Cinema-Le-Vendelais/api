<?php

ini_set("display_errors", 0);

use PHPHtmlParser\Dom;

class CustomController extends Controller
{

    public function __construct($method, $param, $data)
    {
        parent::__construct($method, $param, $data);
    }

    function formatDate($date){
      $months = [
          'janvier' => '01',
          'février' => '02',
          'mars' => '03',
          'avril' => '04',
          'mai' => '05',
          'juin' => '06',
          'juillet' => '07',
          'août' => '08',
          'septembre' => '09',
          'octobre' => '10',
          'novembre' => '11',
          'décembre' => '12'
      ];

      try{
        list($day, $monthName, $year) = explode(' ', $date);

        $month = $months[strtolower($monthName)];

        return $month ? $year."-".$month."-".$day : null;
      }
      catch(Exception $e){
        return null;
      }
    }

    function searchMovie($query) {
      // AUTOCOMPLETE : https://www.allocine.fr/_/autocomplete/StarWars

      $searchUrl = 'https://www.allocine.fr/rechercher/?q=' . urlencode($query);

      $ch = curl_init();
      curl_setopt($ch, CURLOPT_URL, $searchUrl);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/123.0 Safari/537.36');
      curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
      $htmlContent = curl_exec($ch);
      curl_close($ch);
  
      if (!$htmlContent) {
          return null;
      }
  
      $results = array();

      $dom = new Dom;

      @$dom->loadStr($htmlContent);

      $contents = $dom->find('.movies-results ul li');
      
      foreach ($contents as $content)
      {
        $date = $content->find(".date")[0];
        $poster = $content->find(".thumbnail-img")[0];
        array_push($results, array(
          "title" => html_entity_decode(trim($content->find(".meta-title-link")[0]->text)),
          "poster_path" => trim($poster ? $poster->getAttribute("data-src") : null),
          "release_date" => $this->formatDate($date ? $date->text : null),
        ));
      }

      return $results;
    }
    
    function loadMethods()
    {
        switch ($this->method) {
            case "GET":
                if (!empty($this->param)) {
                    $element = $this->searchMovie($this->param);

                    if ($element) {
                        return $this->Response($this->returnResponse($element));
                    } 
                }

                return $this->Response($this->notFoundResponse());
                break;
        }
    }
}
