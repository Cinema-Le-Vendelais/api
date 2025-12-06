<?php

ini_set("display_errors", 1);

class CustomController extends Controller
{

    public function __construct($method, $param, $data)
    {
        parent::__construct($method, $param, $data);
    }

    function getCategories(){
        return $this->returnResponse(array(
            array(
                "id" => "jeunesse",
                "name" => "Jeunesse",
                "genres" => array("family")
            ),
             array(
                "id" => "animation",
                "name" => "Animation",
                "genres" => array("animation")
            ),
            array(
                "id" => "drame",
                "name" => "Drame",
                "genres" => array("drame")
            ),
            array(
                "id" => "comedie",
                "name" => "Comédie",
                "genres" => array("comedy")
            ),
            array(
                "id" => "thriller",
                "name" => "Thriller",
                "genres" => array("thriller")
            ),
            array(
                "id" => "documentaire",
                "name" => "Documentaire",
                "genres" => array("documentary")
            ),
            array(
                "id" => "action",
                "name" => "Action",
                "genres" => array("action")
            ),
            array(
                "id" => "policier",
                "name" => "Policier",
                "genres" => array("crime", "detective")
            )
        ));
    }

    function loadMethods()
    {
        switch ($this->method) {
            case "GET":
                return $this->Response($this->getCategories());
        }
    }
}
