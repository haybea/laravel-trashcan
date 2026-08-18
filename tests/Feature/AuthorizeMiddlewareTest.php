<?php

namespace Haybea\Trashcan\Tests\Feature;

use Haybea\Trashcan\Tests\TestCase;
use Haybea\Trashcan\Trashcan;
use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Gate;

class AuthorizeMiddlewareTest extends TestCase
{
    protected function tearDown(): void
    {
        (new \ReflectionClass(Trashcan::class))->setStaticPropertyValue('authCallback', null);

        parent::tearDown();
    }

    protected function makeUser(): User
    {
        return User::forceCreate([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => bcrypt('secret'),
        ]);
    }

    public function test_allows_access_in_default_allowed_environment(): void
    {
        $this->get(route('trashcan.index'))->assertOk();
    }

    public function test_blocks_when_outside_allowed_environments_with_no_gate_or_auth(): void
    {
        config(['trashcan.allowed_environments' => ['production']]);

        $this->get(route('trashcan.index'))->assertForbidden();
    }

    public function test_allows_via_default_gate_under_documented_name(): void
    {
        config(['trashcan.allowed_environments' => ['production']]);
        Gate::define('viewTrashcan', fn () => true);

        $this->actingAs($this->makeUser())
            ->get(route('trashcan.index'))
            ->assertOk();
    }

    public function test_denies_when_authenticated_user_fails_gate(): void
    {
        config(['trashcan.allowed_environments' => ['production']]);
        Gate::define('viewTrashcan', fn ($user) => false);

        $this->actingAs($this->makeUser())
            ->get(route('trashcan.index'))
            ->assertForbidden();
    }

    public function test_allows_via_custom_auth_callback(): void
    {
        config(['trashcan.allowed_environments' => ['production']]);
        Trashcan::auth(fn ($request) => $request->header('X-Allow') === 'yes');

        $this->withHeaders(['X-Allow' => 'yes'])
            ->get(route('trashcan.index'))
            ->assertOk();

        $this->withHeaders(['X-Allow' => 'no'])
            ->get(route('trashcan.index'))
            ->assertForbidden();
    }
}
