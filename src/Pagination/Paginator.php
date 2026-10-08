<?php

namespace App\Pagination;

use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator as OrmPaginator;

/** Paginação por limit/offset. Com fetch join de coleções, o Paginator do ORM limita pelos IDs (sem cortar coleções). */
final class Paginator
{
    public static function paginate(QueryBuilder $qb, int $page, int $perPage, bool $fetchJoinCollection = true): Page
    {
        $query = $qb->getQuery();
        $paginator = new OrmPaginator($query, $fetchJoinCollection);

        $total = count($paginator);
        $pages = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $page), $pages);

        $query->setFirstResult(($page - 1) * $perPage)->setMaxResults($perPage);

        return new Page(iterator_to_array($paginator, false), $page, $perPage, $total, $pages);
    }
}
