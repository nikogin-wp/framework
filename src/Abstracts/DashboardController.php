<?php

namespace Nikogin\Framework\Abstracts;

abstract class DashboardController
{
    protected string $menuSlug;
    protected string $pageTitle;
    protected string $menuTitle;
    protected string $capability;
    protected string $view;

    public function __construct() {
        add_action('admin_menu', [$this, 'addMenu']);
    }

    abstract public function addMenu(): void;
    abstract public function processForm(): void;
    abstract public function view(): void;

    public function render(): void {
        if (!current_user_can($this->capability)) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $this->processForm();
        }
        $this->view();
    }

    protected function handle(string $action, callable $callback): void {
        if (isset($_POST[$action])) {
            $callback();
        }
    }
}