<?php

namespace App\Repository;

use Doctrine\ORM\QueryBuilder;

/** Filtro das listagens do admin: parte do texto (LIKE) ou, se o termo for numérico, também o código exato. */
final class AdminFilter
{
    public static function apply(QueryBuilder $qb, string $filter, string $textField, string $idField): void
    {
        $filter = trim($filter);
        if ('' === $filter) {
            return;
        }

        $condition = $qb->expr()->like($textField, ':adminFilter');
        $qb->setParameter('adminFilter', '%'.addcslashes($filter, '%_\\').'%');

        if (ctype_digit($filter)) {
            $condition = $qb->expr()->orX($condition, $qb->expr()->eq($idField, ':adminFilterId'));
            $qb->setParameter('adminFilterId', (int) $filter);
        }

        $qb->andWhere($condition);
    }
}
