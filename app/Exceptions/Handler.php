<?php

namespace App\Exceptions;

use Illuminate\Database\QueryException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    private const ERROR_MAP = [
        'Too many connections' => ['db-busy', 503, 'Database Busy'],
        'Connection refused' => ['db-connection', 503, 'Database Connection Error'],
        'server has gone away' => ['db-connection', 503, 'Database Connection Error'],
    ];
    private const API_MESSAGES = [
        'db-busy' => 'Database is experiencing high traffic. Please try again.',
        'db-connection' => 'Database connection failed. Please try again later.',
        '404' => 'The requested resource was not found.',
        '403' => 'You do not have permission to access this resource.',
        '405' => 'The request method is not supported for this resource.',
        'rate-limit' => 'Too many requests. Please slow down.',
        '500' => 'An internal server error occurred.',
        '503' => 'Service is temporarily unavailable.',
        'general' => 'An unexpected error occurred.',
    ];
    protected $dontFlash = ['current_password', 'password', 'password_confirmation'];

    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            Log::channel('single')->error('Exception Caught', [
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);
        });
    }

    public function render($request, Throwable $exception)
    {
        if ($exception instanceof QueryException || $exception instanceof \PDOException) {
            $errorMessage = $exception->getMessage() . ' ' . $exception->getPrevious()?->getMessage();

            foreach (self::ERROR_MAP as $pattern => $config) {
                if (str_contains($errorMessage, $pattern)) {
                    return $this->renderError($request, ...$config);
                }
            }

            // fallback for DB issues
            return $this->renderError($request, 'general', 500, 'Database Error');
        }

        return match (true) {
            $exception instanceof NotFoundHttpException => $this->renderError($request, '404', 404, 'Page Not Found'),
            $exception instanceof MethodNotAllowedHttpException => $this->renderError($request, '405', 405, 'Method Not Allowed'),
            $exception instanceof TooManyRequestsHttpException => $this->renderError($request, 'rate-limit', 429, 'Too Many Requests'),
            $exception instanceof HttpException => $this->handleHttpException($request, $exception),
            default => parent::render($request, $exception)
        };
    }

    private function handleHttpException($request, HttpException $exception)
    {
        return match ($exception->getStatusCode()) {
            403 => $this->renderError($request, '403', 403, 'Access Forbidden'),
            500 => $this->renderError($request, '500', 500, 'Server Error'),
            503 => $this->renderError($request, '503', 503, 'Service Unavailable'),
            default => $this->renderError($request, 'general', $exception->getStatusCode(), 'Something went wrong'),
        };
    }

    private function renderError($request, $view, $status, $title)
    {
        if ($request->expectsJson()) {
            return response()->json([
                'message' => self::API_MESSAGES[$view] ?? self::API_MESSAGES['general'],
                'code' => $status,
                'error' => $title,
            ], $status);
        }

        return response()->view("errors.{$view}", compact('title', 'status'), $status);
    }
}
