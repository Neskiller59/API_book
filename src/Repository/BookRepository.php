<?php

namespace App\Repository;

use App\Entity\Book;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Book>
 */
class BookRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Book::class);
    }

    /**
     * Retourne les livres avec pagination
     *
     * @param int $page
     * @param int $limit
     * @return Book[]
     */
    public function findAllWithPagination(int $page, int $limit): array
    {
        $qb = $this->createQueryBuilder('b')
            ->setFirstResult(($page - 1) * $limit)
            ->setMaxResults($limit)
            ->orderBy('b.id', 'ASC');

        $query = $qb->getQuery();
        $query->setFetchMode(Book::class, 'author', \Doctrine\ORM\Mapping\ClassMetadata::FETCH_EAGER);

        return $query->getResult();
    }

    /**
     * Retourne un Book par ID
     */
    public function findBookById(int $id): ?Book
    {
        return $this->find($id);
    }
}
