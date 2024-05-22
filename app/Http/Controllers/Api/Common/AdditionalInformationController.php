<?php

namespace App\Http\Controllers\Api\Common;

use App\Http\Controllers\Controller;
use App\Http\Traits\Helpers\ApiResponseTrait;
use App\Models\Province;
use Illuminate\Http\Request;

class AdditionalInformationController extends Controller
{
    use ApiResponseTrait;
    public function getProvinces()
    {
        $provinces = Province::with(['districts'])->get();
        return $this->success($provinces);
    }
}
