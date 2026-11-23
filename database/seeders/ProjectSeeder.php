<?php

namespace Database\Seeders;

use App\Models\Project;
use Database\Seeders\Showcase\BatchOne;
use Database\Seeders\Showcase\BatchTwo;
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
        foreach ([BatchOne::projects(), BatchTwo::projects()] as $batch) {
            foreach ($batch as $definition) {
                $project = Project::firstOrCreate(['name' => $definition['name']], [
                    'type' => $definition['type'],
                    'category' => $definition['category'],
                    'language' => $definition['language'] ?? null,
                    'tags' => $definition['tags'],
                    'upload_title' => $definition['upload']['title'],
                    'upload_description' => $definition['upload']['description'],
                    'hashtags' => $definition['upload']['hashtags'],
                    'document' => $definition['document'],
                ]);

                // Videos that were created before their upload details existed get them now.
                // Anything you already wrote is kept.
                $project->fill(array_filter([
                    'upload_title' => $project->upload_title ?? $definition['upload']['title'],
                    'upload_description' => $project->upload_description ?? $definition['upload']['description'],
                    'hashtags' => $project->hashtags ?? $definition['upload']['hashtags'],
                ]))->save();
            }
        }
    }
}
