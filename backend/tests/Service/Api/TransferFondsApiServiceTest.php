<?php

namespace App\Tests\Service\Api;

use App\Entity\Account;
use App\Entity\Currency;
use App\Entity\Transaction;
use App\Entity\User;
use App\Exception\ApiValidationException;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Service\Api\TransferFondsApiService;
use App\Service\CurrencyExchangeService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Messenger\MessageBusInterface;

class TransferFondsApiServiceTest extends TestCase
{
    private AccountRepository       $accountRepository;
    private TransferFondsApiService $transferFondsApiService;
    private CurrencyExchangeService $currencyExchangeService;

    private TransactionRepository $transactionRepository;

    private Account $account;
    private Currency $currency;

    public function setUp(): void
    {
        $this->accountRepository       = $this->createMock(AccountRepository::class);
        $entityManager                 = $this->createMock(EntityManagerInterface::class);
        $this->currencyExchangeService       = $this->createMock(CurrencyExchangeService::class);
        $messageBus = $this->createMock(MessageBusInterface::class);
        $this->transactionRepository = $this->createMock(TransactionRepository::class);
        $this->transferFondsApiService = new TransferFondsApiService(
            $this->accountRepository,
            $entityManager,
            $this->currencyExchangeService,
            $messageBus,
            $this->transactionRepository,
        );

        $user = new User();
        $user->setId(1);

        $this->currency = new Currency();
        $this->currency->setId(1);
        $this->currency->setCode('USD');

        $this->account = new Account();
        $this->account->setUser($user);
        $this->account->setCurrency($this->currency);
        $this->account->setBalance('100.00');
        $this->account->setName('Doe');
        $this->account->setId(1);

        $user->addAccount($this->account);

    }

    /**
     * @return void
     */
    public function testHandleThrowsExceptionForSameAccount(): void
    {
        $request = new Request([], ['fromAccountId' => 1, 'toAccountId' => 1]);

        $service = $this->transferFondsApiService;
        try {
            $service->handle($request);
            $this->fail('Expected exception ApiValidationException was not thrown');
        } catch (ApiValidationException $exception) {
            $expectedMessage = 'Transaction allowed only between different accounts';
            $this->assertEquals($expectedMessage, $exception->getMessage());
        }
    }

    /**
     * @return void
     */
    public function testHandleThrowsExceptionForNoFromAccount(): void
    {
        $request = new Request([], ['fromAccountId' => 0]);

        $this->accountRepository->expects($this->once())
            ->method('find')
            ->with(0)
            ->willReturn(null);

        $service = $this->transferFondsApiService;
        try {
            $service->handle($request);
            $this->fail('Expected exception ApiValidationException was not thrown');
        } catch (ApiValidationException $exception) {
            $expectedMessage = "Account with id: 0 doesn't exist";
            $this->assertEquals($expectedMessage, $exception->getMessage());
        }
    }

    /**
     * @return void
     */
    public function testHandleThrowsExceptionForBalanceFromAccount(): void
    {
        $request = new Request([], ['fromAccountId' => 1, 'toAccountId' => 2, 'amount' => '101.00']);

        $this->accountRepository->expects($this->any())
            ->method('find')
            ->willReturn($this->account);

        $service = $this->transferFondsApiService;
        try {
            $service->handle($request);
            $this->fail('Expected exception ApiValidationException was not thrown');
        } catch (ApiValidationException $exception) {
            $expectedMessage = "Balance to low 100.00 required 101.00";
            $this->assertEquals($expectedMessage, $exception->getMessage());
        }


    }

    public function testHandleThrowsExceptionForNoToAccount(): void
    {
        $request = new Request([], ['fromAccountId' => 1, 'toAccountId' => 0, 'amount' => '10.00']);

        $findMap = [
            [1,null, null, $this->account],
            [0,null, null, null],
        ];

        $this->accountRepository->method('find')
            ->willReturnMap($findMap);

        $service = $this->transferFondsApiService;
        try {
            $service->handle($request);
            $this->fail('Expected exception ApiValidationException was not thrown');
        } catch (ApiValidationException $exception) {
            $expectedMessage = "Account with id: 0 doesn't exist";
            $this->assertEquals($expectedMessage, $exception->getMessage());
        }

    }

    public function testGetToAmountSameCurrency()
    {
        $fromAccount = $this->account;
        $toAccount = $this->account;

        $transaction = new Transaction();
        $transaction->setFromAccount($fromAccount);
        $transaction->setToAccount($toAccount);
        $transaction->setFromAmount(100);

        $this->assertEquals(100, $this->transferFondsApiService->getToAmount($transaction));
    }

    public function testGetToAmountDifferentCurrency()
    {
        $fromAccount = $this->account;

        $toCurrency = new Currency();
        $toCurrency->setCode('EUR');
        $toAccount = new Account();
        $toAccount->setCurrency($toCurrency);

        $this->currencyExchangeService->expects($this->once())
            ->method('getExchangeRate')
            ->with('USD', 'EUR')
            ->willReturn(0.85);

        $transaction = new Transaction();
        $transaction->setFromAccount($fromAccount);
        $transaction->setToAccount($toAccount);
        $transaction->setFromAmount(100);


        $this->assertEquals(85, $this->transferFondsApiService->getToAmount($transaction));
    }

    public function testPrepareTransaction()
    {
        $fromAccount = $this->account;

        $toCurrency = new Currency();
        $toCurrency->setCode('EUR');
        $toAccount = new Account();
        $toAccount->setCurrency($toCurrency);

        $transaction = new Transaction();
        $transaction->setFromAmount(50);
        $transaction->setFromAccount($fromAccount);
        $transaction->setToAccount($toAccount);
        $transaction->setStatus(Transaction::STATUS_PENDING);

        $this->currencyExchangeService->method('getExchangeRate')->willReturn(1.5); // Assuming same currency

        $preparedTransaction = $this->transferFondsApiService->prepareTransaction($transaction);

        $this->assertEquals(Transaction::STATUS_PROCESSED, $preparedTransaction->getStatus());

        $this->assertEquals(50, $preparedTransaction->getFromAccount()->getBalance());
        $this->assertEquals(75, $preparedTransaction->getToAccount()->getBalance());
    }

    public function testProcessNoBalanceTransaction()
    {
        $transaction = new Transaction();
        $transaction->setStatus(Transaction::STATUS_PENDING);
        $transaction->setFromAmount('10000');
        $transaction->setFromAccount($this->account);

        $this->transactionRepository->method('find')->willReturn($transaction);


        try {
            $this->transferFondsApiService->processTransaction(1);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $exception) {
            $expectedMessage = "Unexpected error in processTransaction:Balance to low 100.00 required 10000";
            $this->assertEquals($expectedMessage, $exception->getMessage());
        }
    }

    public function testProcessCompletedTransaction()
    {
        $transaction = new Transaction();
        $transaction->setStatus(Transaction::STATUS_PROCESSED);

        $this->transactionRepository->method('find')->willReturn($transaction);

        try {
            $this->transferFondsApiService->processTransaction(1);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $exception) {
            $expectedMessage = "Try to processed already processed transaction id 1";
            $this->assertEquals($expectedMessage, $exception->getMessage());
        }
    }

    public function testFailProcessedTransaction()
    {
        $transaction = new Transaction();
        $transaction->setStatus(Transaction::STATUS_PROCESSED);

        $this->transactionRepository->method('find')->willReturn($transaction);

        try {
            $this->transferFondsApiService->failTransaction(1);
            $this->fail('Expected exception was not thrown');
        } catch (\Exception $exception) {
            $expectedMessage = "Try to fail processed transaction id 1";
            $this->assertEquals($expectedMessage, $exception->getMessage());
        }
    }

}