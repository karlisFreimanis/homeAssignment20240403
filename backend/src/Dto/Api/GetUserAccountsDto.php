<?php

namespace App\Dto\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class GetUserAccountsDto
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $userId;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->userId = $request->get('userId') ?: null;
    }
}