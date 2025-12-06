<?php

class CustomController extends Controller
{

    public function __construct($method, $param, $data)
    {
        parent::__construct($method, $param, $data);
    }

    private function listFiles($baseDir, $path)
    {
        $fullPath = realpath($baseDir . $path);

        // Vérifier que le chemin est valide et qu'il est dans le répertoire de base
        if ($fullPath === false || strpos($fullPath, $baseDir) !== 0 || !is_dir($fullPath)) {
            http_response_code(400);
            echo json_encode(["error" => "Invalid path"]);
            exit;
        }

        $files = [];
        $items = scandir($fullPath);

        foreach ($items as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $itemPath = $fullPath . DIRECTORY_SEPARATOR . $item;
            $files[] = [
                "name" => $item,
                "type" => is_dir($itemPath) ? "directory" : "file"
            ];
        }

        return [
            "currentPath" => $path,
            "files" => $files
        ];
    }

    private function getPath($path)
    {
        $baseDirectory = "/home/levendel/cloud/uploads";
        $response = $this->listFiles($baseDirectory, $path);

        return $this->returnResponse($response);
    }

    function loadMethods()
    {
        switch ($this->method) {
            case "GET":
                if ($this->hasPermission(ADMINISTRATOR_ROLE)) return $this->Response($this->getPath(isset($_GET['path']) ? $_GET['path'] : '/'));

                return $this->Response($this->noAuthRes("MISSING_PERMISSIONS"));
                break;
        }
    }
}