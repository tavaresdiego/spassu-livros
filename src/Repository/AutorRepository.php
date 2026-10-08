<?php

namespace App\Repository;

use App\Entity\Autor;
use App\Pagination\Page;
use App\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Autor>
 */
class AutorRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Autor::class);
    }

    /**
     * @param string $filter parte do nome ou código exato (quando numérico)
     *
     * @return Page<Autor>
     */
    public function paginate(int $page, int $perPage = 20, string $filter = ''): Page
    {
        $qb = $this->createQueryBuilder('a')->orderBy('a.codAu', 'DESC');
        AdminFilter::apply($qb, $filter, 'a.nome', 'a.codAu');

        return Paginator::paginate(
            $qb,
            $page,
            $perPage,
            fetchJoinCollection: false,
        );
    }
}
