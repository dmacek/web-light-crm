<?php

declare(strict_types=1);

namespace App\Http;

final class ErrorHandler
{
    public static function register(): void
    {
        set_exception_handler(function (\Throwable $e): void {
            error_log((string) $e);

            $code = 'INTERNAL_ERROR';
            $statusCode = 500;
            $message = 'Internal server error';

            if ($e instanceof \InvalidArgumentException) {
                $code = 'INVALID_ARGUMENT';
                $statusCode = 422;
                $message = $e->getMessage();
            }

            if (getenv('APP_DEBUG') === '1' || getenv('APP_ENV') === 'development') {
                ApiResponse::error($e->getMessage(), $code, $statusCode, [
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => explode("\n", $e->getTraceAsString()),
                ]);
            } else {
                ApiResponse::error($message, $code, $statusCode);
            }
        });

        set_error_handler(function (int $severity, string $message, string $file, int $line): bool {
            if (!(error_reporting() & $severity)) {
                return false;
            }
            throw new \ErrorException($message, 0, $severity, $file, $line);
        });
    }
}
