<?php

namespace App\Dto\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class GetUserTransactionHistoryDto
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $accountId;

    #[Assert\Positive]
    private ?int $offset;

    #[Assert\Positive]
    private ?int $limit;

    /**
     * @param Request $request
     */
    public function __construct(Request $request)
    {
        $this->accountId = $request->get('accountId') ?: null;
        $this->offset = $request->get('offset') ?: null;
        $this->limit  = $request->get('limit') ?: null;
    }
}

