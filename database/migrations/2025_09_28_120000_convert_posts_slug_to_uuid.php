<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Make this migration defensive and idempotent. It will:
        // - add slug_uuid if missing
        // - populate slug_uuid for existing rows
        // - convert slug_uuid to slug if slug is missing
        // - avoid failing when indexes/columns are already absent

        // 1) Add slug_uuid if it doesn't exist
        if (!Schema::hasColumn('posts', 'slug_uuid')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->uuid('slug_uuid')->nullable()->after('title');
            });

            // Generate UUIDs for existing posts (only where null)
            \App\Models\Post::withoutEvents(function () {
                \App\Models\Post::chunk(100, function ($posts) {
                    foreach ($posts as $post) {
                        if (empty($post->slug_uuid)) {
                            $post->slug_uuid = (string) Str::uuid();
                            $post->save();
                        }
                    }
                });
            });

            // Make the column non-nullable
            try {
                Schema::table('posts', function (Blueprint $table) {
                    $table->uuid('slug_uuid')->nullable(false)->change();
                });
            } catch (\Exception $e) {
                // Some DB drivers require doctrine/dbal for change(); ignore if it fails
            }
        }

        // 2) If the old string slug exists, attempt to drop it (unique/index may not exist)
        if (Schema::hasColumn('posts', 'slug')) {
            try {
                Schema::table('posts', function (Blueprint $table) {
                    // Attempt to drop unique/index and the column; wrap in try/catch to avoid failure
                    try {
                        $table->dropUnique(['slug']);
                    } catch (\Exception $e) {
                        // ignore if unique/index does not exist
                    }
                    try {
                        $table->dropColumn('slug');
                    } catch (\Exception $e) {
                        // ignore if the column couldn't be dropped
                    }
                });
            } catch (\Exception $e) {
                // ignore whole-table alterations if they fail for some reason
            }
        }

        // 3) Ensure a uuid slug column exists
        if (!Schema::hasColumn('posts', 'slug')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->uuid('slug')->unique()->nullable()->after('title');
            });

            // Copy values from slug_uuid if available
            if (Schema::hasColumn('posts', 'slug_uuid')) {
                \App\Models\Post::withoutEvents(function () {
                    \App\Models\Post::chunk(100, function ($posts) {
                        foreach ($posts as $post) {
                            if (empty($post->slug) && !empty($post->slug_uuid)) {
                                $post->slug = $post->slug_uuid;
                                $post->save();
                            }
                        }
                    });
                });
            }

            // make slug non-nullable if desired
            try {
                Schema::table('posts', function (Blueprint $table) {
                    $table->uuid('slug')->nullable(false)->change();
                });
            } catch (\Exception $e) {
                // ignore if change() isn't supported
            }
        }

        // 4) Drop the temporary slug_uuid if it still exists
        if (Schema::hasColumn('posts', 'slug_uuid')) {
            try {
                Schema::table('posts', function (Blueprint $table) {
                    $table->dropColumn('slug_uuid');
                });
            } catch (\Exception $e) {
                // ignore
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Attempt to restore a string slug column from the uuid slug if present
        if (!Schema::hasColumn('posts', 'slug') && Schema::hasColumn('posts', 'slug_uuid')) {
            Schema::table('posts', function (Blueprint $table) {
                $table->string('slug')->nullable()->after('title');
            });

            \App\Models\Post::withoutEvents(function () {
                \App\Models\Post::chunk(100, function ($posts) {
                    foreach ($posts as $post) {
                        if (empty($post->slug) && !empty($post->slug_uuid)) {
                            $post->slug = (string) $post->slug_uuid;
                            $post->save();
                        }
                    }
                });
            });

            try {
                Schema::table('posts', function (Blueprint $table) {
                    $table->string('slug')->nullable(false)->change();
                    $table->unique('slug');
                });
            } catch (\Exception $e) {
                // ignore if change()/unique fails
            }
        }
    }
};
