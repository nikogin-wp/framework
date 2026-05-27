<?php

namespace Nikogin\Framework\Abstracts;

abstract class SubmenuController extends DashboardController
{
    protected string $parentSlug;

    public function addMenu(): void {
        add_submenu_page($this->parentSlug, $this->pageTitle, $this->menuTitle, $this->capability, $this->menuSlug, [$this, 'render']);
    }
}