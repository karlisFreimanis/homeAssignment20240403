<?php

namespace App\Tests\Service\Api;

use App\Entity\Account;
use App\Entity\Currency;
use App\Entity\User;
use App\Exception\ApiValidationException;
use App\Repository\UserRepository;
use App\Service\Api\GetUserAccountsApiService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;

class GetUserAccountsApiServiceTest extends TestCase
{
    private UserRepository $userRepository;

    /**
     * @return void
     * @throws \PHPUnit\Framework\MockObject\Exception
     */
    public function setUp(): void
    {
        $this->userRepository = $this->createMock(UserRepository::class);

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

        $account = new Account();
        $account->setUser($user);
        $account->setCurrency($currency);
        $account->setBalance('100.00');
        $account->setName('Doe');
        $account->setId(1);

        $user->addAccount($account);

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($user);

        $service = new GetUserAccountsApiService($this->userRepository);

        $result = $service->handle($request);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals([
                                'userId' => 1,
                                'currencyId' => 1,
                                'balance' => '100.00',
                                'name' => 'Doe',
                            ], $result[0]);
    }

    /**
     * @return void
     */
    public function testHandleThrowsExceptionForInvalidUserId(): void
    {
        $request = new Request([], ['userId' => 0]);

        $this->userRepository->expects($this->once())
            ->method('find')
            ->with(0)
            ->willReturn(null);

        $service = new GetUserAccountsApiService($this->userRepository);
        try {
            $service->handle($request);
            // If no exception is thrown, fail the test
            $this->fail('Expected exception ApiValidationException was not thrown');
        } catch (ApiValidationException $exception) {
            // Assert that the exception message is correct
            $expectedMessage = "User with id:0 don't exists!";
            $this->assertEquals($expectedMessage, $exception->getMessage());
        }
    }
}
