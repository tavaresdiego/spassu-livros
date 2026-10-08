<?php

namespace App\Persistence;

use App\Persistence\Exception\DuplicateEntityException;
use App\Persistence\Exception\EntityInUseException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Grava e exclui entidades traduzindo violações de restrição em exceções de domínio.
 * Erros de conexão/driver seguem adiante para o DatabaseExceptionSubscriber.
 */
class EntityWriter
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function save(object $entity): void
    {
        try {
            $this->em->persist($entity);
            $this->em->flush();
        } catch (UniqueConstraintViolationException $e) {
            throw new DuplicateEntityException('Já existe um registro com esses dados.', previous: $e);
        }
    }

    public function remove(object $entity): void
    {
        try {
            $this->em->remove($entity);
            $this->em->flush();
        } catch (ForeignKeyConstraintViolationException $e) {
            throw new EntityInUseException('O registro está vinculado a outros registros.', previous: $e);
        }
    }

    /**
     * Exclui com DELETE direto (DQL). Diferente de remove(), o ORM não apaga antes as linhas
     * das tabelas de junção do lado inverso; assim a FK do banco impede excluir registro em uso.
     */
    public function removeIfUnreferenced(object $entity): void
    {
        try {
            $this->em->createQueryBuilder()
                ->delete($entity::class, 'e')
                ->where('e = :entity')
                ->setParameter('entity', $entity)
                ->getQuery()
                ->execute();
        } catch (ForeignKeyConstraintViolationException $e) {
            throw new EntityInUseException('O registro está vinculado a outros registros.', previous: $e);
        }
    }
}
