<?php

namespace App\Services;

interface BaseService
{
    public function get($url, array $query_data = null, array $headers = null, $json_request = true, $json_response = true);

    public function post($url, array $payload = null, array $headers = null, $json_request = true, $json_response = true);

    public function put($url, array $payload = null, array $headers = null, $json_request = true, $json_response = true);
}
