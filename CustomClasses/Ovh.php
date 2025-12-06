<?php

use \Ovh\Api;

class CustomController extends Controller
{
    private $ovh;

    public function __construct($method, $param, $data)
    {

        $this->ovh = new Api($_ENV["OVH_APP_KEY"],
                $_ENV["OVH_APP_SECRET"],
                $_ENV["OVH_ENDPOINT"],
                $_ENV["OVH_CONSUMER_KEY"]);


        parent::__construct($method, $param, $data);

    }


    function getMailingLists(){
        $data = $this->ovh->get('/email/domain/levendelaiscinema.fr/mailingList');

        return $this->returnResponse($data);
    }

    function removeFromMailingList(){
        $providedData = $this->getData();

        if(!isset($providedData["mailing-list"]) || empty($providedData["mailing-list"] || !isset($providedData["email"]) || empty($providedData["email"])))
        {
            return $this->returnResponse(null, 400, "MISSING_ARGS");
        }

        $this->ovh->del('/email/domain/levendelaiscinema.fr/'.$providedData["mailing-list"].'/benevoles/subscriber/'.$providedData["email"]);
    }

    /*function getUserInfos(){
        $data = $this->doRequest("users/".$_GET["userId"]);

        if ($data["code"] === 200 || $data["code"] === 201) {
            $userInfos = json_decode($data["response"], true)["ocs"]["data"];
            return $this->returnResponse($userInfos);

        } else {
            echo "❌ Erreur HTTP ".$data["code"]." lors de l'obtention de l'utilisateur :\n";
            echo $data["response"];

            echo "Erreur cURL : " . $data["error"] . "\n";
        }
    }

    function createUser()
    {
        $sendData = $this->getData();

        if (!empty($sendData["userid"]) && !empty($sendData["password"]) && !empty($sendData["displayName"]) && !empty($sendData["email"])) {
            $data = $this->doRequest("users", "POST", $sendData);


            if ($data["code"] === 200 || $data["code"] === 201) {
                return $this->returnResponse(json_decode($data["response"], true)["ocs"]["data"]);
            } else {
                echo "❌ Erreur HTTP ".$data["code"]." lors de la création de l'utilisateur :\n";
                echo $data["response"];
                echo "Erreur cURL : " . $data["error"] . "\n";
            }
        }

        

        return $this->returnResponse(null, 400, "MISSING_ARGS");
    }

    function deleteUser(){
        $sendData = $this->getData();

        if (!empty($sendData["userid"])) {
            $data = $this->doRequest("users/".$sendData["userid"], "DELETE");


            if ($data["code"] === 200 || $data["code"] === 201) {
                return $this->returnResponse(json_decode($data["response"], true)["ocs"]["data"]);
            } else {
                echo "❌ Erreur HTTP ".$data["code"]." lors de la suppression de l'utilisateur :\n";
                echo $data["response"];
                echo "Erreur cURL : " . $data["error"] . "\n";
            }
        }


        return $this->returnResponse(null, 400, "MISSING_ARGS");
    }*/

    function loadMethods()
    {

        if(!$this->hasPermission(ADMINISTRATOR_ROLE)) return $this->Response($this->noAuthRes("MISSING_PERMISSIONS"));
        
        switch ($this->method) {
            case "GET":
                    switch($this->param){
                        case "mailing-lists":
                            return $this->Response($this->getMailingLists());
                            break;
                        default:
                            return $this->Response($this->notFoundResponse());
                            break;
                    }

                break;

            case "DELETE":
                switch($this->param){
                        case "mailing-lists":
                            return $this->Response($this->removeFromMailingList());
                            break;
                        default:
                            return $this->Response($this->notFoundResponse());
                            break;
                }
                break;

            /*case "PUT":
                //TODO: Modification de l'utilisateur sur NC si modification dans la database
                if ($this->hasPermission(ADMINISTRATOR_ROLE) || (array_key_exists("passwd", $this->getData()) && $this->getData()["passwd"] == "Sh0rtL&nksP@ssWd")) return $this->Response($this->editElement($this->param));
                return $this->Response($this->noAuthRes("MISSING_PERMISSIONS"));
                break;

            case "POST":
                switch($this->param){
                        case "groups":
                            // * Représente l'ajout d'un utilisateur à un groupe pas la création d'un groupe
                            //return $this->Response($this->create());
                            break;
                        case "users":
                            return $this->Response($this->createUser());
                            break;
                        default:
                            return $this->Response($this->notFoundResponse());
                            break;
                }
                break;*/
        }
    }
}
