<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\PostTooLargeException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use RuntimeException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $exception)
    {
        // 413: Payload Too Large
        if ($exception instanceof PostTooLargeException) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => 'Uploaded files are too large. Please upload smaller images.',
                ], 413);
            }

            return back()->withErrors(['error' => 'File size is too large.'], 413);
        }

        if ($exception instanceof RuntimeException) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'status' => false,
                    'message' => $exception->getMessage(),
                ], 422);
            }
        }

        // 403: Access Denied handler
        // if ($exception instanceof HttpException && $exception->getStatusCode() == 403) {

        //     $user = $request->user();

        //     if ($user->hasRole('shop') && !$user->hasRole('root')) {
        //         return to_route('shop.dashboard.index');
        //     }

        //     return to_route('admin.dashboard.index');
        // }

        return parent::render($request, $exception);
    }

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }
}
