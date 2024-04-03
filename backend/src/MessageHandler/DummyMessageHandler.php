<?php

namespace App\MessageHandler;

use App\Message\DummyMessage;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

#[AsMessageHandler]
final class DummyMessageHandler
{
    public function __invoke(DummyMessage $message)
    {
        echo $message->getMessage();
    }
}
