<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class PayDatabaseSeeder extends Seeder
{
	/**
	 * Seed the application's database.
	 *
	 * @return void
	 */
	public function run()
	{
		// \App\Models\User::factory(30)->create();

		$this->call([
			OrderSeeder::class,
		]);
	}
}
