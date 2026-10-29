<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;

class UserManage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:manage
                            {action : Action to perform (create|list|show|update|activate|deactivate|suspend|set-type|set-validity|delete)}
                            {--email= : User email address}
                            {--name= : User name (for create/update)}
                            {--password= : User password (for create/update)}
                            {--type= : User type: super_admin|library_user}
                            {--phone= : User phone number}
                            {--library-id= : Library ID to attach the user to}
                            {--status= : Account status: active|inactive|suspended}
                            {--valid-until= : Account validity date (Y-m-d or Y-m-d H:i:s), empty to clear}
                            {--force : Skip confirmation prompts}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create and manage application users (account status, validity, type, etc.)';

    /**
     * Allowed values for validation.
     */
    private const TYPES = ['super_admin', 'library_user'];

    private const STATUSES = ['active', 'inactive', 'suspended'];

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'create' => $this->createUser(),
            'list' => $this->listUsers(),
            'show' => $this->showUser(),
            'update' => $this->updateUser(),
            'activate' => $this->setStatus('active'),
            'deactivate' => $this->setStatus('inactive'),
            'suspend' => $this->setStatus('suspended'),
            'set-type' => $this->setType(),
            'set-validity' => $this->setValidity(),
            'delete' => $this->deleteUser(),
            default => $this->fail("Unknown action: {$action}"),
        };
    }

    /**
     * Create a new user.
     */
    private function createUser(): int
    {
        $email = $this->option('email') ?: $this->ask('Email address');
        $name = $this->option('name') ?: $this->ask('Name');

        if (User::where('email', $email)->exists()) {
            $this->error("A user with email '{$email}' already exists.");

            return self::FAILURE;
        }

        $password = $this->option('password') ?: $this->secret('Password');

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'user_type' => $this->validateChoice('type', self::TYPES, 'library_user'),
            'phone' => $this->option('phone'),
            'library_id' => $this->option('library-id'),
            'account_status' => 'active',
            'is_active' => true,
        ]);

        $this->info('User created successfully:');
        $this->table(
            ['ID', 'Name', 'Email', 'Type', 'Status'],
            [[$user->id, $user->name, $user->email, $user->user_type, $user->account_status]]
        );

        return self::SUCCESS;
    }

    /**
     * List all users.
     */
    private function listUsers(): int
    {
        $users = User::query()
            ->when($this->option('type'), fn ($q) => $q->where('user_type', $this->option('type')))
            ->when($this->option('status'), fn ($q) => $q->where('account_status', $this->option('status')))
            ->orderBy('id')
            ->get();

        if ($users->isEmpty()) {
            $this->info('No users found.');

            return self::SUCCESS;
        }

        $rows = $users->map(fn (User $u) => [
            $u->id,
            $u->name,
            $u->email,
            $u->user_type,
            $u->account_status,
            $u->is_active ? 'yes' : 'no',
            $u->account_valid_until?->format('Y-m-d') ?? '-',
            $u->library_id ?? '-',
        ]);

        $this->table(
            ['ID', 'Name', 'Email', 'Type', 'Status', 'Active', 'Valid Until', 'Library'],
            $rows
        );

        return self::SUCCESS;
    }

    /**
     * Show a single user.
     */
    private function showUser(): int
    {
        $user = $this->resolveUser();
        if (! $user) {
            return self::FAILURE;
        }

        $this->table(
            ['Field', 'Value'],
            [
                ['ID', $user->id],
                ['Name', $user->name],
                ['Email', $user->email],
                ['Type', $user->user_type],
                ['Phone', $user->phone ?? '-'],
                ['Account Status', $user->account_status],
                ['Active Flag', $user->is_active ? 'yes' : 'no'],
                ['Valid Until', $user->account_valid_until?->format('Y-m-d H:i:s') ?? '-'],
                ['Verified At', $user->account_verified_at?->format('Y-m-d H:i:s') ?? '-'],
                ['Last Login', $user->last_login_at?->format('Y-m-d H:i:s') ?? '-'],
                ['Library ID', $user->library_id ?? '-'],
                ['Created At', $user->created_at->format('Y-m-d H:i:s')],
            ]
        );

        return self::SUCCESS;
    }

    /**
     * Update an existing user.
     */
    private function updateUser(): int
    {
        $user = $this->resolveUser();
        if (! $user) {
            return self::FAILURE;
        }

        $data = [];

        if ($name = $this->option('name')) {
            $data['name'] = $name;
        }
        if ($password = $this->option('password')) {
            $data['password'] = Hash::make($password);
        }
        if ($phone = $this->option('phone')) {
            $data['phone'] = $phone;
        }
        if ($type = $this->option('type')) {
            $data['user_type'] = $this->normalizeChoice($type, self::TYPES);
        }
        if ($status = $this->option('status')) {
            $data['account_status'] = $this->normalizeChoice($status, self::STATUSES);
            $data['is_active'] = $status === 'active';
        }
        if ($this->option('library-id') !== null) {
            $data['library_id'] = $this->option('library-id');
        }

        if (empty($data)) {
            $this->warn('Nothing to update. Use --name, --password, --phone, --type, --status, or --library-id.');

            return self::FAILURE;
        }

        $user->update($data);
        $this->info("User '{$user->email}' updated successfully.");

        return self::SUCCESS;
    }

    /**
     * Set account status (active/inactive/suspended).
     */
    private function setStatus(string $status): int
    {
        $user = $this->resolveUser();
        if (! $user) {
            return self::FAILURE;
        }

        $user->update([
            'account_status' => $status,
            'is_active' => $status === 'active',
        ]);

        $this->info("User '{$user->email}' is now '{$status}'.");

        return self::SUCCESS;
    }

    /**
     * Set the user type.
     */
    private function setType(): int
    {
        $user = $this->resolveUser();
        if (! $user) {
            return self::FAILURE;
        }

        $type = $this->option('type');
        if (! $type) {
            $this->error('Please provide --type=super_admin|library_user');

            return self::FAILURE;
        }

        $user->update(['user_type' => $this->normalizeChoice($type, self::TYPES)]);
        $this->info("User '{$user->email}' type set to '{$user->user_type}'.");

        return self::SUCCESS;
    }

    /**
     * Set account validity (expiry) date. Empty value clears it (no expiry).
     */
    private function setValidity(): int
    {
        $user = $this->resolveUser();
        if (! $user) {
            return self::FAILURE;
        }

        $validUntil = $this->option('valid-until');

        if ($validUntil === null || $validUntil === '') {
            $user->update(['account_valid_until' => null]);
            $this->info("User '{$user->email}' validity cleared (no expiry).");
        } else {
            $user->update(['account_valid_until' => $validUntil]);
            $this->info("User '{$user->email}' is valid until '{$validUntil}'.");
        }

        return self::SUCCESS;
    }

    /**
     * Delete a user.
     */
    private function deleteUser(): int
    {
        $user = $this->resolveUser();
        if (! $user) {
            return self::FAILURE;
        }

        if (! $this->option('force') && ! $this->confirm("Are you sure you want to delete '{$user->email}'?")) {
            $this->info('Operation cancelled.');

            return self::FAILURE;
        }

        $user->delete();
        $this->info("User '{$user->email}' deleted.");

        return self::SUCCESS;
    }

    /**
     * Resolve a user by --email option or prompt.
     */
    private function resolveUser(): ?User
    {
        $email = $this->option('email') ?: $this->ask('Email address');
        $user = User::where('email', $email)->first();

        if (! $user) {
            $this->error("User with email '{$email}' not found.");

            return null;
        }

        return $user;
    }

    /**
     * Validate an option against allowed choices, prompting if invalid.
     */
    private function validateChoice(string $option, array $allowed, string $default): string
    {
        $value = $this->option($option) ?: $default;

        return $this->normalizeChoice($value, $allowed);
    }

    private function normalizeChoice(string $value, array $allowed): string
    {
        if (! in_array($value, $allowed, true)) {
            return $this->choice('Select a valid value for', $allowed, 0);
        }

        return $value;
    }
}
