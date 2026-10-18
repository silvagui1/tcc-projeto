<?php

namespace Tests;

use App\Models\User;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;

    /**
     * Todo o sistema exige login, então os testes já começam conectados como
     * admin. Testes do próprio login (ou do que acontece sem login) desligam
     * isto com `protected bool $entrarAutomaticamente = false;`.
     */
    protected bool $entrarAutomaticamente = true;

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->entrarAutomaticamente) {
            $this->actingAs(User::factory()->admin()->create());
        }
    }
}
