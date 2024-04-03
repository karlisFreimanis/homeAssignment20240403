<?php

namespace App\MessageHandler;

use App\Message\TransactionMessage;
use App\Service\Api\TransferFondsApiService;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

class TransactionMessageHandler
{
    /**
     * @param TransferFondsApiService $transferFondsApiService
     */
    public function __construct(
        public TransferFondsApiService $transferFondsApiService,
    ) {

    }

    /**
     * @param TransactionMessage $message
     * @return void
     * @throws \Exception
     */
    #[AsMessageHandler(fromTransport: 'transactions')]
    public function processPending(TransactionMessage $message) :void
    {
        $this->transferFondsApiService->processTransaction($message->getTransactionId());
    }

    /**
     * @param TransactionMessage $message
     * @return void
     */
    #[AsMessageHandler(fromTransport: 'failed_transactions')]
    public function processFailed(TransactionMessage $message) :void
    {
        $this->transferFondsApiService->failTransaction($message->getTransactionId());
    }
}
