<?php
namespace Nikogin\Framework\Support;

class HTTP
{
    /**
     * Send a GET request
     *
     * @param string $url
     * @param array $headers
     * @param int|null $timeout
     * @return array
     */
    public static function get(string $url, array $headers = [], ?int $timeout = 0): array
    {
        return self::request('GET', $url, [], $headers, $timeout);
    }

    /**
     * Send a POST request
     */
    public static function post(string $url, array $data = [], array $headers = [], ?int $timeout = 0): array
    {
        return self::request('POST', $url, $data, $headers, $timeout);
    }

    /**
     * Send a PUT request
     */
    public static function put(string $url, array $data = [], array $headers = [], ?int $timeout = 0): array
    {
        return self::request('PUT', $url, $data, $headers, $timeout);
    }

    /**
     * Send a DELETE request
     */
    public static function delete(string $url, array $headers = [], ?int $timeout = 0): array
    {
        return self::request('DELETE', $url, [], $headers, $timeout);
    }

    /**
     * Generic request handler
     *
     * @param string $method
     * @param string $url
     * @param array $data
     * @param array $headers
     * @param int|null $timeout
     * @return array
     */
    protected static function request(string $method, string $url, array $data = [], array $headers = [], ?int $timeout = 0): array
    {
        $args = [
            'method'  => strtoupper($method),
            'headers' => array_merge([
                'Content-Type' => 'application/json',
                'Accept'       => 'application/json',
            ], $headers),
            'timeout' => $timeout,
        ];

        if (!empty($data)) {
            // ensure we send proper JSON body
            $args['body'] = wp_json_encode($data);
        }

        $response = wp_remote_request($url, $args);

        if (is_wp_error($response)) {
            return [
                'success' => false,
                'error'   => $response->get_error_message(),
                'code'    => $response->get_error_code(),
            ];
        }

        $code = (int) wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        $decoded = json_decode($body, true);
        $json_error = (json_last_error() === JSON_ERROR_NONE) ? null : json_last_error_msg();

        return [
            'success'    => $code >= 200 && $code < 300,
            'code'       => $code,
            'body'       => $decoded,
            'raw'        => $body,
            'json_error' => $json_error,
        ];
    }
}
