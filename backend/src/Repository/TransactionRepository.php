<?php

namespace App\Repository;

use App\Entity\Account;
use App\Entity\Transaction;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Transaction>
 *
 * @method Transaction|null find($id, $lockMode = null, $lockVersion = null)
 * @method Transaction|null findOneBy(array $criteria, array $orderBy = null)
 * @method Transaction[]    findAll()
 * @method Transaction[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TransactionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Transaction::class);
    }


    /**
     * @param Account $account
     * @param         $order
     * @param         $limit
     * @param         $offset
     * @return Transaction []
     */
    public function getAccountTransactions(
        Account $account,
                $limit = null,
                $offset = null,
                $order = ['id' => 'DESC'],
    ): array {
        $queryBuilder = $this->createQueryBuilder('transaction');
        $queryBuilder->where(
            $queryBuilder->expr()->orX(
                $queryBuilder->expr()->eq('transaction.fromAccount', ':account'),
                $queryBuilder->expr()->eq('transaction.toAccount', ':account'),
            ),
        )
            ->setParameter('account', $account);

        foreach ($order as $field => $direction) {
            $queryBuilder->addOrderBy('transaction.' . $field, $direction);
        }

        if ($limit !== null) {
            $queryBuilder->setMaxResults($limit);
        }

        if ($offset !== null) {
            $queryBuilder->setFirstResult($offset);
        }

        return $queryBuilder->getQuery()->getResult();
    }

    /**
     * @param Account $account
     * @return int
     */
    public function countAccountTransactions(Account $account): int
    {
        $queryBuilder = $this->createQueryBuilder('transaction');
        $queryBuilder->select('COUNT(transaction.id)');
        $queryBuilder->where($queryBuilder->expr()->orX(
            $queryBuilder->expr()->eq('transaction.fromAccount', ':account'),
            $queryBuilder->expr()->eq('transaction.toAccount', ':account')
        ))
            ->setParameter('account', $account);

        return (int) $queryBuilder->getQuery()->getSingleScalarResult();
    }
}
