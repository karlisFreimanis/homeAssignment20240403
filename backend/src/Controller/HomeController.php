<?php

namespace App\Controller;

use App\Message\DummyMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{

    #[Route(
        path: '/',
        name: 'home',
        methods: ['GET'],
    )]
    public function index(MessageBusInterface $messageBus): JsonResponse
    {
        //dd(getenv('DATABASE_URL'));
        //dd(getenv('MESSENGER_TRANSPORT_DSN'));
        $messageBus->dispatch(new DummyMessage('Dummy text ' . time()));

        return $this->json(
            [
                'message' => 'Message has been dispatched successfully!',
            ],
        );
    }
}