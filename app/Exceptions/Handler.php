<?php

namespace App\Exceptions;

use App\Http\Traits\Helpers\ApiResponseTrait;
namespace App\Exceptions;

use App\Http\Traits\Helpers\ApiResponseTrait;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\Exceptions\MissingAbilityException;
use Throwable;
use Route;

class Handler extends ExceptionHandler
{
    use ApiResponseTrait;
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
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });
    }

    public function render($request, Throwable $exception) {
        if($request->wantsJson()){
            if ($exception instanceof MissingAbilityException) {
                return $this->failure('Không có quyền truy cập', [], Response::HTTP_UNAUTHORIZED);
            }


            $e = $this->prepareException($exception);

            if ($e instanceof HttpResponseException) {
                return $this->failure($e->getMessage(), $e->getTrace());
            }

            if ($e instanceof AuthenticationException) {
                return $this->failure('Chưa đăng nhập hoặc không có quyền hạn', $e->getTrace(), Response::HTTP_UNAUTHORIZED);
            }

            if ($e instanceof ValidationException) {
                return $this->failure('Lỗi dữ liệu đầu vào', $e->errors(), Response::HTTP_NOT_ACCEPTABLE);
            }

            return $this->failure($exception->getMessage(), ['file' => $exception->getFile(), 'line' => $exception->getLine()]);
        }
        return parent::render($request, $exception);

    }


}
