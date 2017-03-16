<?php

namespace App;

use Ratchet\ConnectionInterface;
use Ratchet\MessageComponentInterface;

class Chat implements MessageComponentInterface
{
    private $clients;

    function __construct()
    {
        $this->clients = new \SplObjectStorage();
    }

    public function onOpen(ConnectionInterface $conn)
    {
        $this->clients->attach($conn);

        echo "New connection!\n";
    }

    public function onMessage(ConnectionInterface $from, $msg)
    {
        $data = json_decode(json_decode($msg, true), true);

        if ($this->saveMessage($data)) {
            echo 'Saved message to DB';
        } else {
            echo 'Failed to save message';
        }

        foreach ($this->clients as $client) {
            if ($client !== $from) {
                $client->send($data['content']);
            }
        }
    }

    public function onClose(ConnectionInterface $conn)
    {
        $this->clients->detach($conn);

        echo "A User disconnected\n";
    }

    public function onError(ConnectionInterface $conn, \Exception $e)
    {
        echo "An error has occurred: {$e->getMessage()}\n";

        $conn->close();
    }

    public function saveMessage($data)
    {
        
        $db = new \PDO("mysql:host=localhost;dbname=ratchetphp", "root", "root"); 
        
        $stmt = $db->prepare("
		INSERT INTO messages
		(conversationId, userId, content, date)
		VALUES
		(?, ?, ?, ?)
		");
        
        if ($stmt) {
            $stmt->bindParam(1, $data['id']);
            $stmt->bindParam(2, $data['userId']);
            $stmt->bindParam(3, $data['content']);
            $stmt->bindParam(4, date('Y-m-d H:i:s'));
            
            $stmt->execute();
            
            return true;
        }

        return false;
    }
}
