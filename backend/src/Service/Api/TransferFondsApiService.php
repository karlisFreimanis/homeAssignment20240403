<?php

namespace App\Service\Api;

use App\Entity\Account;
use App\Entity\Transaction;
use App\Exception\ApiValidationException;
use App\Message\TransactionMessage;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Service\CurrencyExchangeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

class TransferFondsApiService
{
    /**
     * @param AccountRepository       $accountRepository
     * @param EntityManagerInterface  $entityManager
     * @param CurrencyExchangeService $currencyExchangeService
     * @param MessageBusInterface     $messageBus
     * @param TransactionRepository   $transactionRepository
     */
    public function __construct(
        readonly private AccountRepository       $accountRepository,
        readonly private EntityManagerInterface  $entityManager,
        readonly private CurrencyExchangeService $currencyExchangeService,
        readonly private MessageBusInterface     $messageBus,
        readonly private TransactionRepository   $transactionRepository,
    ) {
    }

    /**
     * @param Request $request
     * @return array
     * @throws ApiValidationException
     */
    public function handle(Request $request): array
    {
        $fromAccountId = $request->get('fromAccountId') ?? null;
        $toAccountId   = $request->get('toAccountId') ?? null;
        $amount        = $request->get('amount') ?? null;
        if ($fromAccountId === $toAccountId) {
            throw new ApiValidationException('Transaction allowed only between different accounts');
        }
        $fromAccount = $this->accountRepository->find($fromAccountId);
        if (empty($fromAccount)) {
            throw new ApiValidationException('Account with id: ' . $fromAccountId . " doesn't exist");
        }

        if (floatval($fromAccount->getBalance()) < floatval($amount)) {
            throw new ApiValidationException('Balance to low ' . $fromAccount->getBalance() . ' required ' . $amount);
        }

        $toAccount = $this->accountRepository->find($toAccountId);
        if (empty($toAccount)) {
            throw new ApiValidationException('Account with id: ' . $toAccountId . " doesn't exist");
        }

        $newTransaction = $this->addNewTransaction($amount, $fromAccount, $toAccount);

        $this->messageBus->dispatch(new TransactionMessage($newTransaction->getId()));
        return ['id' => $newTransaction->getId()];
    }

    private function addNewTransaction(string $amount, Account $fromAccount, Account $toAccount): Transaction
    {
        $newTransaction = new Transaction();
        $newTransaction->setFromAmount($amount);
        $newTransaction->setFromAccount($fromAccount);
        $newTransaction->setToAccount($toAccount);
        $newTransaction->setCreated(new \DateTime());
        $newTransaction->setStatus(Transaction::STATUS_PENDING);
        $this->entityManager->persist($newTransaction);
        $this->entityManager->flush();
        return $newTransaction;
    }

    /**
     * @param int $transactionId
     * @return void
     * @throws \Exception
     */
    public function processTransaction(int $transactionId): void
    {
        $transaction = $this->transactionRepository->find($transactionId);

        if($transaction->getStatus() === Transaction::STATUS_PROCESSED) {
            throw new \Exception('Try to processed already processed transaction id ' . $transactionId);
        }

        try {
            if ($transaction->getFromAccount()->getBalance() < $transaction->getFromAmount()) {
                throw new \Exception(
                    'Balance to low ' . $transaction->getFromAccount()->getBalance(
                    ) . ' required ' . $transaction->getFromAmount(),
                );
            }
            $this->entityManager->beginTransaction();
            $transaction = $this->prepareTransaction($transaction);
            $this->entityManager->persist($transaction->getFromAccount());
            $this->entityManager->persist($transaction->getToAccount());
            $this->entityManager->persist($transaction);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $exception) {
            $this->entityManager->rollback();
            throw new \Exception('Unexpected error in processTransaction:' . $exception->getMessage());
        }
    }

    public function prepareTransaction(Transaction $transaction): Transaction
    {
        $toAmount    = $this->getToAmount($transaction);
        $fromAccount = $transaction->getFromAccount();
        $toAccount   = $transaction->getToAccount();
        $transaction->setToAmount(strval($toAmount));
        $transaction->setProcessed(new \DateTime());
        $transaction->setStatus(Transaction::STATUS_PROCESSED);
        $fromNewBalance = floatval($fromAccount->getBalance()) - floatval($transaction->getFromAmount());
        $fromAccount->setBalance(strval($fromNewBalance));
        $toNewBalance = floatval($toAccount->getBalance()) + $toAmount;
        $toAccount->setBalance(strval($toNewBalance));
        return $transaction;
    }


    public function failTransaction($transactionId): void
    {
        $transaction = $this->transactionRepository->find($transactionId);
        if($transaction->getStatus() === Transaction::STATUS_PROCESSED) {
            throw new \Exception(
                'Try to fail processed transaction id ' . $transactionId,
            );
        }
        $transaction->setProcessed(new \DateTime());
        $transaction->setStatus(Transaction::STATUS_DECLINED);
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
    }

    public function getToAmount(Transaction $transaction): float
    {
        $fromCurrency = $transaction->getFromAccount()->getCurrency()->getCode();
        $toCurrency   = $transaction->getToAccount()->getCurrency()->getCode();
        if ($fromCurrency === $toCurrency) {
            return $transaction->getFromAmount();
        }
        $rate = $this->currencyExchangeService->getExchangeRate($fromCurrency, $toCurrency);

        return round($rate * $transaction->getFromAmount(), 8);
    }
}