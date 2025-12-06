<?php

class CustomController extends Controller
{
    function emptyDir($dir) {
        if (is_dir($dir)) {
            $scn = scandir($dir);
            foreach ($scn as $files) {
                if ($files !== '.') {
                    if ($files !== '..') {
                        if (!is_dir($dir . '/' . $files)) {
                            unlink($dir . '/' . $files);
                        } else {
                            emptyDir($dir . '/' . $files);
                            rmdir($dir . '/' . $files);
                        }
                    }
                }
            }
        }
    }

    function deleteElement($id)
    {

        $survey = $this->getElementById($id);

        if (!$survey) return $this->notFoundResponse();

        $query = $this->db->prepare("DELETE FROM surveys WHERE id = ?");
        $exe = $query->execute(array($id));

        $query = $this->db->prepare("DELETE FROM `surveys-replies` WHERE surveyId = ?");
        $exe = $query->execute(array($id));

        $dir = __DIR__."/../../../cloud/surveys/".$id;

        if(is_dir($dir)){
            $this->emptyDir($dir);
            rmdir($dir);
        }

        return $this->returnResponse($survey);
    }
}
