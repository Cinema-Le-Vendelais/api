<?php

class CustomController extends Controller
{
    public function __construct($method, $param, $data)
    {
        parent::__construct($method, $param, $data);
    }

    function createElement()
    {
        function generateUniqueShortCode($pdo, $length = 6) {
            do {
                $shortCode = substr(str_shuffle('abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'), 0, $length);
                
                $stmt = $pdo->prepare("SELECT short FROM short_urls WHERE short = ?");
                $stmt->execute([$shortCode]);
                $exists = $stmt->fetch();
            } while ($exists);
        
            return $shortCode;
        }

        $data = $this->getData();

        if (!empty($data["url"])) {
            $shortCode = !empty($data["short"]) ? $data["short"] : generateUniqueShortCode($this->db);
            $query = $this->db->prepare("INSERT INTO `short_urls`(`short`, `url`, `keyword`, `expires`) VALUES (?, ?, ?, ?)");

            $exe = $query->execute(array(
                $shortCode,
                $data["url"],
                array_key_exists("keyword", $data) ? $data["keyword"] : null,
                array_key_exists("expires", $data) ? $data["expires"] : null,
            ));

            return $this->returnResponse($this->getElementById($shortCode));
        }

        return $this->returnResponse(null, 400, "MISSING_ARGS");
    }

    function loadMethods()
    {
        switch ($this->method) {
            case "GET":
                if (!empty($this->param)) {
                    $link = $this->getElementById($this->param);
                    if ($link) {
                        return $this->Response($this->returnResponse($link));
                    }
                    return $this->Response($this->notFoundResponse());
                } else {
                    return $this->Response($this->getAllElements());
                }

                break;

            case "DELETE":
                if ($this->hasPermission(ADMINISTRATOR_ROLE) || (array_key_exists("passwd", $this->getData()) && $this->getData()["passwd"] == $_ENV["LINK_PASSWORD"]))  return $this->Response($this->deleteElement($this->param));
                return $this->Response($this->noAuthRes("MISSING_PERMISSIONS"));
                break;

            case "PUT":

                if ($this->hasPermission(ADMINISTRATOR_ROLE) || (array_key_exists("passwd", $this->getData()) && $this->getData()["passwd"] == $_ENV["LINK_PASSWORD"])) return $this->Response($this->editElement($this->param));
                return $this->Response($this->noAuthRes("MISSING_PERMISSIONS"));
                break;

            case "POST":
                if ($this->hasPermission(ADMINISTRATOR_ROLE) || (array_key_exists("passwd", $this->getData()) && $this->getData()["passwd"] == $_ENV["LINK_PASSWORD"])) return $this->Response($this->createElement());
                return $this->Response($this->noAuthRes("MISSING_PERMISSIONS"));
                break;
        }
    }
}
