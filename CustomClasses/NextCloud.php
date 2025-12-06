<?php

class CustomController extends Controller
{
    private $baseUrl = 'https://nextcloud.levendelaiscinema.fr/ocs/v1.php/cloud';

    public function __construct($method, $param, $data)
    {
        parent::__construct($method, $param, $data);
    }

    function doRequest($url, $method="GET", $data=[]){

        $url = ($url != "" ? $this->baseUrl."/".$url : $this->baseUrl)."?format=json";

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $_ENV["NC_ADMIN_USER"].":".$_ENV["NC_ADMIN_PASSWORD"]);

        $headers = [
            'OCS-APIRequest: true',
            'Accept: application/json'
        ];

        switch($method){
            case "POST":
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_POST, true);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            case "DELETE":
                $headers[] = 'Content-Type: application/json';
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
                break;
            default:
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
                break;
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);


        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        return array("code" => $httpCode, "response" => $response, "error" => $error);
    }

    function getAllGroups(){
        $data = $this->doRequest("groups");
        $ocsData = json_decode($data["response"], true);

        $groups = $ocsData["ocs"]["data"]["groups"];

        // Supprimer "admin"
        $key = array_search('admin', $groups);
        unset($groups[$key]);

        return $this->returnResponse($groups);
    }

    function getUserInfos(){
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

        

        /*if (!empty($data["url"])) {
            $shortCode = !empty($data["short"]) ? $data["short"] : generateUniqueShortCode($this->db);
            $query = $this->db->prepare("INSERT INTO `short_urls`(`short`, `url`, `keyword`, `expires`) VALUES (?, ?, ?, ?)");

            $exe = $query->execute(array(
                $shortCode,
                $data["url"],
                array_key_exists("keyword", $data) ? $data["keyword"] : null,
                array_key_exists("expires", $data) ? $data["expires"] : null,
            ));

            return $this->returnResponse($this->getElementById($shortCode));
        }*/

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
    }

    function loadMethods()
    {

        if(!$this->hasPermission(ADMINISTRATOR_ROLE)) return $this->Response($this->noAuthRes("MISSING_PERMISSIONS"));
        
        switch ($this->method) {
            case "GET":

                    switch($this->param){
                        case "groups":
                            return $this->Response($this->getAllGroups());
                            break;
                        case "users":
                            return $this->Response($this->getUserInfos());
                            break;
                        default:
                            return $this->Response($this->notFoundResponse());
                            break;
                    }

                break;

            case "DELETE":
                switch($this->param){
                        case "groups":
                            // * Représente la suppression d'un utilisateur à un groupe pas la suppression d'un groupe
                            //return $this->Response($this->create());
                            break;
                        case "users":
                            return $this->Response($this->deleteUser());
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
                break;*/

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
                break;
        }
    }
}
