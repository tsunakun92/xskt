<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Support\Facades\Session;

abstract class TestCase extends BaseTestCase {
    protected function setUp(): void {
        parent::setUp();

        // Disable Vite for all tests to prevent manifest errors
        $this->withoutVite();

        // Start the session for all tests
        Session::start();
    }
}
