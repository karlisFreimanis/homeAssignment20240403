<?php

namespace App\Service\Api;

use App\Entity\Transaction;
use App\Exception\ApiValidationException;
use App\Helper\DateTimeHelper;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use Symfony\Component\HttpFoundation\Request;

readonly class GetUserTransactionHistoryService
{
    /**
     * @param TransactionRepository $transactionRepository
     * @param AccountRepository     $accountRepository
     */
    public function __construct(
        private TransactionRepository $transactionRepository,
        private AccountRepository $accountRepository,
    )
    {

    }

    /**
     * @param Request $request
     * @return array
     * @throws ApiValidationException
     */
    public function handle (
        Request $request): array
    {
        $accountId = $request->get('accountId');
        $account = $this->accountRepository->find($accountId);
        if (empty($account)) {
            throw new ApiValidationException("Account with id:" . $accountId . " don't exists!");
        }

        return [
            'data' => $this->mapResult($this->transactionRepository->getAccountTransactions($account, $request->get('limit'), $request->get('offset'))),
            'total' => $this->transactionRepository->countAccountTransactions($account),
        ];
    }

    /**
     * @param Transaction[] $transactions
     * @return array
     */
    public function mapResult(array $transactions): array
    {
        $transactionsMapped = [];
        foreach ($transactions as $transaction) {
            $transactionsMapped[] = [
                'id' => $transaction->getId(),
                'fromAccountId' => $transaction->getFromAccount()->getId(),
                'toAccountId' => $transaction->getToAccount()->getId(),
                'status' => $transaction->getStatus(),
                'fromAmount' => $transaction->getFromAmount(),
                'toAmount' => $transaction->getToAmount(),
                'created' => $transaction->getCreated()->format(DateTimeHelper::DATE_TIME_FORMAT),
                'processed' => empty($transaction->getProcessed()) ? null : $transaction->getProcessed()->format(DateTimeHelper::DATE_TIME_FORMAT),
            ];
        }
        return $transactionsMapped;
    }

}