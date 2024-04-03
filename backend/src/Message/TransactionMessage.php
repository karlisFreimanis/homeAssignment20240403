<?php

namespace App\Message;

class TransactionMessage
{
    private int $transactionId;

    /**
     * @param int $transactionId
     */
    public function __construct(int $transactionId)
    {
        $this->transactionId = $transactionId;
    }

    /**
     * @return int
     */
    public function getTransactionId(): int
    {
        return $this->transactionId;
    }
}
