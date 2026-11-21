<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        if (! Schema::hasColumn('users', 'full_name')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('full_name')->nullable()->after('id');
            });
        }

        // Preserve existing account names before removing the separate columns.
        if (Schema::hasColumn('users', 'first_name') && Schema::hasColumn('users', 'last_name')) {
            DB::table('users')->select('id', 'first_name', 'last_name', 'full_name')->orderBy('id')->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    if (! trim((string) $user->full_name)) {
                        $name = trim(implode(' ', array_filter([$user->first_name ?? '', $user->last_name ?? ''])));
                        DB::table('users')->where('id', $user->id)->update(['full_name' => $name ?: 'Unnamed User']);
                    }
                }
            });
        } else {
            DB::table('users')->where(function ($query) {
                $query->whereNull('full_name')->orWhere('full_name', '');
            })->update(['full_name' => 'User']);
        }

        $drop = array_values(array_filter(['first_name', 'last_name'], fn ($column) => Schema::hasColumn('users', $column)));
        if ($drop) {
            Schema::table('users', function (Blueprint $table) use ($drop) {
                $table->dropColumn($drop);
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users')) {
            return;
        }

        $hasFirst = Schema::hasColumn('users', 'first_name');
        $hasLast = Schema::hasColumn('users', 'last_name');
        if (! $hasFirst || ! $hasLast) {
            Schema::table('users', function (Blueprint $table) use ($hasFirst, $hasLast) {
                if (! $hasFirst) $table->string('first_name')->nullable();
                if (! $hasLast) $table->string('last_name')->nullable();
            });
        }

        if (Schema::hasColumn('users', 'full_name')) {
            DB::table('users')->select('id', 'full_name')->orderBy('id')->chunkById(200, function ($users) {
                foreach ($users as $user) {
                    $parts = preg_split('/\\s+/', trim((string) $user->full_name), 2);
                    DB::table('users')->where('id', $user->id)->update([
                        'first_name' => $parts[0] ?? '',
                        'last_name' => $parts[1] ?? '',
                    ]);
                }
            });
            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('full_name');
            });
        }
    }
};
