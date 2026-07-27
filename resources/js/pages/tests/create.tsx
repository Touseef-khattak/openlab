import TestForm, { emptyParameter, type TestFormData } from './test-form';

export default function CreateTest({ categories }: { categories: { id: number; name: string }[] }) {
    const initial: TestFormData = {
        name: '',
        code: '',
        test_category_id: 'none',
        specimen_type: '',
        price: '',
        is_active: true,
        is_panel: false,
        parameters: [emptyParameter()],
    };

    return <TestForm mode="create" categories={categories} initial={initial} submitUrl="/tests" method="post" />;
}
