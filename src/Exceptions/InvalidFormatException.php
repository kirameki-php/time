<?php declare(strict_types=1);

namespace Kirameki\Time\Exceptions;

use Kirameki\Core\Exceptions\InvalidArgumentException;
use Kirameki\Core\Json;
use Throwable;
use function array_merge;

class InvalidFormatException extends InvalidArgumentException
{
    /**
     * @param array{errors: array<int, string>, warnings: array<int, string>} $errors
     * @param iterable<string, mixed>|null $context
     * @param int $code
     * @param Throwable|null $previous
     */
    public function __construct(
        array $errors,
        ?iterable $context = null,
        int $code = 0,
        ?Throwable $previous = null,
    )
    {
        $allErrors = array_merge($errors['errors'], $errors['warnings']);
        $message = 'Invalid format: ' . Json::encode($allErrors);
        parent::__construct($message, $context, $code, $previous);
    }
}
