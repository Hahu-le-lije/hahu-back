<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Backwards-compatible alias for the canonical content pack seeder.
 */
class GameContentSeeder extends Seeder
{
	public function run(): void
	{
		$this->call(ContentPackSeeder::class);
	}
}
