<?php

namespace Modules\Favorite\Tests\Unit;

use Illuminate\Foundation\Testing\RefreshDatabase;
use League\Fractal\Manager;
use League\Fractal\Resource\Item;
use League\Fractal\Resource\NullResource;
use Modules\Article\Models\Article;
use Modules\Auth\Models\User;
use Modules\Favorite\Http\Resources\Transformers\FavoriteTransformer;
use Modules\Favorite\Models\Favorite;
use Modules\Favorite\Tests\TestCase;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Modules\Person\Models\Person;

class FavoriteTransformerTest extends TestCase
{
    use RefreshDatabase;

    private Manager            $fractal;
    private FavoriteTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->fractal     = new Manager();
        $this->transformer = new FavoriteTransformer();
    }

    public function test_transform_returns_all_expected_keys(): void
    {
        $user     = User::factory()->create();
        $target   = Episode::factory()->create();
        $favorite = Favorite::factory()->forUser($user)->forFavoriteable($target, 'episode')->create();

        $data = $this->transformer->transform($favorite->fresh());

        foreach (['id', 'user_id', 'favoritable_type', 'favoritable_id', 'created_at', 'updated_at'] as $key) {
            $this->assertArrayHasKey($key, $data);
        }
    }

    public function test_favoritable_type_is_class_basename_not_fqcn(): void
    {
        $user     = User::factory()->create();
        $target   = Article::factory()->create();
        $favorite = Favorite::factory()->forUser($user)->forFavoriteable($target, 'article')->create();

        $data = $this->transformer->transform($favorite->fresh());

        $this->assertSame('article', $data['favoritable_type']);
        $this->assertStringNotContainsString('\\', $data['favoritable_type']);
    }

    public function test_transform_maps_values_to_correct_model_attributes(): void
    {
        $user     = User::factory()->create();
        $target   = Person::factory()->create();
        $favorite = Favorite::factory()->forUser($user)->forFavoriteable($target, 'person')->create();

        $data = $this->transformer->transform($favorite->fresh());

        $this->assertSame($favorite->id, $data['id']);
        $this->assertSame($user->id, $data['user_id']);
        $this->assertSame($target->id, $data['favoritable_id']);
    }

    public function test_available_includes_contains_favoritable(): void
    {
        $this->assertContains('favoritable', $this->transformer->getAvailableIncludes());
    }

    public function test_include_favoritable_returns_null_resource_when_relation_is_null(): void
    {
        $favorite = new Favorite([
            'user_id'           => 1,
            'favoriteable_id'   => 999,
            'favoriteable_type' => User::class,
        ]);
        $favorite->setRelation('favoriteable', null);

        $this->assertInstanceOf(NullResource::class, $this->transformer->includeFavoritable($favorite));
    }

    public function test_include_favoritable_degrades_gracefully_when_no_transformer_registered(): void
    {
        $user     = User::factory()->create();
        $target   = User::factory()->create();
        $favorite = Favorite::factory()->forUser($user)->forFavoriteable($target, 'movie')->create()->load('favoriteable');

        $this->assertInstanceOf(NullResource::class, $this->transformer->includeFavoritable($favorite));
    }

    public function test_fractal_serializes_favorite_correctly(): void
    {
        $user     = User::factory()->create();
        $target   = Person::factory()->create();
        $favorite = Favorite::factory()->forUser($user)->forFavoriteable($target, 'person')->create();

        $output = $this->fractal
            ->createData(new Item($favorite->fresh(), $this->transformer))
            ->toArray();

        $this->assertArrayHasKey('data', $output);
        $this->assertSame($user->id, $output['data']['user_id']);
        $this->assertSame('person', $output['data']['favoritable_type']);
    }
}
