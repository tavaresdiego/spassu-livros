<?php

namespace App\Repository;

use App\Entity\Assunto;
use App\Entity\Livro;
use App\Filter\LivroFilter;
use App\Pagination\Page;
use App\Pagination\Paginator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Todas as consultas de listagem trazem autores e assuntos por fetch join (sem N+1).
 *
 * @extends ServiceEntityRepository<Livro>
 */
class LivroRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Livro::class);
    }

    /**
     * Autor e assunto são filtrados por subconsulta, para não cortar as coleções exibidas.
     *
     * @return Page<Livro>
     */
    public function paginateWithRelations(int $page, int $perPage = 20, LivroFilter $filter = new LivroFilter()): Page
    {
        // admin: mais recentes primeiro
        $qb = $this->withRelations()->orderBy('l.codl', 'DESC');
        $like = fn (string $term) => '%'.addcslashes($term, '%_\\').'%';

        if ('' !== $filter->titulo) {
            $qb->andWhere('l.titulo LIKE :titulo')->setParameter('titulo', $like($filter->titulo));
        }
        if ('' !== $filter->editora) {
            $qb->andWhere('l.editora LIKE :editora')->setParameter('editora', $like($filter->editora));
        }
        if ('' !== $filter->autor) {
            $qb->andWhere('EXISTS (SELECT 1 FROM App\Entity\Livro fa JOIN fa.autores fau WHERE fa = l AND fau.nome LIKE :autor)')
                ->setParameter('autor', $like($filter->autor));
        }
        if (null !== $filter->assunto) {
            $qb->andWhere('EXISTS (SELECT 1 FROM App\Entity\Livro fs JOIN fs.assuntos fsa WHERE fs = l AND fsa.codAs = :assuntoFiltro)')
                ->setParameter('assuntoFiltro', $filter->assunto);
        }

        return Paginator::paginate($qb, $page, $perPage);
    }

    /** @return Page<Livro> */
    public function paginateByAssunto(Assunto $assunto, int $page, int $perPage = 12): Page
    {
        // alias separado para o filtro, para não cortar a coleção de assuntos exibida
        $qb = $this->withRelations()
            ->innerJoin('l.assuntos', 'filtro', 'WITH', 'filtro = :assunto')
            ->setParameter('assunto', $assunto);

        return Paginator::paginate($qb, $page, $perPage);
    }

    /**
     * Busca por título OU nome do autor. O alias "filtro" é separado do fetch join
     * para que o card mostre todos os autores, não só os que casaram com o termo.
     *
     * @return Page<Livro>
     */
    public function search(string $term, int $page, int $perPage = 12): Page
    {
        $like = '%'.addcslashes($term, '%_\\').'%';

        $qb = $this->withRelations()
            ->distinct()
            ->leftJoin('l.autores', 'filtro')
            ->where('l.titulo LIKE :term OR filtro.nome LIKE :term')
            ->setParameter('term', $like);

        return Paginator::paginate($qb, $page, $perPage);
    }

    public function findOneWithRelations(int $id): ?Livro
    {
        return $this->withRelations()
            ->where('l.codl = :id')
            ->setParameter('id', $id)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Os primeiros $perAssunto livros (por título) de cada assunto, em 2 consultas:
     * ROW_NUMBER() escolhe os IDs e um fetch join carrega os livros.
     *
     * @return array<int, list<Livro>> livros indexados pelo codAs
     */
    public function findFirstByAssunto(int $perAssunto): array
    {
        $rows = $this->getEntityManager()->getConnection()->fetchAllAssociative(
            'SELECT Assunto_codAs AS assunto, Livro_Codl AS livro FROM (
                SELECT la.Assunto_codAs, la.Livro_Codl,
                       ROW_NUMBER() OVER (PARTITION BY la.Assunto_codAs ORDER BY l.Titulo, l.Codl DESC) AS posicao
                FROM Livro_Assunto la
                INNER JOIN Livro l ON l.Codl = la.Livro_Codl
            ) ranking
            WHERE posicao <= :limite
            ORDER BY posicao',
            ['limite' => $perAssunto],
        );
        if ([] === $rows) {
            return [];
        }

        $livros = [];
        $ids = array_unique(array_map(fn (array $r) => (int) $r['livro'], $rows));
        foreach ($this->withRelations()->where('l.codl IN (:ids)')->setParameter('ids', $ids, ArrayParameterType::INTEGER)->getQuery()->getResult() as $livro) {
            $livros[$livro->getCodl()] = $livro;
        }

        $grouped = [];
        foreach ($rows as $row) {
            $grouped[(int) $row['assunto']][] = $livros[(int) $row['livro']];
        }

        return $grouped;
    }

    private function withRelations(): QueryBuilder
    {
        return $this->createQueryBuilder('l')
            ->addSelect('autor', 'assunto')
            ->leftJoin('l.autores', 'autor')
            ->leftJoin('l.assuntos', 'assunto')
            ->orderBy('l.titulo')
            ->addOrderBy('l.codl');
    }
}
