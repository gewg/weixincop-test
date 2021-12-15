<?php

namespace App\Http\Controllers;

use Laravel\Lumen\Routing\Controller;

class BaseController extends Controller
{
    /**
     * 构造返回数据
     *
     * @param $state
     * @param string $message
     * @param array $data
     * @return array
     */
    protected function buildReturn($state, $message = '', $data = [])
    {
        return [
            'state' => strval($state),
            'message' => strval($message),
            'data' => $data
        ];
    }
}
