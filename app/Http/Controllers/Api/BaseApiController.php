<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use App\Traits\ApiResponse;

class BaseApiController extends Controller
{
    use ApiResponse;
    use AuthorizesRequests;
}
