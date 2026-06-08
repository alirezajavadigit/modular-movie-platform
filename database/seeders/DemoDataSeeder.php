<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Article\Models\Article;
use Modules\Authorization\Models\Permission;
use Modules\Authorization\Models\Role;
use Modules\Auth\Models\User;
use Modules\Category\Models\Category;
use Modules\Discussion\Enums\DiscussionStatus;
use Modules\Movie\Enums\BadgeType;
use Modules\Movie\Enums\MovieType;
use Modules\Movie\Models\Episode;
use Modules\Movie\Models\Movie;
use Modules\Payment\Enums\PaymentStatus;
use Modules\Payment\Models\Payment;
use Modules\Person\Enums\CreditRole;
use Modules\Person\Enums\Gender;
use Modules\Person\Models\Person;
use Modules\Subscription\Enums\SubscriptionPlanStatus;
use Modules\Subscription\Enums\SubscriptionStatus;
use Modules\Subscription\Models\Subscription;
use Modules\Subscription\Models\SubscriptionPlan;
use Modules\Tag\Models\Tag;

class DemoDataSeeder extends Seeder
{
    private const ADMINS = 3;
    private const EDITORS = 8;
    private const USERS = 120;
    private const FILLER_PEOPLE = 150;
    private const FILLER_FILMS = 70;
    private const FILLER_SERIES = 12;
    private const ARTICLES = 60;
    private const SUBSCRIBERS = 90;

    private array $people = [];
    private array $genres = [];
    private array $tags = [];
    private array $movieIds = [];
    private array $episodeIds = [];
    private array $articleIds = [];
    private array $roles = [];

    public function run(): void
    {
        DB::transaction(function (): void {
            $this->seedRolesAndPermissions();
            $this->seedUsers();
            $this->seedPeople();
            $this->seedGenres();
            $this->seedTags();
            $this->seedFilms();
            $this->seedSeries();
            $this->seedArticles();
            $this->seedSubscriptionPlans();
            $this->seedSubscriptionsAndPayments();
            $this->seedEngagement();
            $this->seedDiscussions();
            $this->seedNotifications();
        });

        $this->seedAvatars();
    }

    private function bi(string $en, string $fa): array
    {
        return ['en' => $en, 'fa' => $fa];
    }

    private function persianBody(\Faker\Generator $faker): string
    {
        return implode("\n\n", [
            $faker->realText(220),
            $faker->realText(260),
            $faker->realText(200),
        ]);
    }

    private function genreKeywords(array $genres): string
    {
        $map = [
            'Action' => 'action,explosion,cinematic',
            'Adventure' => 'adventure,mountains,journey',
            'Animation' => 'animation,colorful,art',
            'Comedy' => 'fun,bright,colorful',
            'Crime' => 'noir,city,night',
            'Drama' => 'portrait,cinematic,moody',
            'Fantasy' => 'fantasy,castle,forest',
            'History' => 'vintage,historic,sepia',
            'Horror' => 'dark,fog,horror',
            'Mystery' => 'fog,shadow,mystery',
            'Romance' => 'couple,sunset,romance',
            'Sci-Fi' => 'space,futuristic,scifi',
            'Thriller' => 'dark,suspense,city',
            'War' => 'soldier,battlefield,smoke',
            'Documentary' => 'nature,landscape,wildlife',
            'Family' => 'family,home,warm',
            'Cyberpunk' => 'neon,cyberpunk,city',
            'Space Opera' => 'space,galaxy,stars',
            'Period Drama' => 'vintage,castle,period',
            'Dark Comedy' => 'quirky,moody,urban',
        ];

        return $map[$genres[0] ?? 'Drama'] ?? 'cinema,film';
    }

    private function posterUrl(array $genres, string $seed): string
    {
        $lock = abs(crc32($seed)) % 100000;

        return 'https://loremflickr.com/500/750/' . $this->genreKeywords($genres) . "?lock={$lock}";
    }

    private function trailer(): string
    {
        return 'https://www.youtube.com/watch?v=' . Str::lower(Str::random(11));
    }

    private function downloadLinks(string $key): array
    {
        return [
            "https://cdn.flixmovie.test/stream/{$key}/1080p.mp4",
            "https://cdn.flixmovie.test/stream/{$key}/720p.mp4",
            "https://cdn.flixmovie.test/stream/{$key}/480p.mp4",
        ];
    }

    private function seedRolesAndPermissions(): void
    {
        foreach (['Movie', 'Subscription', 'User'] as $module) {
            $class = "Modules\\{$module}\\Database\\Seeders\\{$module}PermissionSeeder";
            if (class_exists($class)) {
                $this->call($class);
            }
        }

        foreach (['super_admin', 'admin', 'editor', 'subscriber', 'user'] as $name) {
            $this->roles[$name] = Role::firstOrCreate(['name' => $name, 'guard_name' => 'api']);
        }

        $allPermissions = Permission::where('guard_name', 'api')->get();
        $this->roles['admin']->syncPermissions($allPermissions);

        $editorPermissions = $allPermissions->filter(
            fn(Permission $permission) => Str::startsWith(
                $permission->name,
                ['articles.', 'movies.', 'categories.', 'tags.', 'persons.', 'credits.', 'discussions.'],
            ),
        );
        $this->roles['editor']->syncPermissions($editorPermissions);
    }

    private function seedUsers(): void
    {
        $superAdmin = User::factory()->create([
            'name' => 'Alireza Javadi',
            'email' => 'admin@flixmovie.test',
            'phone' => '+989120000001',
        ]);
        $superAdmin->assignRole($this->roles['super_admin']);

        User::factory()->count(self::ADMINS)->create()->each(
            fn(User $user) => $user->assignRole($this->roles['admin']),
        );

        User::factory()->count(self::EDITORS)->create()->each(
            fn(User $user) => $user->assignRole($this->roles['editor']),
        );

        User::factory()->count(self::USERS)->create()->each(
            fn(User $user) => $user->assignRole($this->roles['user']),
        );
    }

    private function seedPeople(): void
    {
        $persian = $this->peoplePersian();

        foreach ($this->peopleData() as $slug => $data) {
            $fa = $persian[$slug] ?? [$data[0], $data[1], $data[5]];

            $person = Person::create([
                'first_name' => $this->bi($data[0], $fa[0]),
                'last_name' => $this->bi($data[1], $fa[1]),
                'slug' => $slug,
                'biography' => $this->bi(
                    $data[6] ?? "{$data[0]} {$data[1]} is an acclaimed figure in international cinema.",
                    "{$fa[0]} {$fa[1]} از چهره‌های شناخته‌شدهٔ سینمای جهان است.",
                ),
                'date_of_birth' => $data[4],
                'place_of_birth' => $this->bi($data[5], $fa[2]),
                'gender' => $data[2],
                'known_for_department' => $data[3],
                'popularity' => $data[7] ?? fake()->randomFloat(3, 20, 95),
                'is_active' => true,
            ]);

            $this->people[$slug] = $person->id;
        }

        $faker = fake('fa_IR');
        $departments = ['Acting', 'Directing', 'Writing', 'Production', 'Sound', 'Camera'];

        foreach (range(1, self::FILLER_PEOPLE) as $index) {
            $first = fake()->firstName();
            $last = fake()->lastName();

            Person::create([
                'first_name' => $this->bi($first, $faker->firstName()),
                'last_name' => $this->bi($last, $faker->lastName()),
                'slug' => Str::slug("{$first}-{$last}") . '-' . $index,
                'biography' => $this->bi(fake()->paragraph(), $faker->realText(160)),
                'date_of_birth' => fake()->dateTimeBetween('-75 years', '-20 years')->format('Y-m-d'),
                'place_of_birth' => $this->bi(fake()->city() . ', ' . fake()->country(), $faker->city()),
                'gender' => Arr::random(Gender::cases())->value,
                'known_for_department' => Arr::random($departments),
                'popularity' => fake()->randomFloat(3, 10, 90),
                'is_active' => fake()->boolean(90),
            ]);
        }
    }

    private function seedGenres(): void
    {
        $top = [
            'Action' => 'اکشن',
            'Adventure' => 'ماجراجویی',
            'Animation' => 'انیمیشن',
            'Comedy' => 'کمدی',
            'Crime' => 'جنایی',
            'Drama' => 'درام',
            'Fantasy' => 'فانتزی',
            'History' => 'تاریخی',
            'Horror' => 'ترسناک',
            'Mystery' => 'معمایی',
            'Romance' => 'عاشقانه',
            'Sci-Fi' => 'علمی‌تخیلی',
            'Thriller' => 'هیجان‌انگیز',
            'War' => 'جنگی',
            'Documentary' => 'مستند',
            'Family' => 'خانوادگی',
        ];

        $order = 1;
        foreach ($top as $en => $fa) {
            $slug = Str::slug($en);
            $category = Category::create([
                'name' => $this->bi($en, $fa),
                'slug' => $this->bi($slug, $slug),
                'description' => $this->bi(
                    "{$en} titles and everything related to the {$en} genre.",
                    "عناوین و محتوای مرتبط با ژانر {$fa}.",
                ),
                'is_active' => true,
                'order' => $order++,
            ]);
            $this->genres[$en] = $category->id;
        }

        $sub = [
            'Cyberpunk' => ['سایبرپانک', 'Sci-Fi'],
            'Space Opera' => ['اپرای فضایی', 'Sci-Fi'],
            'Period Drama' => ['درام تاریخی', 'Drama'],
            'Dark Comedy' => ['کمدی سیاه', 'Comedy'],
        ];

        foreach ($sub as $en => [$fa, $parent]) {
            $slug = Str::slug($en);
            $category = Category::create([
                'parent_id' => $this->genres[$parent],
                'name' => $this->bi($en, $fa),
                'slug' => $this->bi($slug, $slug),
                'description' => $this->bi(
                    "{$en}, a subgenre of {$parent}.",
                    "{$fa}، زیرژانری از {$top[$parent]}.",
                ),
                'is_active' => true,
                'order' => $order++,
            ]);
            $this->genres[$en] = $category->id;
        }
    }

    private function seedTags(): void
    {
        $tags = [
            'mind-bending' => ['Mind Bending', 'ذهن‌برانگیز'],
            'heist' => ['Heist', 'سرقت'],
            'space' => ['Space', 'فضایی'],
            'time-travel' => ['Time Travel', 'سفر در زمان'],
            'dystopia' => ['Dystopia', 'دیستوپیا'],
            'cult-classic' => ['Cult Classic', 'کالت کلاسیک'],
            'based-on-true-story' => ['Based on True Story', 'براساس داستان واقعی'],
            'nonlinear' => ['Nonlinear', 'غیرخطی'],
            'dark-comedy' => ['Dark Comedy', 'کمدی سیاه'],
            'coming-of-age' => ['Coming of Age', 'بلوغ'],
            'epic' => ['Epic', 'حماسی'],
            'twist-ending' => ['Twist Ending', 'پایان غافلگیرکننده'],
            'slow-burn' => ['Slow Burn', 'کند و عمیق'],
            'neo-noir' => ['Neo Noir', 'نئو-نوآر'],
            'satire' => ['Satire', 'طنز'],
            'post-apocalyptic' => ['Post Apocalyptic', 'پساآخرالزمانی'],
            'found-family' => ['Found Family', 'خانواده انتخابی'],
            'revenge' => ['Revenge', 'انتقام'],
            'courtroom' => ['Courtroom', 'دادگاهی'],
            'biographical' => ['Biographical', 'زندگی‌نامه‌ای'],
            'psychological' => ['Psychological', 'روان‌شناختی'],
            'survival' => ['Survival', 'بقا'],
            'period-piece' => ['Period Piece', 'دوره‌ای'],
            'ensemble-cast' => ['Ensemble Cast', 'بازیگران گروهی'],
        ];

        foreach ($tags as $slug => [$enLabel, $faLabel]) {
            $tag = Tag::create([
                'name' => $this->bi($enLabel, $faLabel),
                'slug' => $this->bi($slug, $slug),
                'description' => $this->bi(
                    "Titles tagged as {$enLabel}.",
                    "عناوین دارای برچسب «{$faLabel}».",
                ),
                'color' => fake()->hexColor(),
                'is_active' => true,
            ]);
            $this->tags[$slug] = $tag->id;
        }
    }

    private function seedFilms(): void
    {
        foreach ($this->filmData() as $film) {
            $key = Str::slug($film['title'] . '-' . $film['year']);

            $movie = Movie::create([
                'title' => $film['title'],
                'description' => $film['desc'],
                'poster' => $this->posterUrl($film['genres'] ?? [], $key),
                'trailer_url' => $this->trailer(),
                'download_links' => $this->downloadLinks($key),
                'release_year' => $film['year'],
                'country' => $film['country'],
                'language' => $film['lang'],
                'imdb_score' => $film['imdb'],
                'badge' => $film['badge'],
                'type' => MovieType::Movie->value,
            ]);

            $this->attachCredits($movie, $film);
            $this->attachTaxonomy($movie, $film['genres'] ?? [], $film['tags'] ?? []);
            $this->movieIds[] = $movie->id;
        }

        Movie::factory()
            ->count(self::FILLER_FILMS)
            ->state(['type' => MovieType::Movie->value])
            ->create()
            ->each(function (Movie $movie): void {
                $genreNames = (array) Arr::random(array_keys($this->genres), random_int(1, 3));
                $tagSlugs = (array) Arr::random(array_keys($this->tags), random_int(1, 3));
                $movie->update(['poster' => $this->posterUrl($genreNames, Str::slug($movie->title . '-' . $movie->release_year))]);
                $this->attachRandomCrew($movie);
                $this->attachTaxonomy($movie, $genreNames, $tagSlugs);
                $this->movieIds[] = $movie->id;
            });
    }

    private function seedSeries(): void
    {
        foreach ($this->seriesData() as $series) {
            $key = Str::slug($series['title']);

            $serial = Movie::create([
                'title' => $series['title'],
                'description' => $series['desc'],
                'poster' => $this->posterUrl($series['genres'] ?? [], $key),
                'trailer_url' => $this->trailer(),
                'download_links' => null,
                'release_year' => $series['year'],
                'country' => $series['country'],
                'language' => $series['lang'],
                'imdb_score' => $series['imdb'],
                'badge' => $series['badge'],
                'type' => MovieType::Serial->value,
            ]);

            $this->attachCredits($serial, $series);
            $this->attachTaxonomy($serial, $series['genres'] ?? [], $series['tags'] ?? []);
            $this->createEpisodes($serial, $series['seasons'], $series['genres'] ?? []);
            $this->movieIds[] = $serial->id;
        }

        Movie::factory()
            ->count(self::FILLER_SERIES)
            ->state(['type' => MovieType::Serial->value])
            ->create()
            ->each(function (Movie $serial): void {
                $genreNames = (array) Arr::random(array_keys($this->genres), random_int(1, 3));
                $tagSlugs = (array) Arr::random(array_keys($this->tags), random_int(1, 2));
                $serial->update(['poster' => $this->posterUrl($genreNames, Str::slug($serial->title . '-' . $serial->release_year))]);
                $this->attachRandomCrew($serial);
                $this->attachTaxonomy($serial, $genreNames, $tagSlugs);
                $seasons = [];
                foreach (range(1, random_int(1, 3)) as $season) {
                    $seasons[] = [$season, random_int(6, 12)];
                }
                $this->createEpisodes($serial, $seasons, $genreNames);
                $this->movieIds[] = $serial->id;
            });
    }

    private function createEpisodes(Movie $serial, array $seasons, array $genres = []): void
    {
        foreach ($seasons as [$seasonNumber, $count]) {
            foreach (range(1, $count) as $episodeNumber) {
                $key = Str::slug("{$serial->title}-s{$seasonNumber}-e{$episodeNumber}");
                $episode = Episode::create([
                    'movie_id' => $serial->id,
                    'season_number' => $seasonNumber,
                    'episode_number' => $episodeNumber,
                    'title' => Str::title(fake()->words(random_int(2, 4), true)),
                    'description' => fake()->paragraph(),
                    'poster' => $this->posterUrl($genres, $key),
                    'trailer_url' => $this->trailer(),
                    'download_links' => $this->downloadLinks($key),
                ]);
                $this->episodeIds[] = $episode->id;
            }
        }
    }

    private function attachCredits(Movie $movie, array $data): void
    {
        $rows = [];

        if (! empty($data['dir']) && isset($this->people[$data['dir']])) {
            $rows[] = [
                'person_id' => $this->people[$data['dir']],
                'role' => CreditRole::DIRECTOR->value,
                'department' => 'Directing',
                'order' => 0,
            ];
        }

        if (! empty($data['composer']) && isset($this->people[$data['composer']])) {
            $rows[] = [
                'person_id' => $this->people[$data['composer']],
                'role' => CreditRole::COMPOSER->value,
                'department' => 'Sound',
                'order' => 0,
            ];
        }

        $order = 1;
        foreach ($data['cast'] ?? [] as [$slug, $character]) {
            if (! isset($this->people[$slug])) {
                continue;
            }
            $rows[] = [
                'person_id' => $this->people[$slug],
                'role' => CreditRole::ACTOR->value,
                'character_name' => $character,
                'department' => 'Acting',
                'order' => $order++,
            ];
        }

        if ($rows !== []) {
            $movie->credits()->createMany($rows);
        }
    }

    private function attachRandomCrew(Movie $movie): void
    {
        $pool = array_merge(array_values($this->people), Person::query()->inRandomOrder()->limit(40)->pluck('id')->all());
        $pool = array_values(array_unique($pool));

        $director = Arr::random($pool);
        $castPool = array_values(array_diff($pool, [$director]));
        $cast = Arr::random($castPool, min(random_int(4, 8), count($castPool)));

        $rows = [[
            'person_id' => $director,
            'role' => CreditRole::DIRECTOR->value,
            'department' => 'Directing',
            'order' => 0,
        ]];

        $order = 1;
        foreach ((array) $cast as $personId) {
            $rows[] = [
                'person_id' => $personId,
                'role' => CreditRole::ACTOR->value,
                'character_name' => fake()->firstName(),
                'department' => 'Acting',
                'order' => $order++,
            ];
        }

        $movie->credits()->createMany($rows);
    }

    private function attachTaxonomy(Movie $movie, array $genreNames, array $tagSlugs): void
    {
        $now = now();
        $type = $movie->getMorphClass();

        $categoryRows = [];
        foreach (array_unique($genreNames) as $name) {
            if (! isset($this->genres[$name])) {
                continue;
            }
            $categoryRows[] = [
                'category_id' => $this->genres[$name],
                'categorizable_id' => $movie->id,
                'categorizable_type' => $type,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($categoryRows !== []) {
            DB::table('categorizables')->insert($categoryRows);
        }

        $tagRows = [];
        foreach (array_unique($tagSlugs) as $slug) {
            if (! isset($this->tags[$slug])) {
                continue;
            }
            $tagRows[] = [
                'tag_id' => $this->tags[$slug],
                'taggable_id' => $movie->id,
                'taggable_type' => $type,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }
        if ($tagRows !== []) {
            DB::table('taggables')->insert($tagRows);
        }
    }

    private function seedArticles(): void
    {
        $editors = User::role($this->roles['editor'])->pluck('id')->all();
        $headlines = $this->articleHeadlines();
        $faker = fake('fa_IR');
        $statuses = ['published', 'published', 'published', 'published', 'draft', 'review', 'archived'];

        foreach (range(1, self::ARTICLES) as $index) {
            if (isset($headlines[$index - 1])) {
                [$titleEn, $titleFa] = $headlines[$index - 1];
            } else {
                $titleEn = Str::title(fake()->unique()->words(random_int(4, 7), true));
                $titleFa = $faker->realText(random_int(30, 60));
            }

            $status = $statuses[array_rand($statuses)];
            $published = $status === 'published';
            $slug = Str::slug($titleEn) . '-' . $index;

            $article = Article::create([
                'user_id' => Arr::random($editors),
                'title' => $this->bi($titleEn, $titleFa),
                'slug' => $this->bi($slug, $slug),
                'summary' => $this->bi(fake()->sentence(14), $faker->realText(160)),
                'body' => $this->bi($this->articleBody($titleEn), $this->persianBody($faker)),
                'status' => $status,
                'read_time' => random_int(3, 18),
                'is_featured' => fake()->boolean(20),
                'allow_comments' => fake()->boolean(85),
                'published_at' => $published ? Carbon::now()->subDays(random_int(1, 540)) : null,
            ]);

            $article->categories()->attach(Arr::random(array_values($this->genres), random_int(1, 3)));
            $article->tags()->attach(Arr::random(array_values($this->tags), random_int(1, 4)));

            if ($published) {
                $this->articleIds[] = $article->id;
            }
        }
    }

    private function seedSubscriptionPlans(): void
    {
        $plans = [
            ['Basic', 'Standard definition on a single device.', 8.99, 30, SubscriptionPlanStatus::ACTIVE],
            ['Standard', 'Full HD on two devices at once.', 13.99, 30, SubscriptionPlanStatus::ACTIVE],
            ['Premium', '4K Ultra HD on four devices with offline downloads.', 19.99, 30, SubscriptionPlanStatus::ACTIVE],
            ['Premium Annual', 'A full year of Premium at two months free.', 199.99, 365, SubscriptionPlanStatus::ACTIVE],
            ['Legacy', 'Retired plan kept for existing members.', 6.99, 30, SubscriptionPlanStatus::INACTIVE],
        ];

        foreach ($plans as [$name, $description, $price, $days, $status]) {
            SubscriptionPlan::firstOrCreate(
                ['name' => $name],
                [
                    'description' => $description,
                    'price' => $price,
                    'duration_days' => $days,
                    'status' => $status->value,
                ],
            );
        }
    }

    private function seedSubscriptionsAndPayments(): void
    {
        $plans = SubscriptionPlan::where('status', SubscriptionPlanStatus::ACTIVE->value)->get();
        $subscribers = User::role($this->roles['user'])->inRandomOrder()->limit(self::SUBSCRIBERS)->get();
        $drivers = ['zarinpal', 'stripe', 'idpay', 'nextpay'];

        foreach ($subscribers as $user) {
            $plan = $plans->random();
            $startedAt = Carbon::now()->subDays(random_int(1, 320));
            $endsAt = (clone $startedAt)->addDays($plan->duration_days);

            $outcome = Arr::random(['active', 'active', 'active', 'expired', 'canceled', 'pending']);

            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'starts_at' => $startedAt,
                'ends_at' => $endsAt,
                'status' => SubscriptionStatus::PENDING->value,
            ]);

            $paymentStatus = $outcome === 'pending' ? PaymentStatus::PENDING : PaymentStatus::SUCCESS;

            $payment = Payment::create([
                'payable_id' => $subscription->id,
                'payable_type' => $subscription->getMorphClass(),
                'user_id' => $user->id,
                'amount' => $plan->price,
                'driver' => Arr::random($drivers),
                'transaction_id' => $paymentStatus === PaymentStatus::SUCCESS ? Str::upper(Str::random(18)) : null,
                'status' => $paymentStatus->value,
            ]);

            $status = match ($outcome) {
                'expired' => SubscriptionStatus::EXPIRED,
                'canceled' => SubscriptionStatus::CANCELED,
                'pending' => SubscriptionStatus::PENDING,
                default => SubscriptionStatus::ACTIVE,
            };

            if ($outcome === 'expired') {
                $subscription->ends_at = Carbon::now()->subDays(random_int(1, 60));
            }

            $subscription->payment_id = $payment->id;
            $subscription->status = $status->value;
            $subscription->save();

            if ($status === SubscriptionStatus::ACTIVE) {
                $user->assignRole($this->roles['subscriber']);
            }
        }

        $this->seedFailedPayments();
    }

    private function seedFailedPayments(): void
    {
        $users = User::role($this->roles['user'])->inRandomOrder()->limit(20)->get();
        $plans = SubscriptionPlan::where('status', SubscriptionPlanStatus::ACTIVE->value)->get();
        $drivers = ['zarinpal', 'stripe', 'idpay', 'nextpay'];

        foreach ($users as $user) {
            $plan = $plans->random();
            $subscription = Subscription::create([
                'user_id' => $user->id,
                'plan_id' => $plan->id,
                'starts_at' => null,
                'ends_at' => null,
                'status' => SubscriptionStatus::PENDING->value,
            ]);

            Payment::create([
                'payable_id' => $subscription->id,
                'payable_type' => $subscription->getMorphClass(),
                'user_id' => $user->id,
                'amount' => $plan->price,
                'driver' => Arr::random($drivers),
                'transaction_id' => null,
                'status' => Arr::random([PaymentStatus::FAILED, PaymentStatus::CANCELED])->value,
            ]);
        }
    }

    private function seedEngagement(): void
    {
        $now = now();
        $userIds = User::pluck('id')->all();

        $likeTargets = $this->morphTargets();
        $favoriteTargets = $this->morphTargets(includeEpisodes: false);

        $likeRows = [];
        $favoriteRows = [];

        foreach ($userIds as $userId) {
            $createdAt = Carbon::now()->subDays(random_int(0, 300));

            foreach ($this->sampleTargets($likeTargets, random_int(5, 25)) as [$type, $id]) {
                $likeRows[] = [
                    'user_id' => $userId,
                    'likeable_type' => $type,
                    'likeable_id' => $id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }

            foreach ($this->sampleTargets($favoriteTargets, random_int(3, 15)) as [$type, $id]) {
                $favoriteRows[] = [
                    'user_id' => $userId,
                    'favoriteable_type' => $type,
                    'favoriteable_id' => $id,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }
        }

        foreach (array_chunk($likeRows, 1000) as $chunk) {
            DB::table('likes')->insert($chunk);
        }
        foreach (array_chunk($favoriteRows, 1000) as $chunk) {
            DB::table('favorites')->insert($chunk);
        }
    }

    private function morphTargets(bool $includeEpisodes = true): array
    {
        $movieType = \Modules\Movie\Models\Movie::class;
        $articleType = \Modules\Article\Models\Article::class;
        $episodeType = \Modules\Movie\Models\Episode::class;

        $targets = [];
        foreach ($this->movieIds as $id) {
            $targets[] = [$movieType, $id];
        }
        foreach ($this->articleIds as $id) {
            $targets[] = [$articleType, $id];
        }
        if ($includeEpisodes) {
            foreach ($this->episodeIds as $id) {
                $targets[] = [$episodeType, $id];
            }
        }

        return $targets;
    }

    private function sampleTargets(array $targets, int $count): array
    {
        if ($targets === []) {
            return [];
        }

        $count = min($count, count($targets));
        $keys = (array) array_rand($targets, $count);

        return array_map(fn($key) => $targets[$key], $keys);
    }

    private function seedDiscussions(): void
    {
        $userIds = User::role($this->roles['user'])->pluck('id')->all();
        $movieType = \Modules\Movie\Models\Movie::class;
        $articleType = \Modules\Article\Models\Article::class;

        $threads = [];
        foreach ($this->movieIds as $id) {
            $threads[] = [$movieType, $id];
        }
        foreach ($this->articleIds as $id) {
            $threads[] = [$articleType, $id];
        }

        $statusPool = array_merge(
            array_fill(0, 8, DiscussionStatus::APPROVED->value),
            array_fill(0, 2, DiscussionStatus::PENDING->value),
            [DiscussionStatus::REJECTED->value],
        );

        $parents = [];
        foreach ($threads as [$type, $id]) {
            foreach (range(0, random_int(0, 6)) as $ignored) {
                $createdAt = Carbon::now()->subDays(random_int(0, 280))->subHours(random_int(0, 23));
                $parents[] = [
                    'user_id' => Arr::random($userIds),
                    'parent_id' => null,
                    'discussionable_type' => $type,
                    'discussionable_id' => $id,
                    'body' => Arr::random($this->commentBodies()),
                    'status' => Arr::random($statusPool),
                    'ip_address' => fake()->ipv4(),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }
        }

        foreach (array_chunk($parents, 1000) as $chunk) {
            DB::table('discussions')->insert($chunk);
        }

        $approvedParents = DB::table('discussions')
            ->whereNull('parent_id')
            ->where('status', DiscussionStatus::APPROVED->value)
            ->get(['id', 'discussionable_type', 'discussionable_id']);

        $replies = [];
        foreach ($approvedParents as $parent) {
            if (! fake()->boolean(45)) {
                continue;
            }
            foreach (range(1, random_int(1, 3)) as $ignored) {
                $createdAt = Carbon::now()->subDays(random_int(0, 200));
                $replies[] = [
                    'user_id' => Arr::random($userIds),
                    'parent_id' => $parent->id,
                    'discussionable_type' => $parent->discussionable_type,
                    'discussionable_id' => $parent->discussionable_id,
                    'body' => Arr::random($this->replyBodies()),
                    'status' => DiscussionStatus::APPROVED->value,
                    'ip_address' => fake()->ipv4(),
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }
        }

        foreach (array_chunk($replies, 1000) as $chunk) {
            DB::table('discussions')->insert($chunk);
        }
    }

    private function seedNotifications(): void
    {
        $users = User::all();
        $userType = (new User())->getMorphClass();
        $rows = [];

        $templates = [
            ['welcome', 'database', 'Welcome to FlixMovie! Start exploring trending titles now.'],
            ['subscription.activated', 'email', 'Your subscription is active. Enjoy unlimited streaming.'],
            ['subscription.expiring', 'email', 'Your plan expires soon. Renew to keep watching.'],
            ['comment.replied', 'database', 'Someone replied to your comment.'],
            ['new.release', 'push', 'A new release just landed in your favorite genre.'],
            ['payment.received', 'database', 'We received your payment. Thank you!'],
        ];

        foreach ($users as $user) {
            foreach (range(1, random_int(2, 7)) as $ignored) {
                [$type, $channel, $message] = Arr::random($templates);
                $createdAt = Carbon::now()->subDays(random_int(0, 120));
                $read = fake()->boolean(55);

                $rows[] = [
                    'notifiable_id' => $user->id,
                    'notifiable_type' => $userType,
                    'type' => $type,
                    'channel' => $channel,
                    'data' => json_encode(['message' => $message, 'url' => '/notifications']),
                    'read_at' => $read ? $createdAt->copy()->addHours(random_int(1, 48)) : null,
                    'sent_at' => $createdAt,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ];
            }
        }

        foreach (array_chunk($rows, 1000) as $chunk) {
            DB::table('notifications')->insert($chunk);
        }
    }

    private function seedAvatars(): void
    {
        $counters = ['men' => 0, 'women' => 0];

        Person::query()->orderBy('id')->each(function (Person $person) use (&$counters): void {
            $bucket = $this->avatarBucket($person->gender);
            $index = $counters[$bucket] % 100;
            $counters[$bucket]++;

            try {
                $person->addMediaFromUrl("https://randomuser.me/api/portraits/{$bucket}/{$index}.jpg")
                    ->usingFileName("{$person->slug}.jpg")
                    ->toMediaCollection('avatar');
            } catch (\Throwable) {
            }
        });
    }

    private function avatarBucket(?Gender $gender): string
    {
        return match ($gender) {
            Gender::FEMALE => 'women',
            Gender::MALE => 'men',
            default => Arr::random(['men', 'women']),
        };
    }

    private function peopleData(): array
    {
        return [
            'christopher-nolan' => ['Christopher', 'Nolan', Gender::MALE->value, 'Directing', '1970-07-30', 'London, United Kingdom', null, 92.4],
            'leonardo-dicaprio' => ['Leonardo', 'DiCaprio', Gender::MALE->value, 'Acting', '1974-11-11', 'Los Angeles, United States', null, 95.1],
            'cillian-murphy' => ['Cillian', 'Murphy', Gender::MALE->value, 'Acting', '1976-05-25', 'Douglas, Ireland', null, 88.7],
            'tom-hardy' => ['Tom', 'Hardy', Gender::MALE->value, 'Acting', '1977-09-15', 'Hammersmith, United Kingdom', null, 84.0],
            'anne-hathaway' => ['Anne', 'Hathaway', Gender::FEMALE->value, 'Acting', '1982-11-12', 'New York, United States', null, 83.2],
            'matthew-mcconaughey' => ['Matthew', 'McConaughey', Gender::MALE->value, 'Acting', '1969-11-04', 'Uvalde, United States', null, 80.5],
            'jessica-chastain' => ['Jessica', 'Chastain', Gender::FEMALE->value, 'Acting', '1977-03-24', 'Sacramento, United States', null, 79.1],
            'hans-zimmer' => ['Hans', 'Zimmer', Gender::MALE->value, 'Sound', '1957-09-12', 'Frankfurt, Germany', null, 76.3],
            'denis-villeneuve' => ['Denis', 'Villeneuve', Gender::MALE->value, 'Directing', '1967-10-03', 'Trois-Rivières, Canada', null, 85.6],
            'timothee-chalamet' => ['Timothée', 'Chalamet', Gender::MALE->value, 'Acting', '1995-12-27', 'New York, United States', null, 90.2],
            'zendaya' => ['Zendaya', 'Coleman', Gender::FEMALE->value, 'Acting', '1996-09-01', 'Oakland, United States', null, 91.0],
            'rebecca-ferguson' => ['Rebecca', 'Ferguson', Gender::FEMALE->value, 'Acting', '1983-10-19', 'Stockholm, Sweden', null, 77.4],
            'bong-joon-ho' => ['Bong', 'Joon-ho', Gender::MALE->value, 'Directing', '1969-09-14', 'Daegu, South Korea', null, 82.9],
            'song-kang-ho' => ['Song', 'Kang-ho', Gender::MALE->value, 'Acting', '1967-01-17', 'Gimhae, South Korea', null, 74.8],
            'greta-gerwig' => ['Greta', 'Gerwig', Gender::FEMALE->value, 'Directing', '1983-08-04', 'Sacramento, United States', null, 81.7],
            'margot-robbie' => ['Margot', 'Robbie', Gender::FEMALE->value, 'Acting', '1990-07-02', 'Dalby, Australia', null, 89.3],
            'ryan-gosling' => ['Ryan', 'Gosling', Gender::MALE->value, 'Acting', '1980-11-12', 'London, Canada', null, 86.9],
            'quentin-tarantino' => ['Quentin', 'Tarantino', Gender::MALE->value, 'Directing', '1963-03-27', 'Knoxville, United States', null, 84.5],
            'brad-pitt' => ['Brad', 'Pitt', Gender::MALE->value, 'Acting', '1963-12-18', 'Shawnee, United States', null, 87.6],
            'samuel-l-jackson' => ['Samuel', 'L. Jackson', Gender::MALE->value, 'Acting', '1948-12-21', 'Washington, United States', null, 82.1],
            'uma-thurman' => ['Uma', 'Thurman', Gender::FEMALE->value, 'Acting', '1970-04-29', 'Boston, United States', null, 71.2],
            'martin-scorsese' => ['Martin', 'Scorsese', Gender::MALE->value, 'Directing', '1942-11-17', 'New York, United States', null, 88.0],
            'robert-de-niro' => ['Robert', 'De Niro', Gender::MALE->value, 'Acting', '1943-08-17', 'New York, United States', null, 85.4],
            'joaquin-phoenix' => ['Joaquin', 'Phoenix', Gender::MALE->value, 'Acting', '1974-10-28', 'San Juan, Puerto Rico', null, 83.8],
            'frances-mcdormand' => ['Frances', 'McDormand', Gender::FEMALE->value, 'Acting', '1957-06-23', 'Chicago, United States', null, 70.6],
            'denzel-washington' => ['Denzel', 'Washington', Gender::MALE->value, 'Acting', '1954-12-28', 'Mount Vernon, United States', null, 84.2],
            'viola-davis' => ['Viola', 'Davis', Gender::FEMALE->value, 'Acting', '1965-08-11', 'St. Matthews, United States', null, 79.9],
            'christopher-mcquarrie' => ['Christopher', 'McQuarrie', Gender::MALE->value, 'Directing', '1968-10-12', 'Princeton, United States', null, 72.3],
            'tom-cruise' => ['Tom', 'Cruise', Gender::MALE->value, 'Acting', '1962-07-03', 'Syracuse, United States', null, 90.8],
            'hayao-miyazaki' => ['Hayao', 'Miyazaki', Gender::MALE->value, 'Directing', '1941-01-05', 'Tokyo, Japan', null, 86.1],
            'guillermo-del-toro' => ['Guillermo', 'del Toro', Gender::MALE->value, 'Directing', '1964-10-09', 'Guadalajara, Mexico', null, 81.0],
            'sofia-coppola' => ['Sofia', 'Coppola', Gender::FEMALE->value, 'Directing', '1971-05-14', 'New York, United States', null, 73.5],
            'bill-murray' => ['Bill', 'Murray', Gender::MALE->value, 'Acting', '1950-09-21', 'Evanston, United States', null, 78.4],
            'scarlett-johansson' => ['Scarlett', 'Johansson', Gender::FEMALE->value, 'Acting', '1984-11-22', 'New York, United States', null, 90.0],
            'david-fincher' => ['David', 'Fincher', Gender::MALE->value, 'Directing', '1962-08-28', 'Denver, United States', null, 83.3],
            'edward-norton' => ['Edward', 'Norton', Gender::MALE->value, 'Acting', '1969-08-18', 'Boston, United States', null, 76.0],
            'helena-bonham-carter' => ['Helena', 'Bonham Carter', Gender::FEMALE->value, 'Acting', '1966-05-26', 'London, United Kingdom', null, 74.1],
            'pedro-pascal' => ['Pedro', 'Pascal', Gender::MALE->value, 'Acting', '1975-04-02', 'Santiago, Chile', null, 92.7],
            'bella-ramsey' => ['Bella', 'Ramsey', Gender::NON_BINARY->value, 'Acting', '2003-09-30', 'Nottingham, United Kingdom', null, 75.8],
            'vince-gilligan' => ['Vince', 'Gilligan', Gender::MALE->value, 'Directing', '1967-02-10', 'Richmond, United States', null, 77.0],
            'bryan-cranston' => ['Bryan', 'Cranston', Gender::MALE->value, 'Acting', '1956-03-07', 'Hollywood, United States', null, 84.9],
            'aaron-paul' => ['Aaron', 'Paul', Gender::MALE->value, 'Acting', '1979-08-27', 'Emmett, United States', null, 78.2],
            'emilia-clarke' => ['Emilia', 'Clarke', Gender::FEMALE->value, 'Acting', '1986-10-23', 'London, United Kingdom', null, 85.0],
            'peter-dinklage' => ['Peter', 'Dinklage', Gender::MALE->value, 'Acting', '1969-06-11', 'Morristown, United States', null, 83.1],
            'millie-bobby-brown' => ['Millie', 'Bobby Brown', Gender::FEMALE->value, 'Acting', '2004-02-19', 'Marbella, Spain', null, 88.4],
        ];
    }

    private function peoplePersian(): array
    {
        return [
            'christopher-nolan' => ['کریستوفر', 'نولان', 'لندن، بریتانیا'],
            'leonardo-dicaprio' => ['لئوناردو', 'دی‌کاپریو', 'لس‌آنجلس، آمریکا'],
            'cillian-murphy' => ['کیلیان', 'مورفی', 'داگلاس، ایرلند'],
            'tom-hardy' => ['تام', 'هاردی', 'هامرسمیت، بریتانیا'],
            'anne-hathaway' => ['آن', 'هاتاوی', 'نیویورک، آمریکا'],
            'matthew-mcconaughey' => ['متیو', 'مک‌کانهی', 'یووالدی، آمریکا'],
            'jessica-chastain' => ['جسیکا', 'چستین', 'ساکرامنتو، آمریکا'],
            'hans-zimmer' => ['هانس', 'زیمر', 'فرانکفورت، آلمان'],
            'denis-villeneuve' => ['دنی', 'ویلنوو', 'تروا-ریویر، کانادا'],
            'timothee-chalamet' => ['تیموتی', 'شالامه', 'نیویورک، آمریکا'],
            'zendaya' => ['زندایا', 'کلمن', 'اوکلند، آمریکا'],
            'rebecca-ferguson' => ['ربکا', 'فرگوسن', 'استکهلم، سوئد'],
            'bong-joon-ho' => ['بونگ', 'جون-هو', 'دائگو، کره جنوبی'],
            'song-kang-ho' => ['سونگ', 'کانگ-هو', 'گیمهه، کره جنوبی'],
            'greta-gerwig' => ['گرتا', 'گرویگ', 'ساکرامنتو، آمریکا'],
            'margot-robbie' => ['مارگو', 'رابی', 'دالبی، استرالیا'],
            'ryan-gosling' => ['رایان', 'گاسلینگ', 'لندن، کانادا'],
            'quentin-tarantino' => ['کوئنتین', 'تارانتینو', 'ناکسویل، آمریکا'],
            'brad-pitt' => ['برد', 'پیت', 'شاونی، آمریکا'],
            'samuel-l-jackson' => ['ساموئل', 'ال. جکسون', 'واشنگتن، آمریکا'],
            'uma-thurman' => ['اوما', 'تورمن', 'بوستون، آمریکا'],
            'martin-scorsese' => ['مارتین', 'اسکورسیزی', 'نیویورک، آمریکا'],
            'robert-de-niro' => ['رابرت', 'دنیرو', 'نیویورک، آمریکا'],
            'joaquin-phoenix' => ['واکین', 'فینیکس', 'سن‌خوان، پورتوریکو'],
            'frances-mcdormand' => ['فرانسیس', 'مک‌دورمند', 'شیکاگو، آمریکا'],
            'denzel-washington' => ['دنزل', 'واشینگتن', 'مانت ورنون، آمریکا'],
            'viola-davis' => ['ویولا', 'دیویس', 'سنت متیوز، آمریکا'],
            'christopher-mcquarrie' => ['کریستوفر', 'مک‌کواری', 'پرینستون، آمریکا'],
            'tom-cruise' => ['تام', 'کروز', 'سیراکیوز، آمریکا'],
            'hayao-miyazaki' => ['هایائو', 'میازاکی', 'توکیو، ژاپن'],
            'guillermo-del-toro' => ['گی‌یرمو', 'دل تورو', 'گوادالاخارا، مکزیک'],
            'sofia-coppola' => ['سوفیا', 'کوپولا', 'نیویورک، آمریکا'],
            'bill-murray' => ['بیل', 'موری', 'اوانستون، آمریکا'],
            'scarlett-johansson' => ['اسکارلت', 'جوهانسون', 'نیویورک، آمریکا'],
            'david-fincher' => ['دیوید', 'فینچر', 'دنور، آمریکا'],
            'edward-norton' => ['ادوارد', 'نورتون', 'بوستون، آمریکا'],
            'helena-bonham-carter' => ['هلنا', 'بونهام کارتر', 'لندن، بریتانیا'],
            'pedro-pascal' => ['پدرو', 'پاسکال', 'سانتیاگو، شیلی'],
            'bella-ramsey' => ['بلا', 'رمزی', 'ناتینگهام، بریتانیا'],
            'vince-gilligan' => ['وینس', 'گیلیگان', 'ریچموند، آمریکا'],
            'bryan-cranston' => ['برایان', 'کرنستون', 'هالیوود، آمریکا'],
            'aaron-paul' => ['آرون', 'پاول', 'امت، آمریکا'],
            'emilia-clarke' => ['امیلیا', 'کلارک', 'لندن، بریتانیا'],
            'peter-dinklage' => ['پیتر', 'دینکلیج', 'موریستاون، آمریکا'],
            'millie-bobby-brown' => ['میلی', 'بابی براون', 'ماربلا، اسپانیا'],
        ];
    }

    private function filmData(): array
    {
        return [
            ['title' => 'Inception', 'year' => 2010, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.8, 'badge' => BadgeType::Subtitled->value, 'dir' => 'christopher-nolan', 'composer' => 'hans-zimmer', 'cast' => [['leonardo-dicaprio', 'Dom Cobb'], ['tom-hardy', 'Eames'], ['cillian-murphy', 'Robert Fischer']], 'genres' => ['Sci-Fi', 'Thriller', 'Action'], 'tags' => ['mind-bending', 'heist', 'twist-ending'], 'desc' => 'A thief who steals secrets from dreams takes on one last job: planting an idea instead of stealing one.'],
            ['title' => 'Interstellar', 'year' => 2014, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.7, 'badge' => BadgeType::Subtitled->value, 'dir' => 'christopher-nolan', 'composer' => 'hans-zimmer', 'cast' => [['matthew-mcconaughey', 'Cooper'], ['anne-hathaway', 'Brand'], ['jessica-chastain', 'Murph']], 'genres' => ['Sci-Fi', 'Drama', 'Adventure'], 'tags' => ['space', 'time-travel', 'epic'], 'desc' => 'With Earth dying, a former pilot leads a mission through a wormhole to find humanity a new home.'],
            ['title' => 'Oppenheimer', 'year' => 2023, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.3, 'badge' => BadgeType::Subtitled->value, 'dir' => 'christopher-nolan', 'cast' => [['cillian-murphy', 'J. Robert Oppenheimer']], 'genres' => ['Drama', 'History'], 'tags' => ['based-on-true-story', 'biographical'], 'desc' => 'The story of the physicist whose work on the atomic bomb reshaped the twentieth century.'],
            ['title' => 'Dune', 'year' => 2021, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.0, 'badge' => BadgeType::Subtitled->value, 'dir' => 'denis-villeneuve', 'composer' => 'hans-zimmer', 'cast' => [['timothee-chalamet', 'Paul Atreides'], ['zendaya', 'Chani'], ['rebecca-ferguson', 'Lady Jessica']], 'genres' => ['Sci-Fi', 'Adventure', 'Space Opera'], 'tags' => ['epic', 'space'], 'desc' => 'A young heir is thrust into a brutal struggle over the most valuable resource in the galaxy.'],
            ['title' => 'Dune: Part Two', 'year' => 2024, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.5, 'badge' => BadgeType::Subtitled->value, 'dir' => 'denis-villeneuve', 'composer' => 'hans-zimmer', 'cast' => [['timothee-chalamet', 'Paul Atreides'], ['zendaya', 'Chani'], ['rebecca-ferguson', 'Lady Jessica']], 'genres' => ['Sci-Fi', 'Adventure', 'Space Opera'], 'tags' => ['epic', 'space', 'revenge'], 'desc' => 'Paul unites with the desert people to wage war against those who destroyed his family.'],
            ['title' => 'Blade Runner 2049', 'year' => 2017, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.0, 'badge' => BadgeType::Subtitled->value, 'dir' => 'denis-villeneuve', 'composer' => 'hans-zimmer', 'cast' => [['ryan-gosling', 'K']], 'genres' => ['Sci-Fi', 'Thriller', 'Cyberpunk'], 'tags' => ['neo-noir', 'dystopia', 'slow-burn'], 'desc' => 'A new blade runner unearths a secret that could shatter what remains of society.'],
            ['title' => 'Parasite', 'year' => 2019, 'country' => 'South Korea', 'lang' => 'Korean', 'imdb' => 8.5, 'badge' => BadgeType::Subtitled->value, 'dir' => 'bong-joon-ho', 'cast' => [['song-kang-ho', 'Ki-taek']], 'genres' => ['Drama', 'Thriller', 'Dark Comedy'], 'tags' => ['dark-comedy', 'twist-ending'], 'desc' => 'A poor family schemes its way into the lives of a wealthy household, with explosive results.'],
            ['title' => 'Barbie', 'year' => 2023, 'country' => 'United States', 'lang' => 'English', 'imdb' => 6.8, 'badge' => BadgeType::Dubbed->value, 'dir' => 'greta-gerwig', 'cast' => [['margot-robbie', 'Barbie'], ['ryan-gosling', 'Ken']], 'genres' => ['Comedy', 'Fantasy'], 'tags' => ['satire'], 'desc' => 'A doll questions her perfect world and ventures into reality to discover who she really is.'],
            ['title' => 'Little Women', 'year' => 2019, 'country' => 'United States', 'lang' => 'English', 'imdb' => 7.8, 'badge' => BadgeType::Subtitled->value, 'dir' => 'greta-gerwig', 'cast' => [['timothee-chalamet', 'Laurie']], 'genres' => ['Drama', 'Romance', 'Period Drama'], 'tags' => ['coming-of-age', 'period-piece'], 'desc' => 'Four sisters come of age in the aftermath of war, chasing love, art, and independence.'],
            ['title' => 'Pulp Fiction', 'year' => 1994, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.9, 'badge' => BadgeType::Subtitled->value, 'dir' => 'quentin-tarantino', 'cast' => [['samuel-l-jackson', 'Jules Winnfield'], ['uma-thurman', 'Mia Wallace']], 'genres' => ['Crime', 'Drama'], 'tags' => ['cult-classic', 'nonlinear'], 'desc' => 'The lives of hit men, a boxer, and a gangster\'s wife collide across interwoven Los Angeles stories.'],
            ['title' => 'Once Upon a Time in Hollywood', 'year' => 2019, 'country' => 'United States', 'lang' => 'English', 'imdb' => 7.6, 'badge' => BadgeType::Subtitled->value, 'dir' => 'quentin-tarantino', 'cast' => [['leonardo-dicaprio', 'Rick Dalton'], ['brad-pitt', 'Cliff Booth'], ['margot-robbie', 'Sharon Tate']], 'genres' => ['Comedy', 'Drama'], 'tags' => ['period-piece', 'ensemble-cast'], 'desc' => 'A fading television star and his stunt double navigate a changing Hollywood at the end of an era.'],
            ['title' => 'Kill Bill: Vol. 1', 'year' => 2003, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.2, 'badge' => BadgeType::Subtitled->value, 'dir' => 'quentin-tarantino', 'cast' => [['uma-thurman', 'The Bride']], 'genres' => ['Action', 'Crime', 'Thriller'], 'tags' => ['revenge', 'cult-classic'], 'desc' => 'A betrayed assassin awakens from a coma and sets out to settle the score one name at a time.'],
            ['title' => 'The Wolf of Wall Street', 'year' => 2013, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.2, 'badge' => BadgeType::Subtitled->value, 'dir' => 'martin-scorsese', 'cast' => [['leonardo-dicaprio', 'Jordan Belfort'], ['margot-robbie', 'Naomi']], 'genres' => ['Comedy', 'Crime', 'Drama'], 'tags' => ['based-on-true-story', 'biographical'], 'desc' => 'A stockbroker\'s rise to obscene wealth collapses under fraud, excess, and federal scrutiny.'],
            ['title' => 'Killers of the Flower Moon', 'year' => 2023, 'country' => 'United States', 'lang' => 'English', 'imdb' => 7.6, 'badge' => BadgeType::Subtitled->value, 'dir' => 'martin-scorsese', 'cast' => [['leonardo-dicaprio', 'Ernest Burkhart'], ['robert-de-niro', 'William Hale']], 'genres' => ['Crime', 'Drama', 'History'], 'tags' => ['based-on-true-story', 'slow-burn'], 'desc' => 'A string of murders targets a Native nation made rich by oil, drawing in the early FBI.'],
            ['title' => 'Taxi Driver', 'year' => 1976, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.2, 'badge' => BadgeType::Subtitled->value, 'dir' => 'martin-scorsese', 'cast' => [['robert-de-niro', 'Travis Bickle']], 'genres' => ['Crime', 'Drama'], 'tags' => ['neo-noir', 'psychological'], 'desc' => 'A lonely night-shift cabbie spirals toward violence in a city he can no longer stand.'],
            ['title' => 'The Departed', 'year' => 2006, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.5, 'badge' => BadgeType::Subtitled->value, 'dir' => 'martin-scorsese', 'cast' => [['leonardo-dicaprio', 'Billy Costigan']], 'genres' => ['Crime', 'Drama', 'Thriller'], 'tags' => ['twist-ending', 'neo-noir'], 'desc' => 'An undercover cop and a mole inside the police race to expose each other first.'],
            ['title' => 'Lost in Translation', 'year' => 2003, 'country' => 'United States', 'lang' => 'English', 'imdb' => 7.7, 'badge' => BadgeType::Subtitled->value, 'dir' => 'sofia-coppola', 'cast' => [['bill-murray', 'Bob Harris'], ['scarlett-johansson', 'Charlotte']], 'genres' => ['Comedy', 'Drama', 'Romance'], 'tags' => ['slow-burn'], 'desc' => 'Two strangers adrift in Tokyo form an unlikely bond over a handful of sleepless nights.'],
            ['title' => 'Fight Club', 'year' => 1999, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.8, 'badge' => BadgeType::Subtitled->value, 'dir' => 'david-fincher', 'cast' => [['brad-pitt', 'Tyler Durden'], ['edward-norton', 'The Narrator'], ['helena-bonham-carter', 'Marla Singer']], 'genres' => ['Drama', 'Thriller'], 'tags' => ['cult-classic', 'twist-ending', 'psychological'], 'desc' => 'An insomniac office worker and a soap maker start a club that spirals beyond their control.'],
            ['title' => 'Se7en', 'year' => 1995, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.6, 'badge' => BadgeType::Subtitled->value, 'dir' => 'david-fincher', 'cast' => [['brad-pitt', 'Detective Mills']], 'genres' => ['Crime', 'Drama', 'Mystery'], 'tags' => ['neo-noir', 'twist-ending'], 'desc' => 'Two detectives hunt a killer who stages his murders around the seven deadly sins.'],
            ['title' => 'Spirited Away', 'year' => 2001, 'country' => 'Japan', 'lang' => 'Japanese', 'imdb' => 8.6, 'badge' => BadgeType::Animation->value, 'dir' => 'hayao-miyazaki', 'genres' => ['Animation', 'Fantasy', 'Adventure'], 'tags' => ['coming-of-age'], 'desc' => 'A girl wanders into a spirit world and must work to free her parents and find her way home.'],
            ['title' => 'Princess Mononoke', 'year' => 1997, 'country' => 'Japan', 'lang' => 'Japanese', 'imdb' => 8.4, 'badge' => BadgeType::Animation->value, 'dir' => 'hayao-miyazaki', 'genres' => ['Animation', 'Fantasy', 'Adventure'], 'tags' => ['epic'], 'desc' => 'Caught between forest gods and human industry, a cursed prince searches for an impossible peace.'],
            ['title' => 'Pan\'s Labyrinth', 'year' => 2006, 'country' => 'Mexico', 'lang' => 'Spanish', 'imdb' => 8.2, 'badge' => BadgeType::Subtitled->value, 'dir' => 'guillermo-del-toro', 'genres' => ['Drama', 'Fantasy', 'War'], 'tags' => ['period-piece', 'psychological'], 'desc' => 'A lonely girl escapes a brutal postwar reality into a dark and dangerous fairy tale.'],
            ['title' => 'Mission: Impossible - Dead Reckoning', 'year' => 2023, 'country' => 'United States', 'lang' => 'English', 'imdb' => 7.7, 'badge' => BadgeType::Dubbed->value, 'dir' => 'christopher-mcquarrie', 'cast' => [['tom-cruise', 'Ethan Hunt'], ['rebecca-ferguson', 'Ilsa Faust']], 'genres' => ['Action', 'Adventure', 'Thriller'], 'tags' => ['ensemble-cast'], 'desc' => 'A rogue artificial intelligence forces a veteran agent into his most personal mission yet.'],
            ['title' => 'Joker', 'year' => 2019, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.4, 'badge' => BadgeType::Subtitled->value, 'dir' => 'martin-scorsese', 'cast' => [['joaquin-phoenix', 'Arthur Fleck']], 'genres' => ['Crime', 'Drama', 'Thriller'], 'tags' => ['psychological', 'neo-noir'], 'desc' => 'A failing comedian is pushed to the edge by a cruel city until something inside him breaks.'],
        ];
    }

    private function seriesData(): array
    {
        return [
            ['title' => 'Breaking Bad', 'year' => 2008, 'country' => 'United States', 'lang' => 'English', 'imdb' => 9.5, 'badge' => BadgeType::Subtitled->value, 'dir' => 'vince-gilligan', 'cast' => [['bryan-cranston', 'Walter White'], ['aaron-paul', 'Jesse Pinkman']], 'genres' => ['Crime', 'Drama', 'Thriller'], 'tags' => ['slow-burn', 'psychological'], 'seasons' => [[1, 7], [2, 13], [3, 13], [4, 13], [5, 16]], 'desc' => 'A chemistry teacher turned meth cook descends from desperation into ruthless ambition.'],
            ['title' => 'Game of Thrones', 'year' => 2011, 'country' => 'United States', 'lang' => 'English', 'imdb' => 9.2, 'badge' => BadgeType::Subtitled->value, 'cast' => [['emilia-clarke', 'Daenerys Targaryen'], ['peter-dinklage', 'Tyrion Lannister']], 'genres' => ['Action', 'Adventure', 'Drama', 'Fantasy'], 'tags' => ['epic', 'ensemble-cast'], 'seasons' => [[1, 10], [2, 10], [3, 10], [4, 10], [5, 10], [6, 10], [7, 7], [8, 6]], 'desc' => 'Noble houses wage a brutal contest for the throne while an ancient threat gathers in the north.'],
            ['title' => 'The Last of Us', 'year' => 2023, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.7, 'badge' => BadgeType::Subtitled->value, 'cast' => [['pedro-pascal', 'Joel'], ['bella-ramsey', 'Ellie']], 'genres' => ['Drama', 'Action', 'Sci-Fi'], 'tags' => ['post-apocalyptic', 'found-family', 'survival'], 'seasons' => [[1, 9], [2, 7]], 'desc' => 'A hardened smuggler escorts a teenage girl across a ruined country that may hold humanity\'s last hope.'],
            ['title' => 'Stranger Things', 'year' => 2016, 'country' => 'United States', 'lang' => 'English', 'imdb' => 8.7, 'badge' => BadgeType::Subtitled->value, 'cast' => [['millie-bobby-brown', 'Eleven']], 'genres' => ['Sci-Fi', 'Horror', 'Drama'], 'tags' => ['coming-of-age', 'found-family'], 'seasons' => [[1, 8], [2, 9], [3, 8], [4, 9]], 'desc' => 'A small town\'s kids uncover secret experiments and a monstrous parallel world next door.'],
        ];
    }

    private function articleHeadlines(): array
    {
        return [
            ['How Christopher Nolan Reinvented the Blockbuster', 'چگونه کریستوفر نولان فیلم‌های پرفروش را از نو تعریف کرد'],
            ['The Quiet Power of Slow Cinema', 'قدرت خاموش سینمای آهسته'],
            ['Why Practical Effects Still Beat CGI', 'چرا جلوه‌های عملی هنوز بر جلوه‌های رایانه‌ای برتری دارند'],
            ['A Beginner\'s Guide to Korean New Wave', 'راهنمای مبتدیان برای موج نوی سینمای کره'],
            ['The Comeback of the Movie Musical', 'بازگشت موزیکال سینمایی'],
            ['Scoring Tension: The Art of the Film Composer', 'ساختن تعلیق: هنر آهنگساز فیلم'],
            ['Streaming Wars: What Subscribers Actually Want', 'جنگ پخش آنلاین: مشترکان واقعاً چه می‌خواهند'],
            ['The Anatomy of a Perfect Heist Movie', 'کالبدشکافی یک فیلم سرقت بی‌نقص'],
            ['From Page to Screen: Adaptations That Work', 'از کتاب تا پرده: اقتباس‌هایی که جواب می‌دهند'],
            ['Why We Keep Rewatching the Same Films', 'چرا فیلم‌های تکراری را بارها تماشا می‌کنیم'],
            ['The Return of the Three-Hour Epic', 'بازگشت حماسه‌های سه‌ساعته'],
            ['Animation Is Not Just for Kids', 'انیمیشن فقط برای کودکان نیست'],
            ['Editing Invisible: Cuts You Never Notice', 'تدوین نامرئی: برش‌هایی که هرگز نمی‌بینید'],
            ['The Director\'s Cut Debate, Explained', 'بحث نسخهٔ کارگردان، به زبان ساده'],
            ['How Trailers Learned to Lie', 'چگونه تریلرها دروغ‌گفتن را آموختند'],
        ];
    }

    private function articleBody(string $title): string
    {
        return implode("\n\n", [
            "\"{$title}\" looks at one of the conversations shaping film today.",
            fake()->paragraph(6),
            fake()->paragraph(7),
            fake()->paragraph(5),
        ]);
    }

    private function commentBodies(): array
    {
        return [
            'Just finished this and I am genuinely speechless. The ending wrecked me.',
            'Underrated masterpiece. The cinematography alone is worth the watch.',
            'Came for the cast, stayed for the score. Absolutely incredible.',
            'I did not expect that twist at all. Watched it again immediately.',
            'Honestly a bit overhyped, but still a solid watch for a weekend.',
            'The pacing in the second act drags, but the payoff is worth it.',
            'This is the kind of film that stays with you for days.',
            'Rewatched for the third time and noticed so many new details.',
            'The lead performance deserved every award it got.',
            'Not my usual genre but this completely won me over.',
            'Perfect movie night pick. Everyone in the room was glued to the screen.',
            'The director is on another level. Every frame is intentional.',
        ];
    }

    private function replyBodies(): array
    {
        return [
            'Completely agree, that scene is unforgettable.',
            'Same here! I had to pause and just sit with it.',
            'Respectfully disagree, I thought it dragged a little.',
            'Yes! The soundtrack carried the whole thing.',
            'You should check out the director\'s earlier work too.',
            'This comment convinced me to give it another shot.',
            'Right? Nobody talks about how good the editing is.',
            'Watched it because of comments like this. No regrets.',
        ];
    }
}
