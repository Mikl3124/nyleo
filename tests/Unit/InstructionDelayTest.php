<?php

namespace Tests\Unit;

use App\Enums\DossierStatus;
use App\Enums\Procedure;
use App\Models\Dossier;
use Carbon\Carbon;
use Tests\TestCase;

class InstructionDelayTest extends TestCase
{
    private function dossier(string $procedure, bool $house = true, bool $abf = false): Dossier
    {
        $d = new Dossier;
        $d->procedure = Procedure::from($procedure);
        $d->is_individual_house = $house;
        $d->abf = $abf;
        $d->deposit_date = Carbon::parse('2026-01-31');

        return $d;
    }

    public function test_legal_instruction_delays(): void
    {
        $this->assertSame(1, $this->dossier('DP')->instructionMonths());
        $this->assertSame(2, $this->dossier('DP', abf: true)->instructionMonths());
        $this->assertSame(2, $this->dossier('PC')->instructionMonths());
        $this->assertSame(3, $this->dossier('PC', house: false)->instructionMonths());
        $this->assertSame(3, $this->dossier('PC', abf: true)->instructionMonths());
    }

    public function test_decision_date_does_not_overflow_month_end(): void
    {
        $this->assertSame('2026-02-28', $this->dossier('DP')->computeDecisionDueDate()->toDateString());
    }

    public function test_every_status_maps_to_a_client_step(): void
    {
        $steps = array_keys(DossierStatus::clientSteps());
        foreach (DossierStatus::cases() as $status) {
            $this->assertContains($status->clientStep(), $steps);
        }
    }
}
