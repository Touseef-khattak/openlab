import TestForm, { type TestFormData } from './test-form';

export default function EditTest({ categories, test }: { categories: { id: number; name: string }[]; test: TestFormData }) {
    const initial: TestFormData = {
        ...test,
        test_category_id: test.test_category_id ?? 'none',
        specimen_type: test.specimen_type ?? '',
        // Ensure at least one range row per numeric parameter so the grid is editable.
        parameters: test.parameters.map((p) => ({
            ...p,
            ranges: p.ranges.length > 0 ? p.ranges : [{ gender: 'all', age_min_years: '', age_max_years: '', low: '', high: '', critical_low: '', critical_high: '', normal_text: '' }],
        })),
    };

    return <TestForm mode="edit" categories={categories} initial={initial} submitUrl={`/tests/${test.id}`} method="put" />;
}
