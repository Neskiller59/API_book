<?php

namespace App\Repository;

use App\Entity\Author;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Author>
 */
class AuthorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Author::class);
    }

    /**
     * Retourne les auteurs avec pagination
     *
     * @param int $page  Numéro de page
     * @param int $limit Nombre d’éléments par page
     * @return Author[]
     */
    public function findAllWithPagination(int $page, int $limit): array
    {
        return $this->createQueryBuilder('a')
            ->setFirstResult(($page - 1) * $limit) // Décalage
            ->setMaxResults($limit)               // Limite
            ->getQuery()
            ->getResult();
    }
}
