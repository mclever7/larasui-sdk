<?php

namespace Mclever\LarasuiSdk;

use Illuminate\Support\Facades\Http;

class SuiClient
{
    protected $rpcUrl;

    public function __construct($rpcUrl)
    {
        $this->rpcUrl = $rpcUrl;
    }

    public function getBalance($address)
    {
        $response = Http::post($this->rpcUrl, [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'sui_getBalance',
            'params' => [$address]
        ]);

        return $response->json();
    }
}
