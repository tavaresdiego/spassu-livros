<?php

namespace App\EventSubscriber;

use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\DriverException;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/** Handler dedicado a falhas de banco: página própria, sem expor a mensagem do driver. */
final class DatabaseExceptionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly Environment $twig,
        private readonly LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::EXCEPTION => 'onKernelException'];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = self::findDriverException($event->getThrowable());
        if (null === $exception) {
            return;
        }

        if ($exception instanceof ConnectionException) {
            $status = Response::HTTP_SERVICE_UNAVAILABLE;
            $message = 'Não foi possível conectar ao banco de dados. Tente novamente em instantes.';
        } else {
            $status = Response::HTTP_INTERNAL_SERVER_ERROR;
            $message = 'Erro ao acessar o banco de dados. A equipe já foi notificada.';
        }

        $this->logger->error('Falha de banco de dados: {message}', ['message' => $exception->getMessage(), 'exception' => $exception]);

        $event->setResponse(new Response(
            $this->twig->render('error/database.html.twig', ['message' => $message, 'status_code' => $status]),
            $status,
        ));
    }

    /** O Twig embrulha exceções lançadas durante a renderização; procura a causa na cadeia. */
    private static function findDriverException(\Throwable $e): ?DriverException
    {
        for (; null !== $e; $e = $e->getPrevious()) {
            if ($e instanceof DriverException) {
                return $e;
            }
        }

        return null;
    }
}
