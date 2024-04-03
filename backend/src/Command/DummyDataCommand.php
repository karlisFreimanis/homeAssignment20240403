<?php

namespace App\Command;

use App\Entity\Account;
use App\Entity\Currency;
use App\Entity\User;
use App\Repository\CurrencyRepository;
use App\Repository\UserRepository;
use App\Service\CurrencyExchangeService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\HttpClient\Exception\ClientException;

#[AsCommand(
    name: 'dummyData',
    description: 'Generate some fake data to work with',
)]
class DummyDataCommand extends Command
{
    /**
     * @param CurrencyExchangeService $currencyExchangeService
     * @param CurrencyRepository      $currencyRepository
     * @param EntityManagerInterface  $entityManager
     * @param UserRepository          $userRepository
     */
    public function __construct(
        readonly CurrencyExchangeService $currencyExchangeService,
        readonly CurrencyRepository     $currencyRepository,
        readonly EntityManagerInterface $entityManager,
        readonly UserRepository         $userRepository,
    ) {
        parent::__construct();
    }

    /**
     * @return void
     */
    protected function configure(): void
    {
        $this
            ->addArgument('userCount', InputArgument::REQUIRED, 'int count of users to be generated');
    }

    /**
     * @param InputInterface  $input
     * @param OutputInterface $output
     * @return int
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io        = new SymfonyStyle($input, $output);
        $userCount = (int)$input->getArgument('userCount');

        $this->importCurrencies($io);
        $this->generateUsers($io, $userCount);
        $this->entityManager->flush();
        $this->generateAccounts($io);
        $this->entityManager->flush();

        $io->success('You have a generated dummy data.');

        return Command::SUCCESS;
    }

    /**
     * @param SymfonyStyle $io
     * @return void
     */
    private function importCurrencies(SymfonyStyle $io): void
    {
        $currentCurrencies = $this->currencyRepository->findAll();
        if (!empty($currentCurrencies)) {
            $io->note('Currencies already imported');
            return;
        }

        try {
            $currencies = $this->currencyExchangeService->getCurrencyListFromEcb();
            foreach ($currencies as $code) {
                $newCurrency = new Currency();
                $newCurrency->setCode($code);
                $this->entityManager->persist($newCurrency);
            }
        } catch (ClientException $exception) {
            $io->note(
                'Failed to fetch currency data from the API. The API might be offline.'
                . ' Error: ' . $exception->getMessage(),
            );
        }
    }

    /**
     * @param SymfonyStyle $io
     * @param int          $limit
     * @return void
     */
    private function generateUsers(SymfonyStyle $io, int $limit): void
    {
        $currentUsers = $this->userRepository->findAll();
        if (!empty($currentUsers)) {
            $io->note('Users already generated');
            return;
        }

        foreach (range(1, $limit) as $row) {
            $newUser = new User();
            $newUser->setName($this->generateRandomString());
            $this->entityManager->persist($newUser);
        }
    }

    /**
     * @param SymfonyStyle $io
     * @return void
     */
    private function generateAccounts(SymfonyStyle $io): void
    {
        $users = $this->userRepository->findAll();
        if (empty($users)) {
            $io->note('Generate users first');
            return;
        }

        $currencies = $this->currencyRepository->findAll();
        if (empty($currencies)) {
            $io->note('Import currencies first');
            return;
        }

        foreach ($users as $user) {
            foreach (range(0, rand(0, 4)) as $row) {
                $newAccount = new Account();
                $newAccount->setName($this->generateRandomString());
                $newAccount->setBalance(strval(rand(0, 10000)));
                $newAccount->setUser($user);
                $newAccount->setCurrency($currencies[rand(0, (count($currencies) - 1))]);
                $this->entityManager->persist($newAccount);
            }
        }
    }

    /**
     * @param int $length
     * @return string
     */
    private function generateRandomString(int $length = 127): string
    {
        $characters       = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString     = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }
}

