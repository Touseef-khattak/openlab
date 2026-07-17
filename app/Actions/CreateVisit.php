<?php

namespace App\Actions;

use App\Enums\PaymentStatus;
use App\Enums\SampleStatus;
use App\Models\Patient;
use App\Models\Result;
use App\Models\Sample;
use App\Models\Test;
use App\Models\User;
use App\Models\Visit;
use App\Models\VisitTest;
use Illuminate\Support\Facades\DB;

class CreateVisit
{
    /**
     * Registers a visit end-to-end: resolves the patient, snapshots test
     * prices, opens one sample per specimen type, and pre-creates empty
     * result rows so the bench worklist sees pending work immediately.
     *
     * @param  array{patient: array, doctor_id: ?int, test_ids: int[], discount: float, paid_amount: float}  $data
     */
    public function handle(array $data, User $user): Visit
    {
        return DB::transaction(function () use ($data, $user) {
            $patient = $this->resolvePatient($data['patient']);

            $tests = Test::with('parameters')
                ->whereIn('id', $data['test_ids'])
                ->get();

            $total = $tests->sum('price');
            $discount = min((float) ($data['discount'] ?? 0), (float) $total);
            $net = $total - $discount;
            $paid = min((float) ($data['paid_amount'] ?? 0), $net);

            $visit = Visit::create([
                'visit_number' => Visit::nextNumber(),
                'patient_id' => $patient->id,
                'doctor_id' => $data['doctor_id'] ?? null,
                'visited_at' => now(),
                'total_amount' => $total,
                'discount' => $discount,
                'net_amount' => $net,
                'paid_amount' => $paid,
                'payment_status' => match (true) {
                    $paid >= $net => PaymentStatus::Paid,
                    $paid > 0 => PaymentStatus::Partial,
                    default => PaymentStatus::Unpaid,
                },
                'created_by' => $user->id,
            ]);

            $samples = [];
            foreach ($tests->groupBy(fn (Test $test) => $test->specimen_type ?? 'Other') as $specimen => $specimenTests) {
                $sample = Sample::create([
                    'visit_id' => $visit->id,
                    'sample_number' => $visit->visit_number . '-S' . (count($samples) + 1),
                    'specimen_type' => $specimen,
                    'status' => SampleStatus::Registered,
                ]);
                $samples[$specimen] = $sample;
            }

            foreach ($tests as $test) {
                $visitTest = VisitTest::create([
                    'visit_id' => $visit->id,
                    'test_id' => $test->id,
                    'sample_id' => $samples[$test->specimen_type ?? 'Other']->id,
                    'price' => $test->price,
                ]);

                foreach ($test->parameters as $parameter) {
                    Result::create([
                        'visit_test_id' => $visitTest->id,
                        'test_parameter_id' => $parameter->id,
                    ]);
                }
            }

            return $visit;
        });
    }

    private function resolvePatient(array $data): Patient
    {
        if (! empty($data['id'])) {
            return Patient::findOrFail($data['id']);
        }

        $patient = Patient::create([
            'name' => $data['name'],
            'cnic' => $data['cnic'] ?? null,
            'phone' => $data['phone'] ?? null,
            'gender' => $data['gender'],
            'dob' => $data['dob'] ?? null,
            'age_years' => $data['age_years'] ?? null,
        ]);
        $patient->assignMrn();

        return $patient;
    }
}
