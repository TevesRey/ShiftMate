<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Older project copies used different singular/plural names. Rename only
        // when the canonical table is absent so existing records are preserved.
        foreach ([
            'employee' => 'employees',
            'shift' => 'shifts',
            'schedule' => 'schedules',
            'rest_day_requests' => 'rest_day_request',
            'absence_request' => 'absence_requests',
            'notifications' => 'notification',
        ] as $legacy => $canonical) {
            if (Schema::hasTable($legacy) && ! Schema::hasTable($canonical)) {
                Schema::rename($legacy, $canonical);
            }
        }

        if (! Schema::hasTable('employees')) {
            Schema::create('employees', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->unique()->constrained('users')->cascadeOnDelete();
                $table->integer('employee_number')->unique();
                $table->string('position')->default('Not set');
                $table->string('department')->default('Not assigned');
                $table->string('contact_number')->default('Not provided');
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('shifts')) {
            Schema::create('shifts', function (Blueprint $table) {
                $table->id();
                $table->string('shift_name');
                $table->time('start_time');
                $table->time('end_time');
                $table->string('description')->default('');
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('schedules')) {
            Schema::create('schedules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->foreignId('shift_id')->constrained('shifts')->cascadeOnDelete();
                $table->date('work_date');
                $table->enum('status', ['active', 'inactive'])->default('active');
                $table->timestamps();
                $table->index(['user_id', 'work_date']);
            });
        }

        if (! Schema::hasTable('rest_day_request')) {
            Schema::create('rest_day_request', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->date('current_rest_day');
                $table->date('requested_rest_day');
                $table->string('reason');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('absence_requests')) {
            Schema::create('absence_requests', function (Blueprint $table) {
                $table->id();
                $table->foreignId('employee_id')->constrained('employees')->cascadeOnDelete();
                $table->foreignId('schedule_id')->constrained('schedules')->cascadeOnDelete();
                $table->date('absence_date');
                $table->string('description');
                $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
                $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamp('reviewed_at')->nullable();
                $table->timestamps();
            });
        }

        if (! Schema::hasTable('notification')) {
            Schema::create('notification', function (Blueprint $table) {
                $table->id();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
                $table->string('title');
                $table->string('message');
                $table->string('type');
                $table->boolean('is_read')->default(false);
                $table->timestamps();
            });
        }

        // Add fields used by the current UI to older versions of these tables.
        if (Schema::hasTable('rest_day_request')) {
            Schema::table('rest_day_request', function (Blueprint $table) {
                if (! Schema::hasColumn('rest_day_request', 'requested_rest_day') && Schema::hasColumn('rest_day_request', 'request_rest_day')) {
                    $table->renameColumn('request_rest_day', 'requested_rest_day');
                }
                if (! Schema::hasColumn('rest_day_request', 'reviewed_by')) {
                    $table->unsignedBigInteger('reviewed_by')->nullable();
                }
                if (! Schema::hasColumn('rest_day_request', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable();
                }
                if (! Schema::hasColumn('rest_day_request', 'status')) {
                    $table->string('status')->default('pending');
                }
            });
        }

        if (Schema::hasTable('absence_requests')) {
            Schema::table('absence_requests', function (Blueprint $table) {
                if (! Schema::hasColumn('absence_requests', 'reviewed_by')) {
                    $table->unsignedBigInteger('reviewed_by')->nullable();
                }
                if (! Schema::hasColumn('absence_requests', 'reviewed_at')) {
                    $table->timestamp('reviewed_at')->nullable();
                }
                if (! Schema::hasColumn('absence_requests', 'status')) {
                    $table->string('status')->default('pending');
                }
            });
        }
    }

    public function down(): void
    {
        // Intentionally non-destructive: this migration may have renamed legacy
        // tables or preserved user records, so rollback must not drop live data.
    }
};
