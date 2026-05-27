<?php

namespace Nikogin\Framework\Abstracts;

abstract class MenuController extends DashboardController
{
    protected string $dashIcon = 'dashicons-admin-generic';
    public function addMenu(): void {
        add_menu_page($this->pageTitle, $this->menuTitle, $this->capability, $this->menuSlug, [$this, 'render'], $this->dashIcon, 20);
    }
}