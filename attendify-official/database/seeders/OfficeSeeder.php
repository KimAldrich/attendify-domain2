<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Office;

class OfficeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Office::insert([
            ['name'=>'Registrar','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['name'=>'Library','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
        ]);
    }
}
