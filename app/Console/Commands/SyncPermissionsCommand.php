<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Nwidart\Modules\Facades\Module;
use ReflectionClass;
use ReflectionMethod;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync
                            {--module= : Sync only a specific module by name}
                            {--fresh : Delete all permissions and re-create from scratch}
                            {--dry-run : Show what would be created/deleted without touching the DB}
                            {--skip-admin : Do not create/update the default super admin user}';

    protected $description = 'Sync permissions from Policy classes across all nwidart/laravel-modules';

    private const GUARD = 'api';

    private const SUPER_ADMIN_ROLE = 'super_admin';

    private const DEFAULT_ADMIN_NAME = 'admin';
    private const DEFAULT_ADMIN_EMAIL = 'admin@gmail.com';
    private const DEFAULT_ADMIN_PASSWORD = '12345678';

    private const EXCLUDED_METHODS = [
        '__construct',
        '__call',
        '__callStatic',
        '__get',
        '__set',
        '__isset',
        '__unset',
        '__sleep',
        '__wakeup',
        '__toString',
        '__invoke',
        '__set_state',
        '__clone',
        '__debugInfo',
        '__serialize',
        '__unserialize',
        'before',
        'after',
    ];

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');
        $isFresh  = $this->option('fresh');

        $this->info('Scanning policy classes across modules...');

        $discovered = $this->discoverPermissions();

        if ($discovered->isEmpty()) {
            $this->warn('No permissions discovered from any policy.');
            return self::FAILURE;
        }

        $this->info("Found {$discovered->count()} permissions.");

        if ($isDryRun) {
            $this->runDryRun($discovered);
            return self::SUCCESS;
        }

        if ($isFresh) {
            $this->runFresh($discovered);
        } else {
            $this->runSync($discovered);
        }

        if (!$this->option('skip-admin')) {
            $this->syncSuperAdmin();
        }

        return self::SUCCESS;
    }

    private function discoverPermissions(): Collection
    {
        $permissions = collect();
        $targetModule = $this->option('module');

        $policyDirs = $this->resolvePolicyDirectories($targetModule);

        foreach ($policyDirs as $moduleName => $dir) {
            if (!is_dir($dir)) {
                continue;
            }

            foreach (glob("{$dir}/*.php") as $file) {
                $class = $this->resolveClassFromFile($file);

                if (!$class || !class_exists($class)) {
                    continue;
                }

                $extracted = $this->extractPermissionsFromPolicy($class);

                if ($extracted->isEmpty()) {
                    $this->warn("  [{$moduleName}] {$class} -> no permissions found");
                    continue;
                }

                $permissions = $permissions->merge($extracted);
                $this->line("  [{$moduleName}] {$class} -> {$extracted->count()} permissions");
            }
        }

        return $permissions->unique()->sort()->values();
    }

    private function resolvePolicyDirectories(?string $targetModule): array
    {
        $dirs = [];

        $appPolicyDir = app_path('Policies');
        if (is_dir($appPolicyDir) && !$targetModule) {
            $dirs['App'] = $appPolicyDir;
        }

        $modules = $targetModule
            ? collect([Module::find($targetModule)])->filter()
            : collect(Module::allEnabled());

        foreach ($modules as $module) {
            $dirs[$module->getName()] = $module->getPath() . '/app/Policies';
        }

        return $dirs;
    }

    private function resolveClassFromFile(string $file): ?string
    {
        $contents = file_get_contents($file);

        if (!preg_match('/^namespace\s+(.+?);/m', $contents, $nsMatch)) {
            return null;
        }

        if (!preg_match('/^class\s+(\w+)/m', $contents, $classMatch)) {
            return null;
        }

        return "{$nsMatch[1]}\\{$classMatch[1]}";
    }

    private function extractPermissionsFromPolicy(string $class): Collection
    {
        $permissions = collect();

        try {
            $reflection = new ReflectionClass($class);
            $source = file_get_contents($reflection->getFileName());
            $methods = $this->getPublicPolicyMethods($reflection);

            foreach ($methods as $method) {
                $permissionName = $this->extractPermissionNameFromMethodBody($reflection, $method, $source);

                if ($permissionName) {
                    $permissions->push($permissionName);
                    continue;
                }

                $slug = $this->guessSlugFromClassName($class);

                if ($slug) {
                    $permissions->push("{$slug}.{$method->getName()}");
                }
            }
        } catch (\Throwable $e) {
            $this->warn("Reflection failed for {$class}: {$e->getMessage()}");
        }

        return $permissions;
    }

    private function getPublicPolicyMethods(ReflectionClass $reflection): array
    {
        return array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            fn(ReflectionMethod $method) => !$method->isStatic()
                && !in_array($method->getName(), self::EXCLUDED_METHODS, true)
                && $method->getDeclaringClass()->getName() === $reflection->getName()
        );
    }

    private function extractPermissionNameFromMethodBody(
        ReflectionClass $reflection,
        ReflectionMethod $method,
        string $source,
    ): ?string {
        $lines = array_slice(
            explode("\n", $source),
            $method->getStartLine() - 1,
            $method->getEndLine() - $method->getStartLine() + 1
        );

        $body = implode("\n", $lines);

        if (preg_match("/->hasPermissionTo\(\s*['\"](.+?)['\"]\s*\)/", $body, $m)) {
            return $m[1];
        }

        if (preg_match("/->can\(\s*['\"](.+?)['\"]\s*\)/", $body, $m)) {
            return $m[1];
        }

        if (preg_match("/->checkPermissionTo\(\s*['\"](.+?)['\"]\s*\)/", $body, $m)) {
            return $m[1];
        }

        return null;
    }

    private function guessSlugFromClassName(string $class): ?string
    {
        $shortName = class_basename($class);

        if (!Str::endsWith($shortName, 'Policy')) {
            return null;
        }

        $model = Str::replaceLast('Policy', '', $shortName);

        return Str::of($model)->camel()->lcfirst()->toString();
    }

    private function runSync(Collection $discovered): void
    {
        $existing = Permission::where('guard_name', self::GUARD)->pluck('name');

        $toCreate = $discovered->diff($existing);
        $toDelete = $existing->diff($discovered)->filter(
            fn(string $p) => !Str::startsWith($p, ['page_', 'widget_'])
        );

        if ($toCreate->isEmpty() && $toDelete->isEmpty()) {
            $this->info('Already in sync. Nothing to do.');
            return;
        }

        if ($toCreate->isNotEmpty()) {
            $this->info("Creating {$toCreate->count()} new permissions:");

            foreach ($toCreate as $name) {
                Permission::findOrCreate($name, self::GUARD);
                $this->line("  <fg=green>+</> {$name}");
            }
        }

        if ($toDelete->isNotEmpty()) {
            $this->info("Found {$toDelete->count()} obsolete permissions:");

            $this->table(
                ['Permission'],
                $toDelete->map(fn(string $p) => [$p])->toArray()
            );

            if (!$this->confirm('Delete these obsolete permissions from DB?', false)) {
                $this->warn('Skipped deletion.');
            } else {
                foreach ($toDelete as $name) {
                    Permission::where('name', $name)
                        ->where('guard_name', self::GUARD)
                        ->delete();
                    $this->line("  <fg=red>-</> {$name}");
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info('Sync complete.');
    }

    private function runFresh(Collection $discovered): void
    {
        if (!$this->confirm('This will delete ALL non-shield permissions and re-create. Continue?', false)) {
            $this->warn('Aborted.');
            return;
        }

        Permission::where('guard_name', self::GUARD)
            ->where('name', 'NOT LIKE', 'page_%')
            ->where('name', 'NOT LIKE', 'widget_%')
            ->delete();

        foreach ($discovered as $name) {
            Permission::findOrCreate($name, self::GUARD);
            $this->line("  <fg=green>+</> {$name}");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
        $this->info("Fresh sync complete. {$discovered->count()} permissions created.");
    }

    private function runDryRun(Collection $discovered): void
    {
        $existing = Permission::where('guard_name', self::GUARD)->pluck('name');

        $toCreate = $discovered->diff($existing);
        $toDelete = $existing->diff($discovered)->filter(
            fn(string $p) => !Str::startsWith($p, ['page_', 'widget_'])
        );

        $this->newLine();
        $this->info('Dry run — no changes will be made');
        $this->newLine();

        if ($toCreate->isNotEmpty()) {
            $this->line("<fg=green>Would CREATE {$toCreate->count()} permissions:</>");
            $toCreate->each(fn(string $p) => $this->line("  + {$p}"));
        }

        if ($toDelete->isNotEmpty()) {
            $this->newLine();
            $this->line("<fg=red>Would DELETE {$toDelete->count()} obsolete permissions:</>");
            $toDelete->each(fn(string $p) => $this->line("  - {$p}"));
        }

        if ($toCreate->isEmpty() && $toDelete->isEmpty()) {
            $this->info('Already in sync.');
        }
    }

    private function syncSuperAdmin(): void
    {
        $this->newLine();
        $this->info('Syncing super admin role and default user...');

        $role = Role::firstOrCreate(
            ['name' => self::SUPER_ADMIN_ROLE, 'guard_name' => self::GUARD]
        );

        $allPermissions = Permission::where('guard_name', self::GUARD)->pluck('name')->all();
        $role->syncPermissions($allPermissions);

        $this->line("  <fg=green>✓</> Role '" . self::SUPER_ADMIN_ROLE . "' has " . count($allPermissions) . ' permissions');

        $userModel = config('auth.providers.users.model', \App\Models\User::class);

        $user = $userModel::firstOrNew(['email' => self::DEFAULT_ADMIN_EMAIL]);

        $wasRecentlyCreated = !$user->exists;

        if ($wasRecentlyCreated) {
            $user->name = self::DEFAULT_ADMIN_NAME;
            $user->password = Hash::make(self::DEFAULT_ADMIN_PASSWORD);

            if (in_array('email_verified_at', $user->getFillable(), true) || $user->hasCast('email_verified_at')) {
                $user->email_verified_at = now();
            } else {
                $user->email_verified_at = now();
            }

            $user->save();
            $this->line('  <fg=green>+</> Created default admin user: ' . self::DEFAULT_ADMIN_EMAIL);
        } else {
            $this->line('  <fg=yellow>~</> Admin user already exists: ' . self::DEFAULT_ADMIN_EMAIL);
        }

        if (!$user->hasRole(self::SUPER_ADMIN_ROLE)) {
            $user->assignRole($role);
            $this->line("  <fg=green>✓</> Assigned '" . self::SUPER_ADMIN_ROLE . "' role to user");
        } else {
            $this->line("  <fg=yellow>~</> User already has '" . self::SUPER_ADMIN_ROLE . "' role");
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
