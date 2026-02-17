<?php

namespace Tests\Feature;

use App\Models\Dnc;
use App\Models\DncPeriod;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DncPeriodUpdateTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_updates_current_period_instead_of_creating_new_one()
    {
        // Crear DNC (el Observer creará el período automáticamente)
        $dnc = Dnc::create([
            'name' => 'DNC Test',
            'start_date' => '2025-12-15 10:00:00',
            'end_date' => '2026-01-16 12:59:00',
            'is_active' => true,
        ]);

        $initialPeriodsCount = DncPeriod::count();
        $periodId = $dnc->currentPeriod->id;

        // Actualizar fechas de la DNC (el Observer actualizará el período)
        $dnc->update([
            'end_date' => '2026-01-17 00:00:00',
        ]);

        // Verificar que NO se creó un nuevo período
        $this->assertEquals($initialPeriodsCount, DncPeriod::count());

        // Verificar que el período se actualizó
        $dnc->refresh();
        $period = $dnc->currentPeriod;
        $this->assertEquals('2026-01-17 00:00:00', $period->end_date->format('Y-m-d H:i:s'));
        $this->assertEquals($periodId, $period->id);
        $this->assertTrue($period->is_current);
    }

    /** @test */
    public function it_creates_period_on_dnc_creation()
    {
        $dnc = Dnc::create([
            'name' => 'DNC Nueva',
            'start_date' => '2025-12-15 10:00:00',
            'end_date' => '2026-01-16 12:59:00',
            'is_active' => true,
        ]);

        $this->assertEquals(1, $dnc->periods()->count());
        $this->assertTrue($dnc->currentPeriod->is_current);
    }
}
