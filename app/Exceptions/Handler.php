<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of exception types with their corresponding custom log levels.
     *
     * @var array<class-string<Throwable>, \Psr\Log\LogLevel::*>
     */
    protected $levels = [];

    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [];

    /**
     * A list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Custom logging logic here if needed
        });

        $this->renderable(function (Throwable $e, Request $request) {
            // API routes: return JSON
            if ($request->is('api/*')) {
                $status = $e instanceof HttpExceptionInterface ? $e->getStatusCode() : 500;
                return response()->json([
                    'message' => $e->getMessage(),
                    'status' => $status,
                ], $status);
            }

            // HTTP exceptions: render error views
            if ($e instanceof HttpExceptionInterface) {
                $status = $e->getStatusCode();
                return response()->view("errors.{$status}", [], $status);
            }

            // Default: 500
            return response()->view('errors.500', [], 500);
        });
    }
}