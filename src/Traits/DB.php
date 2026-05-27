<?php

namespace Nikogin\Framework\Traits;

use wpdb;

trait DB
{
    /**
     * Get the global wpdb instance.
     *
     * @return wpdb
     */
    public function db(): wpdb
    {
        global $wpdb;
        return $wpdb;
    }
}