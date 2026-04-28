<?php

namespace App\Services;

use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;
use PhpAmqpLib\Exception\AMQPTimeoutException;
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
        $this->mq_cancel_timeout = (int) config('queue.connections.rabbitmq.hosts.0.cancel_timeout');

        [$this->callback_queue, ,] = $this->channel->queue_declare(
            "",
            false,
            false,
            true,
            true
        );

        $this->channel->basic_consume(
            $this->callback_queue,
            '',
            false,
            true,
            false,
            false,
            [$this, 'onResponse']
        );
    }

    public function __destruct()
    {
        $this->channel->close();
        $this->connection->close();
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
            'reply_to' => $this->callback_queue,
            'expiration'     =>  (string) (1000 * $this->mq_cancel_timeout) // TTL in milliseconds (e.g., 30 seconds)
        ]);

        $this->channel->basic_publish($msg, '', $queueName);

        // Wait until the response arrives
        $startTime = time();
        while (!$this->response) {
            try {
                $this->channel->wait(null, false, $this->mq_cancel_timeout);
            } catch (AMQPTimeoutException $e) {
                throw new Exception("RPC call timed out after {$this->mq_cancel_timeout} seconds");
            }
        }

        return json_decode($this->response, true);
    }
}