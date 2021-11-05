<?php

namespace SaaSFormation\Vendor\API;

use SaaSFormation\Vendor\CommandStore\CommandStoreInterface;
use SaaSFormation\Vendor\Queue\Message;
use SaaSFormation\Vendor\Queue\QueueInterface;
use SaaSFormation\Vendor\CommandBus\Command;

class AsyncCommandHandler
{
    private QueueInterface $queue;
    private CommandStoreInterface $commandStore;

    public function __construct(QueueInterface $queue, CommandStoreInterface $commandStore)
    {
        $this->queue = $queue;
        $this->commandStore = $commandStore;
    }

    public function handle(Command $command): void
    {
        $this->commandStore->create($command);
        $this->queue->publish(Message::create($command->toArray(), [
            'commandName' => $command->code()
        ]), 'pending_commands');
    }
}
