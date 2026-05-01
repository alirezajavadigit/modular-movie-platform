<?php

namespace Modules\Person\Tests\Unit\Repositories;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Article\Models\Article;
use Modules\Person\DTOs\CreateCreditDTO;
use Modules\Person\DTOs\UpdateCreditDTO;
use Modules\Person\Enums\CreditRole;
use Modules\Person\Models\Credit;
use Modules\Person\Models\Person;
use Modules\Person\Repositories\CreditRepository;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class CreditRepositoryTest extends TestCase
{
    use RefreshDatabase;

    private CreditRepository $repository;

    protected function setUp(): void
    {
        parent::setUp();
        $this->repository = new CreditRepository(new Credit());
    }

    public function test_find_by_id_returns_credit(): void
    {
        $person  = Person::factory()->create();
        $article = Article::factory()->create();

        $credit = Credit::factory()->create([
            'person_id'       => $person->id,
            'creditable_type' => 'article',
            'creditable_id'   => $article->id,
        ]);

        $this->assertEquals($credit->id, $this->repository->findById($credit->id)->id);
    }

    public function test_get_by_person_returns_credits_for_person(): void
    {
        $person  = Person::factory()->create();
        $article = Article::factory()->create();

        Credit::factory()->count(3)->create([
            'person_id'       => $person->id,
            'creditable_type' => 'article',
            'creditable_id'   => $article->id,
        ]);

        $this->assertEquals(3, $this->repository->getByPerson($person->id)->total());
    }

    public function test_get_by_creditable_filters_correctly(): void
    {
        $article1 = Article::factory()->create();
        $article2 = Article::factory()->create();

        Credit::factory()->count(3)->create([
            'creditable_type' => 'article',
            'creditable_id'   => $article1->id,
        ]);
        Credit::factory()->count(2)->create([
            'creditable_type' => 'article',
            'creditable_id'   => $article2->id,
        ]);

        $this->assertEquals(3, $this->repository->getByCreditable('article', $article1->id)->total());
    }

    public function test_get_cast_returns_only_cast_roles(): void
    {
        $article = Article::factory()->create();

        Credit::factory()->create([
            'creditable_type' => 'article',
            'creditable_id'   => $article->id,
            'role'            => CreditRole::ACTOR->value,
        ]);
        Credit::factory()->create([
            'creditable_type' => 'article',
            'creditable_id'   => $article->id,
            'role'            => CreditRole::DIRECTOR->value,
        ]);

        $this->assertCount(1, $this->repository->getCastFor('article', $article->id));
    }

    public function test_get_crew_returns_only_crew_roles(): void
    {
        $article = Article::factory()->create();

        Credit::factory()->create([
            'creditable_type' => 'article',
            'creditable_id'   => $article->id,
            'role'            => CreditRole::ACTOR->value,
        ]);
        Credit::factory()->create([
            'creditable_type' => 'article',
            'creditable_id'   => $article->id,
            'role'            => CreditRole::DIRECTOR->value,
        ]);
        Credit::factory()->create([
            'creditable_type' => 'article',
            'creditable_id'   => $article->id,
            'role'            => CreditRole::COMPOSER->value,
        ]);

        $this->assertCount(2, $this->repository->getCrewFor('article', $article->id));
    }

    public function test_create_persists_credit(): void
    {
        $person  = Person::factory()->create();
        $article = Article::factory()->create();

        $dto = new CreateCreditDTO(
            personId: $person->id,
            creditableType: 'article',
            creditableId: $article->id,
            role: CreditRole::ACTOR->value,
            characterName: 'Neo',
            creditedAs: null,
            department: null,
            order: 1,
        );

        $credit = $this->repository->create($dto);

        $this->assertNotNull($credit->id);
        $this->assertEquals('Neo', $credit->character_name);
    }

    public function test_update_modifies_credit(): void
    {
        $credit = Credit::factory()->create([
            'creditable_type' => 'article',
            'creditable_id'   => Article::factory()->create()->id,
            'character_name'  => 'Old',
        ]);

        $dto = new UpdateCreditDTO(
            role: null,
            characterName: 'New Character',
            creditedAs: null,
            department: null,
            order: 99,
        );

        $updated = $this->repository->update($credit->id, $dto);

        $this->assertEquals('New Character', $updated->character_name);
        $this->assertEquals(99, $updated->order);
    }

    public function test_delete_soft_deletes(): void
    {
        $credit = Credit::factory()->create([
            'creditable_type' => 'article',
            'creditable_id'   => Article::factory()->create()->id,
        ]);

        $this->assertTrue($this->repository->delete($credit->id));
        $this->assertSoftDeleted('credits', ['id' => $credit->id]);
    }

    public function test_restore_recovers_soft_deleted(): void
    {
        $credit = Credit::factory()->create([
            'creditable_type' => 'article',
            'creditable_id'   => Article::factory()->create()->id,
        ]);
        $credit->delete();

        $this->assertNull($this->repository->restore($credit->id)->deleted_at);
    }

    public function test_exists_returns_correct_boolean(): void
    {
        $credit = Credit::factory()->create([
            'creditable_type' => 'article',
            'creditable_id'   => Article::factory()->create()->id,
        ]);

        $this->assertTrue($this->repository->exists($credit->id));
        $this->assertFalse($this->repository->exists(999));
    }
}
