<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param \Illuminate\Http\Request $request
     * @param Exception $exception
     * @return $this|\Symfony\Component\HttpFoundation\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof HttpException || $exception instanceof ValidationHttpException) {
            $message = $exception->getMessage();
            $status_code = $exception->getStatusCode();
            $data = compact('message', 'status_code');
            if (config('app.debug')) {
                $trace = $exception->getTraceAsString();
                $data = array_merge($data, compact('trace'));
            }
            return response()->json($data)->setStatusCode($status_code);
        } else {
            return parent::render($request, $exception);
        }
    }
}
