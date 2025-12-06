<?php

ini_set("display_errors", 0);

class CustomController extends Controller
{

    public function __construct($method, $param, $data)
    {
        parent::__construct($method, $param, $data);
    }

    private function getAllSettings(){
        $query = $this->db->prepare("SELECT * FROM settings WHERE id = 1");
        $query->execute(array());
        $settings = $query->fetch(PDO::FETCH_ASSOC);
        unset($settings["id"]);
        return $this->returnResponse($settings);
    }



    private function getSetting($setting){
        $query = $this->db->prepare("SELECT ".$setting." FROM settings WHERE id = 1");
        $exe = $query->execute(array());
        return $this->returnResponse($query->fetch(PDO::FETCH_ASSOC)[$setting]);
    }
    

    function loadMethods()
    {
        switch ($this->method) {
            case "GET":
                if (!empty($this->param)) {
                    $element = $this->getSetting($this->param);

                    if ($element) {
                        return $this->Response($this->returnResponse($element));
                    } 
                }

                return $this->Response($this->getAllSettings());
                break;
        }
    }
}
