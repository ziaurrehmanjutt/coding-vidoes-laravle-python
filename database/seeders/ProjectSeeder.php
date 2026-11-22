<?php

namespace Database\Seeders;

use App\Models\Project;
use Database\Seeders\Showcase\BatchOne;
use Illuminate\Database\Seeder;

/**
 * Hand-scripted studio videos, added a few at a time. Each batch is a class in Showcase/.
 * Projects that already exist (matched by name) are left alone, so your own edits are never overwritten.
 *
 * Run with: php artisan db:seed --class=ProjectSeeder
 */
class ProjectSeeder extends Seeder
{
    public function run(): void
    {
        foreach ([BatchOne::projects()] as $batch) {
            foreach ($batch as $definition) {
                Project::firstOrCreate(['name' => $definition['name']], [
                    'type' => $definition['type'],
                    'category' => $definition['category'],
                    'language' => $definition['language'] ?? null,
                    'tags' => $definition['tags'],
                    'document' => $definition['document'],
                ]);
            }
        }
    }
}
