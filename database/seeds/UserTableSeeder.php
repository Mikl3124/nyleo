<?php

use Illuminate\Database\Seeder;

class UserTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('users')->insert([
             [
                'email' => 'client@gmail.com',
                'password' => bcrypt('password'),
                'email_verified_at' => '2020-01-17 10:21:11',
                'role' =>'client',
            ],
            [
                'email' => 'admin@gmail.com',
                'password' => bcrypt('password'),
                'email_verified_at' => '2020-01-17 10:21:11',
                'role' =>'admin',
            ],

         ]

         );
    }
}
