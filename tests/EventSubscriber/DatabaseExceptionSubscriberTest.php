<?php

namespace App\Tests\EventSubscriber;

use App\EventSubscriber\DatabaseExceptionSubscriber;
use Doctrine\DBAL\Driver\Exception as DriverExceptionInterface;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\DriverException;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

class DatabaseExceptionSubscriberTest extends KernelTestCase
{
    private static function driverError(): DriverExceptionInterface
    {
        return new class('SQLSTATE[HY000] [2002] Connection refused') extends \Exception implements DriverExceptionInterface {
            public function getSQLState(): ?string
            {
                return 'HY000';
            }
        };
    }

    private function dispatch(\Throwable $e): ExceptionEvent
    {
        self::bootKernel();
        $event = new ExceptionEvent(self::$kernel, new Request(), HttpKernelInterface::MAIN_REQUEST, $e);
        static::getContainer()->get(DatabaseExceptionSubscriber::class)->onKernelException($event);

        return $event;
    }

    public function testConnectionErrorRenders503WithOwnMessage(): void
    {
        $response = $this->dispatch(new ConnectionException(self::driverError(), null))->getResponse();

        self::assertNotNull($response);
        self::assertSame(503, $response->getStatusCode());
        self::assertStringContainsString('Não foi possível conectar ao banco de dados', (string) $response->getContent());
        self::assertStringNotContainsString('Connection refused', (string) $response->getContent());
    }

    public function testOtherDriverErrorsRender500WithOwnMessage(): void
    {
        $response = $this->dispatch(new DriverException(self::driverError(), null))->getResponse();

        self::assertNotNull($response);
        self::assertSame(500, $response->getStatusCode());
        self::assertStringContainsString('Erro ao acessar o banco de dados', (string) $response->getContent());
    }

    public function testIgnoresNonDatabaseExceptions(): void
    {
        self::assertNull($this->dispatch(new \RuntimeException('outro'))->getResponse());
    }
}
