<?php

namespace App\Exceptions;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\App;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;
use Laravel\Lumen\Exceptions\Handler as ExceptionHandler;
use Symfony\Component\HttpFoundation\File\Exception\AccessDeniedException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that should not be reported.
     *
     * @var array
     */
    protected $dontReport = [
        AuthorizationException::class,
        HttpException::class,
        ModelNotFoundException::class,
        ValidationException::class,
    ];

    /**
     * All known exception with their error code and message
     * adaptMessage defines whether the message contained in the error should be used (true) or not (false)
     *
     * @var array|array[]
     */
    protected array $exceptionMap = [
        ModelNotFoundException::class => [
            'code' => 404,
            'type' => 'NOT_IN_DB',
            'message' => 'Could not find what you were looking for.',
            'adaptMessage' => false,
        ],

        NotFoundHttpException::class => [
            'code' => 404,
            'type' => 'NOT_FOUND',
            'message' => 'Could not find what you were looking for.',
            'adaptMessage' => false,
        ],

        MethodNotAllowedHttpException::class => [
            'code' => 405,
            'type' => 'NOT_ALLOWED',
            'message' => 'This method is not allowed for this endpoint.',
            'adaptMessage' => false,
        ],

        ValidationException::class => [
            'code' => 422,
            'type' => 'VALIDATION_FAILED',
            'message' => 'Some data failed validation in the request',
            'adaptMessage' => false,
        ],

        InvalidArgumentException::class => [
            'code' => 400,
            'type' => 'INVALID_ARGUMENT',
            'message' => 'You provided some invalid input value',
            'adaptMessage' => true,
        ],

        // TODO maybe add InvalidFormatException (thrown by e.g. Carbon::parse())
        AccessDeniedException::class => [
            'code' => 403,
            'type' => 'ACCESS_DENIED',
            'message' => 'You don\'t have the rights to access the desired resource',
            'adaptMessage' => false,
        ]
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Throwable  $exception
     * @return void
     *
     * @throws \Exception
     */
    public function report(Throwable $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Throwable  $exception
     * @return \Illuminate\Http\Response|\Illuminate\Http\JsonResponse
     *
     * @throws \Throwable
     */
    public function render($request, Throwable $exception)
    {
        // if App isn't in production return the default lumen error messages
        if (!App::isProduction() && false) { // TODO remove && false
            return parent::render($request, $exception);
        }

        // custom exception reporting
        $response = $this->formatException($exception);

        return response()->json(['error' => $response], $response['status'] ?? 500);
    }

    /**
     * Format an exception to the given standards
     *
     * @param \Throwable $exception
     *
     * @return array
     */
    protected function formatException(\Throwable $exception): array
    {
        // get the type of the thrown exception
        $exceptionClass = get_class($exception);

        // Multi exceptions get a special treatment
        if ($exceptionClass == "App\Exceptions\MultiException") {
            // place holder
            $content = [];

            // format all the different exceptions (note type error since it isn't recognized that $exception is MultiException
            // and I don't know how to tell php that it is
            foreach ($exception->getExceptions() as $e) {
                $content[] = $this->formatException($e);
            }

            return $content;
        }

        // search the exception in the above cerated map, if it doesn't exist assume generic exception
        $definition = $this->exceptionMap[$exceptionClass] ?? [
            'code' => 500,
            'type' => 'SERVER_ERROR',
            'message' => $exception->getMessage() ?? 'Something went wrong while processing your request',
            'adaptMessage' => false,
        ];

        // check wether to adapt the message stored in the error or not
        if (!empty($definition['adaptMessage'])) {
            $definition['message'] = $exception->getMessage() ?? $definition['message'];
        }

        return [
            'status' => $definition['code'] ?? 500,
            'title' => $definition['title'] ?? 'Error',
            'description' => $definition['message'],
        ];
    }
}
