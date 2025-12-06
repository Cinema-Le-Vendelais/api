<?php

class CustomController extends Controller
{

    public function __construct($method, $param, $data)
    {
        parent::__construct($method, $param, $data);
    }

    function getData(){
        $progressStmt = $this->db->query("SELECT * FROM email_queue WHERE sender = \"newsletter\"");
        $progress = $progressStmt->fetchAll(PDO::FETCH_ASSOC);

        // Filtrer les emails par statut

        $totalEmails = count($progress);

        $sentEmails = count(array_filter($progress, function ($email) {
            return $email['status'] === 'sent';
        }));

        $pendingEmails = count(array_filter($progress, function ($email) {
            return $email['status'] === 'pending';
        }));

        $failedEmails = count(array_filter($progress, function ($email) {
            return $email['status'] === 'failed';
        }));

        $stmt = $this->db->query("SELECT sent_at FROM email_queue WHERE status = 'sent' AND sender = \"newsletter\" ORDER BY id DESC LIMIT 1");
        $lastSentEmail = $stmt->fetch(PDO::FETCH_ASSOC);

        // Calculer le prochain envoi à Xh42
        $now = new DateTime();
        $nextSendTime = clone $now;
        
        // Définir l'heure du prochain envoi à Xh42
        if ($now->format('i') < 42) {
            // Avant 42 minutes dans l'heure en cours
            $nextSendTime->setTime(intval($now->format('H')), 42);
        } else {
            // Après 42 minutes, le prochain envoi est à l'heure suivante
            $nextSendTime->modify('+1 hour');
            $nextSendTime->setTime(intval($nextSendTime->format('H')), 42);
        }

        // Formatage de la date et heure du prochain envoi
        $nextSendFormatted = $nextSendTime->format('Y-m-d H:i:s');
        $nextSendTimestamp = $nextSendTime->getTimestamp();

        $data = array(
            "totalEmails" => $totalEmails,
            "sentEmails" => $sentEmails,
            "pendingEmails" => $pendingEmails,
            "failedEmails" => $failedEmails,
            "nextSendTime" => $nextSendFormatted,
            "nextSendTimestamp" => $nextSendTimestamp,

            "lastSentDate" => !is_bool($lastSentEmail) ? $lastSentEmail["sent_at"] : null
        );

        return $this->returnResponse($data);
    }

    

    function loadMethods()
    {
        switch ($this->method) {
            case "GET":
                return $this->Response($this->getData());
                break;
        }
    }
}
