<?php

namespace App\Dto\Api;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Validator\Constraints as Assert;

class TransferFondsDto
{
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $fromAccountId;
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?int $toAccountId;
    #[Assert\NotBlank]
    #[Assert\Positive]
    private ?float $amount;

    public function __construct(Request $request)
    {
        $this->fromAccountId = $request->get('fromAccountId') ?: null;
        $this->toAccountId   = $request->get('toAccountId') ?: null;
        $this->amount        = $request->get('amount') ?: null;
    }
}
