<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use Exception;

class RabbitRpcClient
{
    private $connection;
    private $channel;
    private $callback_queue;
    private $response;
    private $corr_id;
    private $mq_cancel_timeout;

    public function __construct()
    {
        $this->connection = new AMQPStreamConnection(
            config('queue.connections.rabbitmq.hosts.0.host'),
            config('queue.connections.rabbitmq.hosts.0.port'),
            config('queue.connections.rabbitmq.hosts.0.user'),
            config('queue.connections.rabbitmq.hosts.0.password')
        );
        $this->channel = $this->connection->channel();
        $this->mq_cancel_timeout = config('queue.connections.rabbitmq.hosts.0.cancel_timeout');

        // Declare a unique, temporary callback queue for THIS specific request
        list($this->callback_queue, , ) = $this->channel->queue_declare("", false, false, true, false);

        $this->channel->basic_consume(
            $this->callback_queue,
            '',
            false,
            true,
            false,
            false,
            array($this, 'onResponse')
        );
    }

    public function onResponse($req)
    {
        if ($req->get('correlation_id') == $this->corr_id) {
            $this->response = $req->body;
        }
    }

    public function call($queueName, $payload)
    {
        $this->response = null;
        $this->corr_id = uniqid();

        $msg = new AMQPMessage(json_encode($payload), [
            'correlation_id' => $this->corr_id,
            'reply_to' => $this->callback_queue
        ]);

        $this->channel->basic_publish($msg, '', $queueName);

        // Wait until the response arrives
        $startTime = time();
        while (!$this->response) {
            // Check if we have exceeded the allowed timeout
            if ((time() - $startTime) > $this->mq_cancel_timeout) {
                throw new Exception("Request timed out after {$this->mq_cancel_timeout} seconds.");
            }

            $this->channel->wait(null, false, 1);
        }

        return json_decode($this->response, true);
    }
}