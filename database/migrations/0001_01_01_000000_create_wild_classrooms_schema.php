<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Consolidated schema for Wild Classrooms.
 *
 * This replaces the 62 migrations written between 2014 and 2024. Those were a
 * historical record of churn — tables renamed (activities -> challenges,
 * tasks -> spottings), columns added one at a time, and two of them could not
 * run at all: one declared a duplicate class name, and another dropped a
 * foreign key by name, which SQLite cannot do. That made a from-scratch
 * migrate impossible, so tests could never build the schema.
 *
 * The definitions below were generated from the live production database, so
 * a fresh test database gets byte-equivalent structure to production rather
 * than whatever the old migration sequence happened to leave behind.
 *
 * Every table is guarded with hasTable(). Production already has these tables
 * and the old migration names recorded, so there this migration is a no-op;
 * on a fresh database it builds everything. Nova's own tables (action_events,
 * nova_notifications, field_attachments) come from the Nova package.
 */
return new class extends Migration
{
    public function up(): void
    {
        /*
         * Production was built by the old migration sequence and already has
         * every table below, with those migration names recorded. The app
         * tables are all-or-nothing, so one sentinel is enough: if spottings
         * exists this is that database and there is nothing to build.
         */
        if (Schema::hasTable('spottings')) {
            return;
        }

        // ---- Framework tables -------------------------------------------------

        Schema::create('users', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::create('password_reset_tokens', function (Blueprint $table): void {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::create('sessions', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->longText('payload');
            $table->integer('last_activity')->index();
        });

        Schema::create('cache', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->mediumText('value');
            $table->integer('expiration');
        });

        Schema::create('cache_locks', function (Blueprint $table): void {
            $table->string('key')->primary();
            $table->string('owner');
            $table->integer('expiration');
        });

        Schema::create('jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('queue')->index();
            $table->longText('payload');
            $table->unsignedTinyInteger('attempts');
            $table->unsignedInteger('reserved_at')->nullable();
            $table->unsignedInteger('available_at');
            $table->unsignedInteger('created_at');
        });

        Schema::create('job_batches', function (Blueprint $table): void {
            $table->string('id')->primary();
            $table->string('name');
            $table->integer('total_jobs');
            $table->integer('pending_jobs');
            $table->integer('failed_jobs');
            $table->longText('failed_job_ids');
            $table->mediumText('options')->nullable();
            $table->integer('cancelled_at')->nullable();
            $table->integer('created_at');
            $table->integer('finished_at')->nullable();
        });

        Schema::create('failed_jobs', function (Blueprint $table): void {
            $table->id();
            $table->string('uuid')->unique();
            $table->text('connection');
            $table->text('queue');
            $table->longText('payload');
            $table->longText('exception');
            $table->timestamp('failed_at')->useCurrent();
        });

        Schema::create('personal_access_tokens', function (Blueprint $table): void {
            $table->id();
            $table->morphs('tokenable');
            $table->string('name');
            $table->string('token', 64)->unique();
            $table->text('abilities')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        // ---- Roles ------------------------------------------------------------

        Schema::create('roles', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->timestamps();
        });

        // Production has no unique constraint on the pair, and 259 rows that
        // may contain duplicates, so adding one here would diverge from live.
        Schema::create('role_user', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('role_id');
            $table->unsignedInteger('user_id');
            $table->timestamps();
        });

        // ---- Content ----------------------------------------------------------

        Schema::create('categories', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('icon')->nullable();
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('challenges', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('category_id')->index();
            $table->integer('month')->nullable();
            $table->string('title');
            $table->text('plural')->nullable();
            $table->text('image_name')->nullable();
            $table->string('image', 255)->nullable();
            $table->text('credit')->nullable();
            $table->string('teaser');
            $table->text('description')->nullable();
            $table->text('where_to_find_them')->nullable();
            $table->timestamps();
        });

        Schema::create('posts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('author_id')->index();
            $table->string('title');
            $table->text('intro');
            $table->date('post_date');
            $table->mediumText('content');
            $table->unsignedInteger('category_id')->nullable()->index();
            $table->unsignedInteger('challenge_id')->nullable()->index();
            $table->string('featured_image');
            $table->timestamps();
        });

        Schema::create('static_pages', function (Blueprint $table): void {
            $table->increments('id');
            $table->string('type');
            $table->string('name');
            $table->string('title')->nullable();
            $table->longText('attributes');
            $table->timestamps();
        });

        // ---- Groups, units, spottings ----------------------------------------

        Schema::create('groups', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('owner_id')->index();
            $table->text('title');
            $table->string('type')->default('group');
            $table->text('description')->nullable();
            $table->string('address_line_1');
            $table->string('address_line_2')->nullable();
            $table->string('city');
            $table->string('county')->nullable();
            $table->string('postal_code');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('public');
            $table->string('image')->nullable();
            $table->timestamps();
        });

        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->unsignedBigInteger('group_id')->index();
            $table->timestamps();
        });

        Schema::create('spottings', function (Blueprint $table): void {
            $table->id();
            $table->unsignedInteger('unit_id');
            $table->unsignedInteger('challenge_id')->index();
            $table->string('campaign', 255)->nullable();
            $table->text('notes')->nullable();
            $table->string('owner')->nullable();
            $table->text('picture')->nullable();
            $table->text('exif')->nullable();
            $table->text('latitude')->nullable();
            $table->text('longitude')->nullable();
            $table->string('location')->nullable();
            $table->string('what3words')->nullable();
            $table->boolean('gallery')->default(false);
            $table->boolean('map')->default(false);
            $table->boolean('merit')->default(false);
            $table->boolean('featured')->default(false);
            $table->integer('likes')->default(0);
            $table->text('comment')->nullable();
            $table->timestamps();
        });

        // ---- Likes (maize-tech/laravel-markable) ------------------------------

        Schema::create('markable_likes', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('user_id')->index();
            $table->string('markable_type');
            $table->unsignedBigInteger('markable_id');
            $table->string('value')->nullable();
            $table->longText('metadata')->nullable();
            $table->timestamps();

            $table->index(['markable_type', 'markable_id']);
        });

        // ---- Schools / classrooms --------------------------------------------
        // Carried over for fidelity with production. Both are effectively
        // unused (schools has 0 rows, classrooms 5) and are candidates for
        // removal once confirmed dead.

        Schema::create('schools', function (Blueprint $table): void {
            $table->id();
            $table->string('school');
            $table->string('logo');
            $table->string('featured_image');
            $table->text('statement');
            $table->string('head');
            $table->string('address_line_1');
            $table->string('city');
            $table->string('postal_code');
            $table->string('accent_colour');
            $table->string('latitude');
            $table->string('longitude');
            $table->timestamps();
        });

        Schema::create('classrooms', function (Blueprint $table): void {
            $table->id();
            $table->string('teacher');
            $table->string('year');
            $table->string('key_stage');
            $table->string('intake_year');
            $table->unsignedInteger('school_id')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        foreach ([
            'classrooms', 'schools', 'markable_likes', 'spottings', 'units',
            'groups', 'static_pages', 'posts', 'challenges', 'categories',
            'role_user', 'roles', 'personal_access_tokens', 'failed_jobs',
            'job_batches', 'jobs', 'cache_locks', 'cache', 'sessions',
            'password_reset_tokens', 'users',
        ] as $table) {
            Schema::dropIfExists($table);
        }
    }
};
