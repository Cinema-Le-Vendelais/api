<?php

ini_set("display_errors", 1);

class CustomController extends Controller
{

    public function __construct($method, $param, $data)
    {
        parent::__construct($method, $param, $data);
    }

    function getElementById($id){
        $query = $this->db->prepare("SELECT * FROM `benevoles` WHERE uuid = ?");
        $query->execute(array($id));
        $element = $query->fetch(PDO::FETCH_ASSOC);

        //echo ;

        if ($query->rowCount() == 0) {
            return false;
        }

        

        // TODO: Décoder les valeurs encodées si token valide

        return $element;
    }

}
