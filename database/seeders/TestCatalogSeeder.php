<?php

namespace Database\Seeders;

use App\Models\ReferenceRange;
use App\Models\Test;
use App\Models\TestCategory;
use App\Models\TestParameter;
use Illuminate\Database\Seeder;

/**
 * Seeds a realistic starter catalog for a small clinical lab.
 * Range sources: standard adult reference intervals; labs adjust
 * per their analyzers, so all of this is editable data, not code.
 *
 * Parameter shape: [name, unit, decimals, ranges[], type?, options?]
 * Range shape:     [gender, low, high, critical_low, critical_high]
 */
class TestCatalogSeeder extends Seeder
{
    public function run(): void
    {
        $catalog = [
            'Hematology' => [
                [
                    'code' => 'CBC', 'name' => 'Complete Blood Count', 'price' => 800,
                    'specimen' => 'Whole Blood (EDTA)', 'panel' => true,
                    'parameters' => [
                        ['Hemoglobin', 'g/dL', 1, [
                            ['male', 13.5, 17.5, 7.0, 20.0],
                            ['female', 12.0, 15.5, 7.0, 20.0],
                        ]],
                        ['RBC Count', 'x10^6/uL', 2, [
                            ['male', 4.5, 5.9, null, null],
                            ['female', 4.1, 5.1, null, null],
                        ]],
                        ['Hematocrit (HCT)', '%', 1, [
                            ['male', 41, 53, null, null],
                            ['female', 36, 46, null, null],
                        ]],
                        ['MCV', 'fL', 1, [[null, 80, 100, null, null]]],
                        ['MCH', 'pg', 1, [[null, 27, 32, null, null]]],
                        ['MCHC', 'g/dL', 1, [[null, 32, 36, null, null]]],
                        ['WBC Count', 'x10^3/uL', 2, [[null, 4.0, 11.0, 1.0, 50.0]]],
                        ['Platelet Count', 'x10^3/uL', 0, [[null, 150, 400, 20, 1000]]],
                        ['Neutrophils', '%', 0, [[null, 40, 75, null, null]]],
                        ['Lymphocytes', '%', 0, [[null, 20, 45, null, null]]],
                        ['Monocytes', '%', 0, [[null, 2, 10, null, null]]],
                        ['Eosinophils', '%', 0, [[null, 1, 6, null, null]]],
                    ],
                ],
                [
                    'code' => 'ESR', 'name' => 'Erythrocyte Sedimentation Rate', 'price' => 300,
                    'specimen' => 'Whole Blood (EDTA)',
                    'parameters' => [
                        ['ESR', 'mm/hr', 0, [
                            ['male', 0, 15, null, null],
                            ['female', 0, 20, null, null],
                        ]],
                    ],
                ],
            ],
            'Biochemistry' => [
                [
                    'code' => 'GLU-F', 'name' => 'Blood Glucose (Fasting)', 'price' => 250,
                    'specimen' => 'Serum',
                    'parameters' => [
                        ['Glucose (Fasting)', 'mg/dL', 0, [[null, 70, 100, 40, 500]]],
                    ],
                ],
                [
                    'code' => 'GLU-R', 'name' => 'Blood Glucose (Random)', 'price' => 250,
                    'specimen' => 'Serum',
                    'parameters' => [
                        ['Glucose (Random)', 'mg/dL', 0, [[null, 70, 140, 40, 500]]],
                    ],
                ],
                [
                    'code' => 'HBA1C', 'name' => 'Glycated Hemoglobin (HbA1c)', 'price' => 1500,
                    'specimen' => 'Whole Blood (EDTA)',
                    'parameters' => [
                        ['HbA1c', '%', 1, [[null, 4.0, 5.6, null, null]]],
                    ],
                ],
                [
                    'code' => 'LFT', 'name' => 'Liver Function Tests', 'price' => 1200,
                    'specimen' => 'Serum', 'panel' => true,
                    'parameters' => [
                        ['Bilirubin (Total)', 'mg/dL', 2, [[null, 0.2, 1.2, null, 15.0]]],
                        ['Bilirubin (Direct)', 'mg/dL', 2, [[null, 0.0, 0.3, null, null]]],
                        ['ALT (SGPT)', 'U/L', 0, [
                            ['male', 10, 50, null, null],
                            ['female', 10, 35, null, null],
                        ]],
                        ['AST (SGOT)', 'U/L', 0, [[null, 10, 40, null, null]]],
                        ['Alkaline Phosphatase', 'U/L', 0, [[null, 44, 147, null, null]]],
                    ],
                ],
                [
                    'code' => 'RFT', 'name' => 'Renal Function Tests', 'price' => 1000,
                    'specimen' => 'Serum', 'panel' => true,
                    'parameters' => [
                        ['Urea', 'mg/dL', 0, [[null, 17, 43, null, 200]]],
                        ['Creatinine', 'mg/dL', 2, [
                            ['male', 0.7, 1.3, null, 10.0],
                            ['female', 0.6, 1.1, null, 10.0],
                        ]],
                        ['Uric Acid', 'mg/dL', 1, [
                            ['male', 3.5, 7.2, null, null],
                            ['female', 2.6, 6.0, null, null],
                        ]],
                    ],
                ],
                [
                    'code' => 'LIPID', 'name' => 'Lipid Profile', 'price' => 1400,
                    'specimen' => 'Serum', 'panel' => true,
                    'parameters' => [
                        ['Cholesterol (Total)', 'mg/dL', 0, [[null, null, 200, null, null]]],
                        ['Triglycerides', 'mg/dL', 0, [[null, null, 150, null, null]]],
                        ['HDL Cholesterol', 'mg/dL', 0, [
                            ['male', 40, null, null, null],
                            ['female', 50, null, null, null],
                        ]],
                        ['LDL Cholesterol', 'mg/dL', 0, [[null, null, 100, null, null]]],
                    ],
                ],
            ],
            'Serology' => [
                [
                    'code' => 'HBSAG', 'name' => 'Hepatitis B Surface Antigen (HBsAg)', 'price' => 500,
                    'specimen' => 'Serum',
                    'parameters' => [
                        ['HBsAg', null, 0, [], 'select', ['Non-Reactive', 'Reactive']],
                    ],
                ],
                [
                    'code' => 'HCV', 'name' => 'Anti-HCV Antibodies', 'price' => 500,
                    'specimen' => 'Serum',
                    'parameters' => [
                        ['Anti-HCV', null, 0, [], 'select', ['Non-Reactive', 'Reactive']],
                    ],
                ],
            ],
            'Urine Analysis' => [
                [
                    'code' => 'URE', 'name' => 'Urine Routine Examination', 'price' => 400,
                    'specimen' => 'Urine', 'panel' => true,
                    'parameters' => [
                        ['Color', null, 0, [], 'text'],
                        ['pH', null, 1, [[null, 4.5, 8.0, null, null]]],
                        ['Specific Gravity', null, 3, [[null, 1.005, 1.030, null, null]]],
                        ['Protein', null, 0, [], 'select', ['Nil', 'Trace', '+', '++', '+++']],
                        ['Glucose', null, 0, [], 'select', ['Nil', 'Trace', '+', '++', '+++']],
                        ['Pus Cells', '/HPF', 0, [], 'text'],
                        ['Red Blood Cells', '/HPF', 0, [], 'text'],
                    ],
                ],
            ],
        ];

        $categoryOrder = 0;
        foreach ($catalog as $categoryName => $tests) {
            $category = TestCategory::firstOrCreate(
                ['name' => $categoryName],
                ['display_order' => $categoryOrder++],
            );

            $testOrder = 0;
            foreach ($tests as $def) {
                $test = Test::updateOrCreate(
                    ['code' => $def['code']],
                    [
                        'test_category_id' => $category->id,
                        'name' => $def['name'],
                        'price' => $def['price'],
                        'specimen_type' => $def['specimen'],
                        'is_panel' => $def['panel'] ?? false,
                        'display_order' => $testOrder++,
                    ],
                );

                $paramOrder = 0;
                foreach ($def['parameters'] as $paramDef) {
                    [$name, $unit, $decimals, $ranges] = $paramDef;

                    $parameter = TestParameter::updateOrCreate(
                        ['test_id' => $test->id, 'name' => $name],
                        [
                            'unit' => $unit,
                            'decimals' => $decimals,
                            'result_type' => $paramDef[4] ?? 'numeric',
                            'options' => $paramDef[5] ?? null,
                            'display_order' => $paramOrder++,
                        ],
                    );

                    foreach ($ranges as [$gender, $low, $high, $criticalLow, $criticalHigh]) {
                        ReferenceRange::updateOrCreate(
                            [
                                'test_parameter_id' => $parameter->id,
                                'gender' => $gender,
                                'age_min_months' => null,
                                'age_max_months' => null,
                            ],
                            [
                                'low' => $low,
                                'high' => $high,
                                'critical_low' => $criticalLow,
                                'critical_high' => $criticalHigh,
                            ],
                        );
                    }
                }
            }
        }
    }
}
