<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use PhpAmqpLib\Connection\AMQPStreamConnection;
use PhpAmqpLib\Message\AMQPMessage;

class RabbitRpcServer extends Command
{
    protected $signature = 'rabbitmq:rpc-server {queueName}';
    protected $description = 'Start a generic RabbitMQ RPC server to consume and respond to requests';

    public function handle()
    {
        $queueName = $this->argument('queueName');

        $connection = new AMQPStreamConnection(
            config('queue.connections.rabbitmq.hosts.0.host'),
            config('queue.connections.rabbitmq.hosts.0.port'),
            config('queue.connections.rabbitmq.hosts.0.user'),
            config('queue.connections.rabbitmq.hosts.0.password')
        );

        $channel = $connection->channel();
        $channel->queue_declare($queueName, false, true, false, false);

        $this->info(" [*] Awaiting RPC requests on '{$queueName}'");

        $callback = function ($req) {
            $payload = json_decode($req->body, true);
            $this->info(" [x] Received request.");

            // TODO: Route payload to your specific internal logic (e.g., fetching DB records)
            // For now, this is a generic response
            $responseData = ['status' => 'success', 'data' => 'Generic Response Processed'];

            $msg = new AMQPMessage(
                json_encode($responseData),
                ['correlation_id' => $req->get('correlation_id')]
            );

            // Respond to the specific reply_to queue
            $req->delivery_info['channel']->basic_publish(
                $msg,
                '',
                $req->get('reply_to')
            );
            $req->ack();
        };

        $channel->basic_qos(null, 1, null);
        $channel->basic_consume($queueName, '', false, false, false, false, $callback);

        while ($channel->is_consuming()) {
            $channel->wait();
        }

        $channel->close();
        $connection->close();
    }
}