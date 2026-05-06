<?php

namespace Modules\Like\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Modules\Like\Http\Resources\Transformers\LikeTransformer;
use Modules\Like\Models\Like;
use Modules\Like\Tests\TestCase;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Modules\Person\Models\Person;

class LikeTransformerTest extends TestCase
{
    use RefreshDatabase;

    private Manager        $fractal;
    private LikeTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fractal     = new Manager();
        $this->transformer = new LikeTransformer();
    }

    public function test_transform_returns_all_expected_keys(): void
    {
        $user   = User::factory()->create();
        $target = Episode::factory()->create();
        $like   = Like::factory()->forUser($user)->forLikeable($target, 'episode')->create();

        $data = $this->transformer->transform($like->fresh());

        foreach (['id', 'user_id', 'likeable_type', 'likeable_id', 'created_at', 'updated_at'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }
    }

    public function test_likeable_type_is_class_basename_not_fqcn(): void
    {
        $user   = User::factory()->create();
        $target = Article::factory()->create();
        $like   = Like::factory()->forUser($user)->forLikeable($target, 'article')->create();

        $data = $this->transformer->transform($like->fresh());

        $this->assertSame('article', $data['likeable_type']);
        $this->assertStringNotContainsString('\\', $data['likeable_type']);
    }

    public function test_transform_maps_values_to_correct_model_attributes(): void
    {
        $user   = User::factory()->create();
        $target = Person::factory()->create();
        $like   = Like::factory()->forUser($user)->forLikeable($target, 'person')->create();

        $data = $this->transformer->transform($like->fresh());

        $this->assertSame($like->id, $data['id']);
        $this->assertSame($user->id, $data['user_id']);
        $this->assertSame($target->id, $data['likeable_id']);
    }

    public function test_available_includes_contains_likeable(): void
    {
        $this->assertContains('likeable', $this->transformer->getAvailableIncludes());
    }

    public function test_include_likeable_returns_null_resource_when_relation_is_null(): void
    {
        $like = new Like([
            'user_id'       => 1,
            'likeable_id'   => 999,
            'likeable_type' => User::class,
        ]);
        $like->setRelation('likeable', null);

        $this->assertInstanceOf(NullResource::class, $this->transformer->includeLikeable($like));
    }

    public function test_include_likeable_degrades_gracefully_when_no_transformer_registered(): void
    {
        $user   = User::factory()->create();
        $target = User::factory()->create();
        $like   = Like::factory()->forUser($user)->forLikeable($target, 'movie')->create()->load('likeable');

        $this->assertInstanceOf(NullResource::class, $this->transformer->includeLikeable($like));
    }

    public function test_fractal_serializes_like_correctly(): void
    {
        $user   = User::factory()->create();
        $target = Person::factory()->create();
        $like   = Like::factory()->forUser($user)->forLikeable($target, 'person')->create();

        $output = $this->fractal
            ->createData(new Item($like->fresh(), $this->transformer))
            ->toArray();

        $this->assertArrayHasKey('data', $output);
        $this->assertSame($user->id, $output['data']['user_id']);
        $this->assertSame('person', $output['data']['likeable_type']);
    }
}
