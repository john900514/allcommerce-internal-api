<?php

namespace App\Services;

use Ixudra\Curl\Facades\Curl;

class AnAPIService implements BaseService
{
    protected function setCurl($url, array $query_data = null, array $headers = null, $json_request = true, $json_response = true)
    {
        $response = Curl::to($url);

        if (!is_null($query_data)) {
            $response = $response->withData($query_data);
        }

        if (!is_null($headers)) {
            $response = $response->withHeaders($headers);
        }

        if ($json_request && $json_response)
        {
            $response = $response->asJson(true);
        }
        elseif ((!$json_request) && ($json_response))
        {
            $response = $response->asJsonResponse(true);
        }
        elseif (($json_request) && (!$json_response))
        {
            $response = $response->asJsonRequest();
        }

        return $response;
    }

    public function get($url, array $query_data = null, array $headers = null, $json_request = true, $json_response = true)
    {
       $results = false;

        $response = $this->setCurl($url, $query_data, $headers, $json_request, $json_response)
            ->get();

        if(!is_null($response))
        {
            $results = $response;
        }

        return $results;
    }

    public function post($url, array $payload = null, array $headers = null, $json_request = true, $json_response = true)
    {
        $results = false;

        $response = $this->setCurl($url, $payload, $headers, $json_request, $json_response)
            ->post();

        if(!is_null($response))
        {
            $results = $response;
        }

        return $results;
    }

    public function put($url, array $payload = null, array $headers = null, $json_request = true, $json_response = true)
    {
        $results = false;

        $response = $this->setCurl($url, $payload, $headers, $json_request, $json_response)
            ->put();

        if(!is_null($response))
        {
            $results = $response;
        }

        return $results;
    }
}
