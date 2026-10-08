<?php

namespace App\Tests\Persistence;

use App\Persistence\EntityWriter;
use App\Persistence\Exception\DuplicateEntityException;
use App\Persistence\Exception\EntityInUseException;
use Doctrine\DBAL\Driver\Exception as DriverExceptionInterface;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\ForeignKeyConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class EntityWriterTest extends TestCase
{
    private static function driverError(): DriverExceptionInterface
    {
        return new class('erro do driver') extends \Exception implements DriverExceptionInterface {
            public function getSQLState(): ?string
            {
                return '23000';
            }
        };
    }

    public function testRemoveTranslatesForeignKeyViolation(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('flush')->willThrowException(new ForeignKeyConstraintViolationException(self::driverError(), null));

        $this->expectException(EntityInUseException::class);
        (new EntityWriter($em))->remove(new \stdClass());
    }

    public function testSaveTranslatesUniqueViolation(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('flush')->willThrowException(new UniqueConstraintViolationException(self::driverError(), null));

        $this->expectException(DuplicateEntityException::class);
        (new EntityWriter($em))->save(new \stdClass());
    }

    public function testConnectionErrorsAreNotSwallowed(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $em->method('flush')->willThrowException(new ConnectionException(self::driverError(), null));

        $this->expectException(ConnectionException::class);
        (new EntityWriter($em))->save(new \stdClass());
    }

    public function testSavePersistsAndFlushes(): void
    {
        $entity = new \stdClass();
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())->method('persist')->with($entity);
        $em->expects(self::once())->method('flush');

        (new EntityWriter($em))->save($entity);
    }
}
