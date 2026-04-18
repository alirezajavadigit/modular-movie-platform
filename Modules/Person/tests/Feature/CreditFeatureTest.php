<?php

namespace Modules\Person\Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Modules\Person\Enums\CreditRole;
use Modules\Person\Models\Credit;
use Modules\Person\Models\Person;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class CreditFeatureTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function asAdmin(): static
    {
        $user = User::factory()->create();

        $permissions = [
            'credits.viewAny',
            'credits.view',
            'credits.create',
            'credits.update',
            'credits.delete',
            'credits.restore',
            'credits.forceDelete',
        ];

        foreach ($permissions as $p) {
            Permission::findOrCreate($p, 'api');
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $user->givePermissionTo($permissions);

        return $this->actingAs($user, 'api');
    }

    public function test_store_creates_credit_with_character_name(): void
    {
        $person  = Person::factory()->create();
        $article = Article::factory()->create();

        $this->asAdmin()
            ->postJson('/api/v1/admin/credits', [
                'person_id'       => $person->id,
                'creditable_type' => 'article',
                'creditable_id'   => $article->id,
                'role'            => CreditRole::ACTOR->value,
                'character_name'  => 'Neo',
                'order'           => 1,
            ])
            ->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('credits', [
            'person_id'       => $person->id,
            'creditable_type' => 'article',
            'creditable_id'   => $article->id,
            'role'            => CreditRole::ACTOR->value,
            'character_name'  => 'Neo',
        ]);
    }

    public function test_store_fails_when_person_does_not_exist(): void
    {
        $article = Article::factory()->create();

        $this->asAdmin()
            ->postJson('/api/v1/admin/credits', [
                'person_id'       => 999999,
                'creditable_type' => 'article',
                'creditable_id'   => $article->id,
                'role'            => CreditRole::ACTOR->value,
            ])
            ->assertUnprocessable();
    }

    public function test_store_fails_when_role_is_invalid(): void
    {
        $person  = Person::factory()->create();
        $article = Article::factory()->create();

        $this->asAdmin()
            ->postJson('/api/v1/admin/credits', [
                'person_id'       => $person->id,
                'creditable_type' => 'article',
                'creditable_id'   => $article->id,
                'role'            => 'super-hero',
            ])
            ->assertUnprocessable();
    }

    public function test_store_fails_when_creditable_type_not_in_morph_map(): void
    {
        $person  = Person::factory()->create();

        $this->asAdmin()
            ->postJson('/api/v1/admin/credits', [
                'person_id'       => $person->id,
                'creditable_type' => 'unknown-type',
                'creditable_id'   => 1,
                'role'            => CreditRole::ACTOR->value,
            ])
            ->assertUnprocessable();
    }

    public function test_single_person_can_have_multiple_roles_on_same_creditable(): void
    {
        $person  = Person::factory()->create();
        $article = Article::factory()->create();

        $article->attachCredit($person, CreditRole::DIRECTOR);
        $article->attachCredit($person, CreditRole::WRITER);
        $article->attachCredit($person, CreditRole::PRODUCER);

        $this->assertEquals(3, $article->credits()->count());
        $this->assertEquals(3, $person->credits()->count());

        $this->assertTrue($article->directors()->exists());
        $this->assertTrue($article->writers()->exists());
        $this->assertTrue($article->producers()->exists());
    }

    public function test_cast_and_crew_scopes_separate_correctly(): void
    {
        $actor    = Person::factory()->create();
        $director = Person::factory()->create();
        $article  = Article::factory()->create();

        $article->attachCredit($actor, CreditRole::ACTOR, ['character_name' => 'Neo']);
        $article->attachCredit($director, CreditRole::DIRECTOR);

        $this->assertEquals(1, $article->cast()->count());
        $this->assertEquals(1, $article->crew()->count());
    }

    public function test_cast_endpoint_returns_only_cast_credits(): void
    {
        $actor    = Person::factory()->create();
        $director = Person::factory()->create();
        $article  = Article::factory()->create();

        $article->attachCredit($actor, CreditRole::ACTOR, ['character_name' => 'Neo', 'order' => 1]);
        $article->attachCredit($director, CreditRole::DIRECTOR, ['order' => 0]);

        $response = $this->getJson("/api/v1/credits/article/{$article->id}/cast")
            ->assertOk()
            ->assertJsonPath('success', true);

        $data = $response->json('data');
        $this->assertCount(1, $data);
    }

    public function test_crew_endpoint_returns_only_crew_credits(): void
    {
        $actor    = Person::factory()->create();
        $director = Person::factory()->create();
        $article  = Article::factory()->create();

        $article->attachCredit($actor, CreditRole::ACTOR, ['character_name' => 'Neo']);
        $article->attachCredit($director, CreditRole::DIRECTOR);

        $response = $this->getJson("/api/v1/credits/article/{$article->id}/crew")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertCount(1, $response->json('data'));
    }

    public function test_update_modifies_credit(): void
    {
        $person  = Person::factory()->create();
        $article = Article::factory()->create();

        $credit = $article->attachCredit($person, CreditRole::ACTOR, ['character_name' => 'Old']);

        $this->asAdmin()
            ->putJson("/api/v1/admin/credits/{$credit->id}", [
                'character_name' => 'New Character',
                'order'          => 5,
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('credits', [
            'id'             => $credit->id,
            'character_name' => 'New Character',
            'order'          => 5,
        ]);
    }

    public function test_destroy_soft_deletes_credit(): void
    {
        $credit = Credit::factory()->create([
            'creditable_type' => 'article',
            'creditable_id'   => Article::factory()->create()->id,
        ]);

        $this->asAdmin()
            ->deleteJson("/api/v1/admin/credits/{$credit->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('credits', ['id' => $credit->id]);
    }

    public function test_morph_map_uses_aliases_not_fqcn(): void
    {
        $person  = Person::factory()->create();
        $article = Article::factory()->create();

        $article->attachCredit($person, CreditRole::ACTOR);

        $this->assertDatabaseHas('credits', [
            'creditable_type' => 'article',
        ]);
        $this->assertDatabaseMissing('credits', [
            'creditable_type' => Article::class,
        ]);
    }
}
