<?php

namespace Nikogin\Framework\Contracts;

use WP_Error;
use WP_REST_Request;

interface Middleware
{

    public static function verify(WP_REST_Request $request): bool|WP_Error;

}