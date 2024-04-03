<?php

namespace App\Service\Api;

use App\Entity\Transaction;
use App\Exception\ApiValidationException;
use App\Message\TransactionMessage;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Service\CurrencyExchangeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Messenger\MessageBusInterface;

readonly class TransferFondsApiService
{
    /**
     * @param AccountRepository       $accountRepository
     * @param EntityManagerInterface  $entityManager
     * @param CurrencyExchangeService $currencyExchangeService
     * @param MessageBusInterface     $messageBus
     * @param TransactionRepository   $transactionRepository
     */
    public function __construct(
        public AccountRepository       $accountRepository,
        public EntityManagerInterface  $entityManager,
        public CurrencyExchangeService $currencyExchangeService,
        public MessageBusInterface     $messageBus,
        public TransactionRepository   $transactionRepository,
    ) {
    }

    /**
     * @param int|null   $fromAccountId
     * @param int|null   $toAccountId
     * @param float|null $amount
     * @return array
     * @throws ApiValidationException
     */
    public function fire(?int $fromAccountId, ?int $toAccountId, ?float $amount): array
    {
        if ($fromAccountId === $toAccountId) {
            throw new ApiValidationException('Transaction allowed only between different accounts');
        }
        $fromAccount = $this->accountRepository->find($fromAccountId);
        if (empty($fromAccount)) {
            throw new ApiValidationException('Account with id: ' . $fromAccountId . " doesn't exist");
        }

        if ($fromAccount->getBalance() < $amount) {
            throw new ApiValidationException('Balance to low ' . $fromAccount->getBalance() . ' required ' . $amount);
        }

        $toAccount = $this->accountRepository->find($toAccountId);
        if (empty($toAccount)) {
            throw new ApiValidationException('Account with id: ' . $toAccountId . " doesn't exist");
        }

        $newTransaction = new Transaction();
        $newTransaction->setFromAmount($amount);
        $newTransaction->setFromAccount($fromAccount);
        $newTransaction->setToAccount($toAccount);
        $newTransaction->setCreated(new \DateTime());
        $newTransaction->setStatus(Transaction::STATUS_PENDING);
        $this->entityManager->persist($newTransaction);
        $this->entityManager->flush();

        $this->messageBus->dispatch(new TransactionMessage($newTransaction->getId()));
        return ['id' => $newTransaction->getId()];
    }

    /**
     * @param int $transactionId
     * @return void
     * @throws \Exception
     */
    public function processTransaction(int $transactionId): void
    {
        $transaction = $this->transactionRepository->find($transactionId);


        try {
            if ($transaction->getFromAccount()->getBalance() < $transaction->getFromAmount()) {
                throw new \Exception(
                    'Balance to low ' . $transaction->getFromAccount()->getBalance(
                    ) . ' required ' . $transaction->getFromAmount(),
                );
            }
            $this->entityManager->beginTransaction();
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
            $this->entityManager->persist($fromAccount);
            $this->entityManager->persist($toAccount);
            $this->entityManager->persist($transaction);

            $this->entityManager->flush();
            $this->entityManager->commit();
        } catch (\Throwable $exception) {
            $this->entityManager->rollback();
            throw new \Exception('Unexpected error in processTransaction:' . $exception->getMessage());
        }
    }

    /**
     * @param $transactionId
     * @return void
     */
    public function failTransaction($transactionId): void
    {
        $transaction = $this->transactionRepository->find($transactionId);
        if($transaction->getStatus() === Transaction::STATUS_PROCESSED) {
            return;
        }
        $transaction->setProcessed(new \DateTime());
        $transaction->setStatus(Transaction::STATUS_DECLINED);
        $this->entityManager->persist($transaction);
        $this->entityManager->flush();
    }

    private function getToAmount(Transaction $transaction): float
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