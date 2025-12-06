<?php

class CustomController extends Controller
{

    public function __construct($method, $param, $data)
    {
        parent::__construct($method, $param, $data);
    }

    private function getAllAds(){
        $path = "../../cloud/uploads/annonceurs";
        
        if (!is_dir($path)) {
            return $this->returnResponse(array());
        }
        
        try {
            $files = scandir($path);
            if ($files === false) {
                return $this->returnResponse(array());
            }
            $this->returnResponse(array_values(array_diff($files, array('..', '.'))));
        } catch(Exception $e) {
            return $this->returnResponse(array());
        }
    }


    function loadMethods(){

        switch($this->method)
        {
            case "GET":
                return $this->Response($this->getAllAds());
                break;
        }
    }

}
