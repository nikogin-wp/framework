<?php

namespace Nikogin\Framework\Abstracts;

use WP_REST_Response;

abstract class ApiController
{
    /**
     * Creates a successful WP_REST_Response.
     *
     * @param mixed $data The data to include in the response.
     * @param string $message A success message.
     * @param int $statusCode The HTTP status code (default: 200).
     * @return WP_REST_Response
     */
    protected function success(mixed $data = [], string $message = 'Success.', int $statusCode = 200): WP_REST_Response
    {
        return new WP_REST_Response([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $statusCode);
    }

    /**
     * Creates a failed WP_REST_Response.
     *
     * @param string $message An error message.
     * @param int $statusCode The HTTP status code (default: 400 Bad Request).
     * @param array $errors Optional array of detailed errors.
     * @return WP_REST_Response
     */
    protected function failed(string $message = 'An error occurred.', int $statusCode = 400, array $errors = []): WP_REST_Response
    {
        $response = [
            'success' => false,
            'message' => $message,
        ];

        if (!empty($errors)) {
            $response['errors'] = $errors;
        }

        return new WP_REST_Response($response, $statusCode);
    }
}