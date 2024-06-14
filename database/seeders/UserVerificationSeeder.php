<?php

namespace Database\Seeders;

use App\Models\UserVerification;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserVerificationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        UserVerification::create([
            'user_id' => 1,
            'otp' => '123455',
            'expire_at' => '2024-08-09 08:09:01'
        ]);
    }
}
