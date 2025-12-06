<?php

//ini_set("display_errors", 1);
require __DIR__ . '/tools/Encrypter.php';

use League\OAuth2\Client\Provider\GenericProvider;
use League\OAuth2\Client\Token\AccessToken;

define("ADMINISTRATOR_ROLE", "Rédacteurs");
define("AFFICHES_ROLE", "Affiches");
define("BENEVOLE_ROLE", "Bénévoles");

class Controller
{
    public $param;
    public $method;
    public $db;
    public $user;
    public $crypt;
    public $sso;
    public $data;

    public function __construct($method, $param, $data)
    {
        $this->method = $method;
        $this->param = $param;
        $this->data = $data;
        $this->sso = new GenericProvider([
            'clientId'                => $_ENV["NEXTCLOUD_CLIENT_ID"],
            'clientSecret'            => $_ENV["NEXTCLOUD_CLIENT_SECRET"],
            'redirectUri'             => $_ENV["NEXTCLOUD_CALLBACK"],
            'urlAuthorize'            => 'https://nextcloud.levendelaiscinema.fr/index.php/apps/oauth2/authorize',
            'urlAccessToken'          => 'https://nextcloud.levendelaiscinema.fr/index.php/apps/oauth2/api/v1/token',
            'urlResourceOwnerDetails' => 'https://nextcloud.levendelaiscinema.fr/ocs/v2.php/cloud/user?format=json',
            'scopes'                  => 'read'
        ]);
        $this->crypt = new Encrypter($_ENV["DB_ENC_PASSWORD"], $_ENV["DB_ENC_SALT"]);

        try {
            $this->db = new PDO('mysql:host='.$_ENV["DB_HOST"].';dbname='.$_ENV["DB_NAME"].';charset=utf8', $_ENV["DB_USER"], $_ENV["DB_PASSWORD"]);
        } catch (PDOException $e) {
            echo 'Echec de la connexion : ' . $e->getMessage();
            exit;
        }

        $this->loadData();
    }

    function loadData()
    {
        $this->loadUser();
        $this->loadMethods();
    }

    function returnResponse($data, $code = 200, $status = "OK")
    {
        $json = array();
        $json["status"] = $status;
        $json["status_code"] = $code;
        $json["data"] = $data;

        return $json;
    }

    function Response($data)
    {
        switch ($data["status_code"]) {
            case "404":
                header('HTTP/1.0 404 Not Found');
                break;
        }
        echo json_encode($data,  JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }

    function getData()
    {
        return (array) json_decode(file_get_contents('php://input'), TRUE);
    }



    function loadUser()
    {
        $get = $this->getData();
        $token = array_key_exists("oauth_access_token", $_COOKIE) ? $_COOKIE["oauth_access_token"] : (array_key_exists("token", $get) ? $get["token"] : null);

        if (!$token) {
            $token = array_key_exists("token", $_GET) ? $_GET["token"] : null;
        }

        // Checker qu'on renseigne un token
        if (!$token) {

            return $this->user = null;
        }

        try {
            $resourceOwner = $this->sso->getResourceOwner(new AccessToken(['access_token' => $token]));
            $data = $resourceOwner->toArray()["ocs"];
        } catch (Exception $e) {
            $data = array();
        }

        // Checker que le token est valide
        if (!isset($data["data"]["id"])) {
            return $this->user = null;
        }

        $this->user = $data;
    }

    function hasPermission($permission)

    {
        if (!$this->user) return false;
        return in_array($permission, $this->user["data"]["groups"]);
    }

    function noAuthRes($txt)
    {
        return $this->returnResponse(null, 401, $txt);
    }

    function notFoundResponse()
    {
        return $this->returnResponse(null, 404, "ELEMENT_NOT_FOUND");
    }

    function getElementById($id){
        $query = $this->db->prepare("SELECT * FROM `{$this->data["table"]}` WHERE {$this->data["identifier"]} = ?");
        $query->execute(array($id));
        $element = $query->fetch(PDO::FETCH_ASSOC);

        if ($query->rowCount() == 0) {
            return false;
        }

        foreach($element as $key=>$option){
            foreach($this->data["elements"] as $k=>$v)
            {

                if($k == $key && array_key_exists("encrypted", $v) && $v["encrypted"] && $this->hasPermission(ADMINISTRATOR_ROLE))
                {
                    $element[$key] = $this->crypt->decrypt($element[$key]);
                }

                if($k == $key && array_key_exists("isJson", $v) && $v["isJson"])
                {
                    $element[$key] = json_decode($element[$key], true);
                }

                if($k == $key && array_key_exists("hide", $v) && $v["hide"])
                {
                    unset($element[$key]);
                }
            }
        }

        return $element;
    }

    function getAllElements()
    {
        $query = $this->db->prepare("SELECT * FROM `{$this->data["table"]}`");
        $query->execute(array());
        $elements = $query->fetchAll(PDO::FETCH_ASSOC);

        foreach($elements as $el_key=>$element)
        {
            foreach($element as $key=>$option){
                foreach($this->data["elements"] as $k=>$v)
                {
                    if($k == $key && array_key_exists("encrypted", $v) && $v["encrypted"] && $this->hasPermission(ADMINISTRATOR_ROLE))
                    {
                        $elements[$el_key][$key] = $this->crypt->decrypt($elements[$el_key][$key]);
                    }

                    if($k == $key && array_key_exists("isJson", $v) && $v["isJson"])
                    {
                        $elements[$el_key][$key] = json_decode($elements[$el_key][$key], true);
                    }

                    if($k == $key && array_key_exists("hide", $v) && $v["hide"])
                    {
                        unset($elements[$el_key][$key]);
                    }
                }
            }
        }

        if ($query->rowCount() == 0) {
            return $this->returnResponse([]);
        }

        return $this->returnResponse($elements);
    }

    function createElement()
    {
        $data = $this->getData();

        if(count($data) == 0)
        {
            return $this->returnResponse(null, 400, "MISSING_ARGS");
        }

        $allArgs = true;
        foreach($this->data["elements"] as $k=>$v)
        {
            if(array_key_exists("isJson", $v) && $v["isJson"])
            {
                $data[$k] = json_encode($data[$k]);
            }

            if(array_key_exists("encrypted", $v) && $v["encrypted"])
            {
                $data[$k] = $this->crypt->encrypt($data[$k]);
            }

            // Vérifier qu'on à les arguments && qu'ils sont requis
            if((!array_key_exists($k, $data) || empty($data[$k]) || !isset($data[$k])) && (!array_key_exists("canBeZero", $v) || !$v["canBeZero"]) && (!array_key_exists("canBeNull", $v) || !$v["canBeNull"]) && (!array_key_exists("required", $v) || $v["required"])) $allArgs = false;
        }

        if($allArgs)
        {
            function makeSQL($v)
            {
                return "`$v`";
            }

            function makeInterrogation($v){
                return "?";
            }

            $sqlCommand = "INSERT INTO `{$this->data["table"]}` (".join(", ", array_map("makeSQL", array_keys(array_intersect_key($data, $this->data["elements"])))).") VALUES (".join(", ", array_map("makeInterrogation", array_keys(array_intersect_key($data, $this->data["elements"])))).")";

            $query = $this->db->prepare($sqlCommand);

            $exe = $query->execute(array_values(array_intersect_key($data, $this->data["elements"])));

            return $this->returnResponse($this->getElementById($this->db->lastInsertId()));
        }

        return $this->returnResponse(null, 400, "MISSING_ARGS");
    }

    function editElement($id)
    {
        $element = $this->getElementById($id);

        if (!$element) {
            return $this->notFoundResponse();
        }

        $data = $this->getData();

        if (count($data) == 0) {
            return $this->returnResponse($element, 400, "MISSING_ARGS");
        }

        foreach($this->data["elements"] as $k=>$v)
        {
            if(array_key_exists("isJson", $v) && $v["isJson"] && array_key_exists($k, $data))
            {
                $data[$k] = json_encode($data[$k]);
            }

            if(array_key_exists("encrypted", $v) && $v["encrypted"])
            {
                $data[$k] = $this->crypt->encrypt($data[$k]);
            }
        }


        function makeSQL($v)
        {
            return "`$v`=?";
        }

        $sqlCommand = "UPDATE `{$this->data["table"]}` SET " . join(", ", array_map("makeSQL", array_keys(array_intersect_key($data, $this->data["elements"])))) . " WHERE {$this->data["identifier"]} = ?";

        $arr = array_values(array_intersect_key($data, $this->data["elements"]));
        $arr[] = $id;

        $query = $this->db->prepare($sqlCommand);

        $exe = $query->execute($arr);

        return $this->returnResponse($element);

        return $this->returnResponse($element, 400, "MISSING_ARGS");
    }

    function deleteElement($id)
    {
        $element = $this->getElementById($id);

        if (!$element) {
            return $this->notFoundResponse();
        }

        $query = $this->db->prepare("DELETE FROM `{$this->data["table"]}` WHERE {$this->data["identifier"]} = ?");

        $exe = $query->execute([$id]);

        return $this->returnResponse($element);
    }

    function loadMethods(){
        switch ($this->method) {
            case "GET":
                if (!empty($this->param)) {
                    $element = $this->getElementById($this->param);

                    if ($element) {
                        return $this->Response($this->returnResponse($element));
                    }

                    return $this->Response($this->notFoundResponse());
                } else {
                    return $this->Response($this->getAllElements());
                }
                break;

            case "DELETE":
                if ($this->user) {
                    return $this->Response($this->deleteElement($this->param));
                }

                return $this->Response($this->noAuthRes("MISSING_PERMISSIONS"));
                break;

            case "PUT":
                if ($this->user) {
                    return $this->Response($this->editElement($this->param));
                }

                return $this->Response($this->noAuthRes("MISSING_PERMISSIONS"));
                break;

            case "POST":
                if ($this->user) {
                    return $this->Response($this->createElement($this->param));
                }

                return $this->Response($this->noAuthRes("MISSING_PERMISSIONS"));

                break;
        }
    }
}



class MainController extends Controller
{
    function loadData()
    {
        $get = $this->getData();

        $token = array_key_exists("token", $_COOKIE) ? $_COOKIE["token"] : (array_key_exists("token", $get) ? $get["token"] : (array_key_exists("token", $_GET) ? $_GET["token"] : null));

        if (!$token) {

            $this->Response($this->noAuthRes("MISSING_TOKEN"));

            exit;
        }

        $resourceOwner = $this->sso->getResourceOwner(new AccessToken(['access_token' => $token]));
        $data = $resourceOwner->toArray()["ocs"];

        // Checker que le token est valide
        if (!isset($data["data"]["id"])) {
            $this->Response($this->noAuthRes("UNDEFINED_TOKEN"));
            exit;
        }

        if (!(in_array("Rédacteurs", $data["data"]["groups"]))) {
            $this->Response($this->noAuthRes("MISSING_PERMISSIONS"));

            exit;
        }

        $this->user = $data;
        $this->loadMethods();
    }
}
