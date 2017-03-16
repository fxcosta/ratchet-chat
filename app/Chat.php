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
        
        $db = new \mysqli('localhost', 'root', 'root', 'ratchetphp');
        
        $stmt = $db->prepare('
		INSERT INTO messages
		(
		conversationId,
		userId,
		content,
		date
		)
		VALUES
		(
		?,
		?,
		?,
		?
		)
		');
        
        if ($stmt) {
            $stmt->bind_param('iiss',
                $data['id'],
                $data['userId'],
                $data['content'],
                date('Y-m-d H:i:s')
            );
            
            $stmt->execute();
            
            $stmt->close();
            
            $db->close();
            
            return true;
        } else {
            return false;
        }
    }
}
