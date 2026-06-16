<?php

namespace Modules\Person\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Mockery;
use Modules\Auth\Models\User;
use Modules\Person\Contracts\PersonServiceInterface;
use Modules\Person\Models\Person;
use Modules\Person\Tests\Concerns\LoadsMediaLibrary;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class PersonFeatureTest extends TestCase
{
    use RefreshDatabase;
    use LoadsMediaLibrary;

    private PersonServiceInterface $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->loadMediaLibraryMigration();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->service = Mockery::mock(PersonServiceInterface::class);
        $this->app->instance(PersonServiceInterface::class, $this->service);
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        Mockery::close();
    }

    private function makePerson(array $attributes = []): Person
    {
        $person = Mockery::mock(Person::class)->makePartial();

        foreach (
            array_merge([
                'id'                   => 1,
                'slug'                 => 'john-doe',
                'date_of_birth'        => null,
                'date_of_death'        => null,
                'gender'               => null,
                'known_for_department' => 'Acting',
                'popularity'           => 50.0,
                'is_active'            => true,
                'created_at'           => now(),
                'updated_at'           => now(),
                'deleted_at'           => null,
                'full_name'            => 'John Doe',
            ], $attributes) as $key => $value
        ) {
            $person->$key = $value;
        }

        $person->shouldReceive('getTranslations')->with('first_name')->andReturn(['en' => 'John']);
        $person->shouldReceive('getTranslations')->with('last_name')->andReturn(['en' => 'Doe']);
        $person->shouldReceive('getTranslations')->with('biography')->andReturn([]);
        $person->shouldReceive('getTranslations')->with('place_of_birth')->andReturn([]);
        $person->shouldReceive('getFirstMediaUrl')->andReturn('');

        return $person;
    }

    private function makePaginator(array $items = []): LengthAwarePaginator
    {
        return new LengthAwarePaginator($items, count($items), 15, 1, ['path' => 'http://localhost']);
    }

    private function asAdmin(): static
    {
        $user = User::factory()->create();

        $permissions = [
            'persons.viewAny',
            'persons.view',
            'persons.create',
            'persons.update',
            'persons.delete',
            'persons.restore',
            'persons.forceDelete',
            'persons.activate',
            'persons.deactivate',
            'persons.viewTrashed',
        ];

        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'api');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo($permissions);

        return $this->actingAs($user, 'api');
    }

    private function storePayload(array $override = []): array
    {
        return array_merge([
            'first_name' => ['en' => 'John'],
            'last_name'  => ['en' => 'Doe'],
            'slug'       => 'john-doe',
        ], $override);
    }

    public function test_active_returns_paginated_list(): void
    {
        $this->service->shouldReceive('getActive')->once()->with(15)->andReturn($this->makePaginator([$this->makePerson()]));

        $this->getJson('/api/v1/persons/active')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_popular_returns_collection(): void
    {
        $this->service->shouldReceive('getPopular')->once()->with(20)->andReturn(new \Illuminate\Database\Eloquent\Collection([$this->makePerson()]));

        $this->getJson('/api/v1/persons/popular')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_by_department_returns_paginated(): void
    {
        $this->service->shouldReceive('getByDepartment')->once()->with('Acting', 15)->andReturn($this->makePaginator());

        $this->getJson('/api/v1/persons/department/Acting')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_search_returns_paginated(): void
    {
        $this->service->shouldReceive('search')->once()->with('nolan', 15)->andReturn($this->makePaginator());

        $this->getJson('/api/v1/persons/search?q=nolan')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_find_by_slug_returns_person(): void
    {
        $this->service->shouldReceive('findBySlug')->once()->with('john-doe')->andReturn($this->makePerson());

        $this->getJson('/api/v1/persons/slug/john-doe')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_index_requires_authentication(): void
    {
        $this->getJson('/api/v1/admin/persons')->assertUnauthorized();
    }

    public function test_store_requires_authentication(): void
    {
        $this->postJson('/api/v1/admin/persons', $this->storePayload())->assertUnauthorized();
    }

    public function test_index_returns_paginated(): void
    {
        $this->service->shouldReceive('adminFilter')->once()->andReturn($this->makePaginator([$this->makePerson()]));

        $this->asAdmin()
            ->getJson('/api/v1/admin/persons')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_store_creates_person(): void
    {
        $this->service->shouldReceive('store')->once()->andReturn($this->makePerson());

        $this->asAdmin()
            ->postJson('/api/v1/admin/persons', $this->storePayload())
            ->assertCreated()
            ->assertJsonPath('success', true);
    }

    public function test_store_fails_validation_when_first_name_missing(): void
    {
        $this->asAdmin()
            ->postJson('/api/v1/admin/persons', $this->storePayload(['first_name' => null]))
            ->assertUnprocessable();
    }

    public function test_store_fails_validation_when_slug_invalid(): void
    {
        $this->asAdmin()
            ->postJson('/api/v1/admin/persons', $this->storePayload(['slug' => 'Invalid Slug!']))
            ->assertUnprocessable();
    }

    public function test_store_fails_validation_when_gender_invalid(): void
    {
        $this->asAdmin()
            ->postJson('/api/v1/admin/persons', $this->storePayload(['gender' => 'robot']))
            ->assertUnprocessable();
    }

    public function test_show_returns_person(): void
    {
        $person = Person::factory()->create();
        $this->service->shouldReceive('findById')->once()->with($person->id)->andReturn($person);

        $this->asAdmin()
            ->getJson("/api/v1/admin/persons/{$person->id}")
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_update_modifies_person(): void
    {
        $person = Person::factory()->create();
        $this->service->shouldReceive('update')->once()->with($person->id, Mockery::any(), null)->andReturn($person);

        $this->asAdmin()
            ->putJson("/api/v1/admin/persons/{$person->id}", ['first_name' => ['en' => 'Jane']])
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_destroy_deletes_person(): void
    {
        $person = Person::factory()->create();
        $this->service->shouldReceive('delete')->once()->with($person->id)->andReturn(true);

        $this->asAdmin()
            ->deleteJson("/api/v1/admin/persons/{$person->id}")
            ->assertNoContent();
    }
    public function test_restore_returns_restored(): void
    {
        $dbPerson = Person::factory()->create();
        $dbPerson->delete();

        $this->service->shouldReceive('restore')->once()->with($dbPerson->id)->andReturn($this->makePerson());

        $this->asAdmin()
            ->patchJson("/api/v1/admin/persons/{$dbPerson->id}/restore")
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    public function test_polymorphic_attach_person_to_article_via_credit(): void
    {
        Mockery::close();
        $this->app->forgetInstance(PersonServiceInterface::class);

        $person  = Person::factory()->create();
        $article = \Modules\Article\Models\Article::factory()->create();

        $credit = $article->attachCredit($person, \Modules\Person\Enums\CreditRole::ACTOR, [
            'character_name' => 'Neo',
            'order'          => 1,
        ]);

        $this->assertDatabaseHas('credits', [
            'person_id'       => $person->id,
            'creditable_id'   => $article->id,
            'creditable_type' => 'article',
            'role'            => 'actor',
            'character_name'  => 'Neo',
        ]);

        $this->assertTrue($article->credits()->whereKey($credit->id)->exists());
        $this->assertTrue($person->credits()->whereKey($credit->id)->exists());
    }
}
