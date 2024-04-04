<?php

namespace App\Tests\Service\Api;

use App\Entity\Account;
use App\Entity\Currency;
use App\Entity\Transaction;
use App\Entity\User;
use App\Exception\ApiValidationException;
use App\Repository\AccountRepository;
use App\Repository\TransactionRepository;
use App\Service\Api\GetAccountTransactionHistoryService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class GetAccountTransactionHistoryServiceTest extends TestCase
{
    private AccountRepository $accountRepository;
    private TransactionRepository $transactionRepository;

    public function setUp(): void
    {
        $this->accountRepository     = $this->createMock(AccountRepository::class);
        $this->transactionRepository = $this->createMock(TransactionRepository::class);
    }

    /**
     * @return void
     * @throws ApiValidationException
     */
    public function testHandleReturnsArrayOfAccounts(): void
    {
        $request = new Request([], ['userId' => 1]);

        $user = new User();
        $user->setId(1);

        $currency = new Currency();
        $currency->setId(1);

        $account1 = new Account();
        $account1->setUser($user);
        $account1->setCurrency($currency);
        $account1->setBalance('100.00');
        $account1->setName('Doe');
        $account1->setId(1);

        $account2 = new Account();
        $account2->setUser($user);
        $account2->setCurrency($currency);
        $account2->setBalance('200.00');
        $account2->setName('Doe2');
        $account2->setId(2);

        $transaction1 = new Transaction();
        $transaction1->setId(1);
        $transaction1->setFromAccount($account1);
        $transaction1->setToAccount($account2);
        $transaction1->setCreated(new \DateTime('2024-04-03 00:00:00'));
        $transaction1->setStatus(Transaction::STATUS_PROCESSED);
        $transaction1->setFromAmount('10.00');
        $transaction1->setToAmount('10.00');

        $this->accountRepository->expects($this->once())
            ->method('find')
            ->with(0)
            ->willReturn($account1);

        $this->transactionRepository->expects($this->once())
            ->method('getAccountTransactions')
            ->with($account1)
            ->willReturn([$transaction1]);
        $this->transactionRepository->expects($this->once())
            ->method('countAccountTransactions')
            ->with($account1)
            ->willReturn(1);

        $service = new GetAccountTransactionHistoryService($this->transactionRepository, $this->accountRepository);

        $result = $service->handle($request);

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals(
            [
                'data'  => [
                    [
                        'id' => 1,
                        'fromAccountId' => 1,
                        'toAccountId' => 2,
                        'status' => 1,
                        'fromAmount' => '10.00',
                        'toAmount' => '10.00',
                        'created' => '2024-04-03 00:00:00',
                        'processed' => null,
                    ]
                ],
                'total' => 1,
            ],
            $result,
        );
    }

    /**
     * @return void
     */
    public function testHandleThrowsExceptionForInvalidAccountId(): void
    {
        $request = new Request([], ['accountId' => 0]);

        $this->accountRepository->expects($this->once())
            ->method('find')
            ->with(0)
            ->willReturn(null);

        $service = new GetAccountTransactionHistoryService($this->transactionRepository, $this->accountRepository);
        try {
            $service->handle($request);
            // If no exception is thrown, fail the test
            $this->fail('Expected exception ApiValidationException was not thrown');
        } catch (ApiValidationException $exception) {
            // Assert that the exception message is correct
            $expectedMessage = "Account with id:0 don't exists!";
            $this->assertEquals($expectedMessage, $exception->getMessage());
        }
    }

}