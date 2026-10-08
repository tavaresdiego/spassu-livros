<?php

namespace App\Persistence\Exception;

/** O registro não pode ser excluído porque outro registro depende dele (violação de FK). */
final class EntityInUseException extends \RuntimeException
{
}
