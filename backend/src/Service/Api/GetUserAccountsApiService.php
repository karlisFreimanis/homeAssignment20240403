<?php

namespace App\Service\Api;

use App\Entity\Account;
use App\Exception\ApiValidationException;
use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\Request;

readonly class GetUserAccountsApiService
{
    /**
     * @param UserRepository $userRepository
     */
    public function __construct(
        public UserRepository $userRepository,
    ) {
    }

    /**
     * @param Request $request
     * @return array
     * @throws ApiValidationException
     */
    public function handle(Request $request): array
    {
        $userId = $request->get('userId');
        $user   = $this->userRepository->find($userId);

        if (empty($user)) {
            throw new ApiValidationException("User by id:" . $userId . " don't exists!");
        }

        $accounts = [];
        foreach ($user->getAccounts() as $account) {
            $accounts[] = $this->mapResults($account);
        }
        return $accounts;
    }

    /**
     * @param Account $account
     * @return array
     */
    private function mapResults(Account $account): array
    {
        return [
            'userId'     => $account->getUser()->getId(),
            'currencyId' => $account->getCurrency()->getId(),
            'balance'    => $account->getBalance(),
            'name'       => $account->getName(),
        ];
    }
}
