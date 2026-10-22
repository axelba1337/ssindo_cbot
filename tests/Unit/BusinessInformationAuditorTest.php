<?php

namespace Tests\Unit;

use App\Services\BusinessInformationAuditor;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class BusinessInformationAuditorTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
        });
        Schema::create('faq_qa', function (Blueprint $table) {
            $table->id();
            $table->string('intent')->nullable();
            $table->text('question');
            $table->text('answer');
            $table->boolean('is_active')->default(true);
        });
        Schema::create('company_contacts', function (Blueprint $table) {
            $table->id();
            $table->string('type');
            $table->string('value');
            $table->boolean('is_primary')->default(false);
        });
        Schema::create('office_hours', function (Blueprint $table) {
            $table->id();
            $table->time('open_time');
            $table->time('close_time');
        });
    }

    public function test_it_flags_conflicting_business_information(): void
    {
        DB::table('services')->insert([
            'id' => 3,
            'name' => 'Software Development',
            'description' => 'Pembuatan aplikasi web/mobile.',
            'is_active' => true,
        ]);
        DB::table('faq_qa')->insert([
            [
                'id' => 13,
                'question' => 'Berapa biaya pemasangan?',
                'answer' => 'Paket mulai dari Rp 350.000 per titik.',
                'is_active' => true,
            ],
            [
                'id' => 35,
                'question' => 'Kenapa biaya berbeda?',
                'answer' => 'Biaya tidak bisa disamaratakan dan tergantung lokasi.',
                'is_active' => true,
            ],
            [
                'id' => 36,
                'question' => 'Kapan survei?',
                'answer' => 'Survei dapat dijadwalkan dalam 1 hari kerja.',
                'is_active' => true,
            ],
            [
                'id' => 45,
                'question' => 'Bisa buat aplikasi mobile?',
                'answer' => 'Kami fokus pada CCTV, bukan aplikasi mobile.',
                'is_active' => true,
            ],
        ]);
        DB::table('company_contacts')->insert([
            [
                'id' => 2,
                'type' => 'email',
                'value' => 'support@neevwork.com',
                'is_primary' => true,
            ],
            [
                'id' => 3,
                'type' => 'address',
                'value' => 'Bangkalan, melayani area Surabaya & Madura',
                'is_primary' => true,
            ],
        ]);
        DB::table('office_hours')->insert([
            'open_time' => '09:00:00',
            'close_time' => '17:00:00',
        ]);
        $homepage = '<a href="mailto:admin@neevwork.com">Email</a>'
            .'<span>Lokasi</span><strong>Jl. Raya Kauman No.137, Bangkalan</strong>';

        $codes = collect(
            app(BusinessInformationAuditor::class)->audit($homepage)
        )->pluck('code');

        $this->assertContains('software_mobile_capability_conflict', $codes);
        $this->assertContains('cctv_price_policy_conflict', $codes);
        $this->assertContains('unverified_survey_promises', $codes);
        $this->assertContains('homepage_primary_email_mismatch', $codes);
        $this->assertContains('homepage_primary_address_mismatch', $codes);
    }
}
