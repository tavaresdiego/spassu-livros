<?php

namespace App\Repository;

use App\Entity\Assunto;
use App\Pagination\Page;
use App\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Assunto>
 */
class AssuntoRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Assunto::class);
    }

    /** @return list<Assunto> */
    public function findAllOrdered(string $direction = 'ASC'): array
    {
        return $this->createQueryBuilder('s')->orderBy('s.descricao', $direction)->getQuery()->getResult();
    }

    /**
     * @param string $filter parte da descrição ou código exato (quando numérico)
     *
     * @return Page<Assunto>
     */
    public function paginate(int $page, int $perPage = 20, string $filter = ''): Page
    {
        $qb = $this->createQueryBuilder('s')->orderBy('s.codAs', 'DESC');
        AdminFilter::apply($qb, $filter, 's.descricao', 's.codAs');

        return Paginator::paginate(
            $qb,
            $page,
            $perPage,
            fetchJoinCollection: false,
        );
    }

    /** @return array<int, int> quantidade de livros por codAs */
    public function countLivrosByAssunto(): array
    {
        $rows = $this->getEntityManager()->getConnection()
            ->fetchAllKeyValue('SELECT Assunto_codAs, COUNT(*) FROM Livro_Assunto GROUP BY Assunto_codAs');

        return array_map('intval', $rows);
    }
}
