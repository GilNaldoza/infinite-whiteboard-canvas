<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('whiteboards') && ! Schema::hasTable('boards')) {
            Schema::rename('whiteboards', 'boards');
        }

        if (! Schema::hasTable('boards')) {
            Schema::create('boards', function (Blueprint $table) {
                $table->id();
                $table->string('name')->unique();
                $table->longText('canvas_data')->nullable();
                $table->timestamps();
            });

            return;
        }

        Schema::table('boards', function (Blueprint $table) {
            if (! Schema::hasColumn('boards', 'name')) {
                $table->string('name')->nullable()->unique()->after('id');
            }

            if (! Schema::hasColumn('boards', 'canvas_data')) {
                $table->longText('canvas_data')->nullable()->after('name');
            }
        });

        DB::table('boards')
            ->whereNull('name')
            ->orderBy('id')
            ->get(['id'])
            ->each(function ($board): void {
                DB::table('boards')
                    ->where('id', $board->id)
                    ->update(['name' => 'Untitled board '.$board->id]);
            });
    }

    public function down(): void
    {
        //
    }
};
