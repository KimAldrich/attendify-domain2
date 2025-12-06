<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Campus;

class CampusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Campus::insert([
            ['abbrev'=>'AS','name'=>'Asingan','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['abbrev'=>'AL','name'=>'Alaminos','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['abbrev'=>'BA','name'=>'Bayambang','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['abbrev'=>'BI','name'=>'Binmaley','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['abbrev'=>'IN','name'=>'Infanta','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['abbrev'=>'LI','name'=>'Lingayen','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['abbrev'=>'SC','name'=>'San Carlos City','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['abbrev'=>'SM','name'=>'Santa Maria','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
            ['abbrev'=>'UR','name'=>'Urdaneta City','is_active'=>true,'created_at'=>now(),'updated_at'=>now()],
        ]);
    }
}
