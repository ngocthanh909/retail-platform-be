<?php
/**
 * Created by PhpStorm.
 * User: Bawa, Lakhveer
 * Email: iamdeep.dhaliwal@gmail.com
 * Date: 2020-06-14
 * Time: 12:18 p.m.
 */

namespace App\Http\Traits\Helpers;

use Illuminate\Http\Response;
trait ApiResponseTrait
{
    protected function success($data = [], $message = null, $status = Response::HTTP_OK)
    {
        return response([
            'success' => true,
            'data' => $data,
            'message' => $message ?? "Thành công",
        ], $status);
    }

    protected function failure($message = "", $errors = [], $status = Response::HTTP_BAD_REQUEST)
    {
        return response([
            'success' => false,
            'message' => $message ?? "Lỗi hệ thống",
            'errors' => $errors
        ], $status);
    }
}
