<?php

namespace Fixture\App\Admin;

// Extends a class that is not loaded here (admin-only in WordPress).
// Autoloading this file would fatal, so discovery must never touch it.
class AdminTable extends \WP_List_Table
{
}
