<?php

class CustomController extends Controller
{

    public function __construct($method, $param, $data)
    {
        parent::__construct($method, $param, $data);
    }

    function deleteElement($id){

        $element = $this->getElementById($id);

        if (!$element) {
            return $this->notFoundResponse();
        }

        
        $sqlCommand = "UPDATE roles SET hided=? WHERE id = ?";
        $query = $this->db->prepare($sqlCommand);
        $exe = $query->execute(array(1, $id));

        return $this->returnResponse($element);
    }

}
