import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/react';
import { LoaderCircle, Plus, Trash2, X } from 'lucide-react';

type ResultType = 'numeric' | 'text' | 'select';

interface RangeForm {
    gender: string;
    age_min_years: number | string | null;
    age_max_years: number | string | null;
    low: number | string | null;
    high: number | string | null;
    critical_low: number | string | null;
    critical_high: number | string | null;
    normal_text: string | null;
}

interface ParameterForm {
    id?: number;
    name: string;
    unit: string | null;
    result_type: ResultType;
    decimals: number;
    options: string[];
    has_results?: boolean;
    ranges: RangeForm[];
}

export interface TestFormData {
    id?: number;
    name: string;
    code: string;
    test_category_id: number | string | null;
    specimen_type: string | null;
    price: string;
    is_active: boolean;
    is_panel: boolean;
    parameters: ParameterForm[];
    [key: string]: unknown;
}

export function emptyRange(): RangeForm {
    return { gender: 'all', age_min_years: '', age_max_years: '', low: '', high: '', critical_low: '', critical_high: '', normal_text: '' };
}

export function emptyParameter(): ParameterForm {
    return { name: '', unit: '', result_type: 'numeric', decimals: 2, options: [], ranges: [emptyRange()] };
}

export default function TestForm({
    mode,
    categories,
    initial,
    submitUrl,
    method,
}: {
    mode: 'create' | 'edit';
    categories: { id: number; name: string }[];
    initial: TestFormData;
    submitUrl: string;
    method: 'post' | 'put';
}) {
    const { data, setData, post, put, processing, errors, transform } = useForm<TestFormData>(initial);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Tests', href: '/tests' },
        { title: mode === 'create' ? 'New test' : `Edit ${initial.name}`, href: submitUrl },
    ];

    const setParam = (index: number, patch: Partial<ParameterForm>) =>
        setData(
            'parameters',
            data.parameters.map((p, i) => (i === index ? { ...p, ...patch } : p)),
        );

    const setRange = (pIndex: number, rIndex: number, patch: Partial<RangeForm>) =>
        setData(
            'parameters',
            data.parameters.map((p, i) =>
                i === pIndex ? { ...p, ranges: p.ranges.map((r, j) => (j === rIndex ? { ...r, ...patch } : r)) } : p,
            ),
        );

    const setType = (panel: boolean) => {
        if (panel) {
            setData('is_panel', true);
        } else {
            // Single: collapse to one parameter that mirrors the test name.
            setData((current) => ({
                ...current,
                is_panel: false,
                parameters: [current.parameters[0] ?? emptyParameter()],
            }));
        }
    };

    const addParam = () => setData('parameters', [...data.parameters, emptyParameter()]);
    const removeParam = (index: number) =>
        setData('parameters', data.parameters.filter((_, i) => i !== index));

    const addRange = (pIndex: number) => setParam(pIndex, { ranges: [...data.parameters[pIndex].ranges, emptyRange()] });
    const removeRange = (pIndex: number, rIndex: number) =>
        setParam(pIndex, { ranges: data.parameters[pIndex].ranges.filter((_, j) => j !== rIndex) });

    const submit = (event: React.FormEvent) => {
        event.preventDefault();
        transform((form) => ({
            ...form,
            test_category_id: form.test_category_id === 'none' || form.test_category_id === '' ? null : Number(form.test_category_id),
            price: form.price === '' ? '0' : form.price,
        }));
        const options = { preserveScroll: true };
        method === 'post' ? post(submitUrl, options) : put(submitUrl, options);
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={mode === 'create' ? 'New test' : `Edit ${initial.name}`} />
            <form onSubmit={submit} className="flex flex-col gap-4 p-4">
                {/* ---- test details ---- */}
                <Card>
                    <CardHeader>
                        <CardTitle>{mode === 'create' ? 'New test' : `Edit ${initial.name}`}</CardTitle>
                    </CardHeader>
                    <CardContent className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div className="lg:col-span-2">
                            <Label htmlFor="name">Test name *</Label>
                            <Input id="name" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                            <InputError message={errors.name} />
                        </div>
                        <div>
                            <Label htmlFor="code">Code</Label>
                            <Input
                                id="code"
                                value={data.code}
                                placeholder="auto from name"
                                onChange={(e) => setData('code', e.target.value)}
                            />
                            <InputError message={errors.code} />
                        </div>
                        <div>
                            <Label>Category</Label>
                            <Select
                                value={data.test_category_id ? String(data.test_category_id) : 'none'}
                                onValueChange={(value) => setData('test_category_id', value)}
                            >
                                <SelectTrigger>
                                    <SelectValue placeholder="Uncategorized" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">Uncategorized</SelectItem>
                                    {categories.map((category) => (
                                        <SelectItem key={category.id} value={String(category.id)}>
                                            {category.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>
                        <div>
                            <Label htmlFor="specimen">Specimen type</Label>
                            <Input
                                id="specimen"
                                value={data.specimen_type ?? ''}
                                placeholder="e.g. Serum, EDTA Whole Blood"
                                onChange={(e) => setData('specimen_type', e.target.value)}
                            />
                            <InputError message={errors.specimen_type} />
                        </div>
                        <div>
                            <Label htmlFor="price">Price *</Label>
                            <Input id="price" type="number" min={0} value={data.price} onChange={(e) => setData('price', e.target.value)} />
                            <InputError message={errors.price} />
                        </div>
                        <div className="flex items-end">
                            <label className="flex items-center gap-2 text-sm">
                                <input
                                    type="checkbox"
                                    checked={data.is_active}
                                    onChange={(e) => setData('is_active', e.target.checked)}
                                />
                                Active (available to order)
                            </label>
                        </div>

                        <div className="lg:col-span-3">
                            <Label>Value type</Label>
                            <div className="flex gap-2">
                                <button
                                    type="button"
                                    onClick={() => setType(false)}
                                    className={`flex-1 rounded-md border px-4 py-2 text-left text-sm ${
                                        !data.is_panel ? 'border-primary bg-accent' : 'hover:bg-accent'
                                    }`}
                                >
                                    <span className="font-medium">Single</span>
                                    <span className="text-muted-foreground block text-xs">One result (e.g. HbA1c, ESR)</span>
                                </button>
                                <button
                                    type="button"
                                    onClick={() => setType(true)}
                                    className={`flex-1 rounded-md border px-4 py-2 text-left text-sm ${
                                        data.is_panel ? 'border-primary bg-accent' : 'hover:bg-accent'
                                    }`}
                                >
                                    <span className="font-medium">Multiple</span>
                                    <span className="text-muted-foreground block text-xs">Panel with many parameters (e.g. CBC)</span>
                                </button>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                {/* ---- parameters ---- */}
                {typeof errors.parameters === 'string' && (
                    <div className="rounded-md border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                        {errors.parameters}
                    </div>
                )}

                {data.parameters.map((parameter, pIndex) => (
                    <Card key={pIndex}>
                        <CardHeader>
                            <CardTitle className="flex items-center justify-between text-base">
                                <span>{data.is_panel ? `Parameter ${pIndex + 1}` : 'Reference value'}</span>
                                <div className="flex items-center gap-2">
                                    {parameter.has_results && <Badge variant="secondary">has results</Badge>}
                                    {data.is_panel && data.parameters.length > 1 && !parameter.has_results && (
                                        <Button type="button" variant="ghost" size="sm" onClick={() => removeParam(pIndex)}>
                                            <Trash2 className="size-4 text-red-600" />
                                        </Button>
                                    )}
                                </div>
                            </CardTitle>
                        </CardHeader>
                        <CardContent className="flex flex-col gap-4">
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                {data.is_panel && (
                                    <div className="lg:col-span-2">
                                        <Label>Parameter name *</Label>
                                        <Input value={parameter.name} onChange={(e) => setParam(pIndex, { name: e.target.value })} />
                                        <InputError message={errors[`parameters.${pIndex}.name` as keyof typeof errors] as string} />
                                    </div>
                                )}
                                <div>
                                    <Label>Result type</Label>
                                    <Select
                                        value={parameter.result_type}
                                        onValueChange={(value) => setParam(pIndex, { result_type: value as ResultType })}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="numeric">Numeric</SelectItem>
                                            <SelectItem value="text">Text</SelectItem>
                                            <SelectItem value="select">Select (list)</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div>
                                    <Label>Unit</Label>
                                    <Input
                                        value={parameter.unit ?? ''}
                                        placeholder="e.g. g/dL"
                                        onChange={(e) => setParam(pIndex, { unit: e.target.value })}
                                    />
                                </div>
                                {parameter.result_type === 'numeric' && (
                                    <div>
                                        <Label>Decimals</Label>
                                        <Input
                                            type="number"
                                            min={0}
                                            max={4}
                                            value={parameter.decimals}
                                            onChange={(e) => setParam(pIndex, { decimals: Number(e.target.value) })}
                                        />
                                    </div>
                                )}
                            </div>

                            {/* select options */}
                            {parameter.result_type === 'select' && (
                                <div>
                                    <Label>Options</Label>
                                    <div className="flex flex-wrap gap-2">
                                        {parameter.options.map((option, oIndex) => (
                                            <div key={oIndex} className="flex items-center gap-1">
                                                <Input
                                                    className="h-8 w-36"
                                                    value={option}
                                                    onChange={(e) =>
                                                        setParam(pIndex, {
                                                            options: parameter.options.map((o, k) => (k === oIndex ? e.target.value : o)),
                                                        })
                                                    }
                                                />
                                                <button
                                                    type="button"
                                                    onClick={() =>
                                                        setParam(pIndex, { options: parameter.options.filter((_, k) => k !== oIndex) })
                                                    }
                                                >
                                                    <X className="size-4" />
                                                </button>
                                            </div>
                                        ))}
                                        <Button
                                            type="button"
                                            variant="outline"
                                            size="sm"
                                            onClick={() => setParam(pIndex, { options: [...parameter.options, ''] })}
                                        >
                                            <Plus className="size-4" /> Option
                                        </Button>
                                    </div>
                                    <InputError message={errors[`parameters.${pIndex}.options` as keyof typeof errors] as string} />
                                </div>
                            )}

                            {/* reference ranges (numeric only) */}
                            {parameter.result_type === 'numeric' && (
                                <div className="flex flex-col gap-2">
                                    <div className="flex items-center justify-between">
                                        <Label>Reference ranges</Label>
                                        <Button type="button" variant="outline" size="sm" onClick={() => addRange(pIndex)}>
                                            <Plus className="size-4" /> Add range
                                        </Button>
                                    </div>
                                    <div className="overflow-x-auto">
                                        <table className="w-full min-w-[720px] text-sm">
                                            <thead>
                                                <tr className="text-muted-foreground text-left">
                                                    <th className="pr-2 pb-1 font-medium">Applies to</th>
                                                    <th className="pr-2 pb-1 font-medium">Age min (y)</th>
                                                    <th className="pr-2 pb-1 font-medium">Age max (y)</th>
                                                    <th className="pr-2 pb-1 font-medium">Min</th>
                                                    <th className="pr-2 pb-1 font-medium">Max</th>
                                                    <th className="pr-2 pb-1 font-medium">Crit low</th>
                                                    <th className="pr-2 pb-1 font-medium">Crit high</th>
                                                    <th className="pr-2 pb-1 font-medium">Normal text</th>
                                                    <th />
                                                </tr>
                                            </thead>
                                            <tbody>
                                                {parameter.ranges.map((range, rIndex) => (
                                                    <tr key={rIndex}>
                                                        <td className="pr-2 pb-1">
                                                            <Select
                                                                value={range.gender}
                                                                onValueChange={(value) => setRange(pIndex, rIndex, { gender: value })}
                                                            >
                                                                <SelectTrigger className="h-8 w-28">
                                                                    <SelectValue />
                                                                </SelectTrigger>
                                                                <SelectContent>
                                                                    <SelectItem value="all">All</SelectItem>
                                                                    <SelectItem value="male">Male</SelectItem>
                                                                    <SelectItem value="female">Female</SelectItem>
                                                                    <SelectItem value="other">Other</SelectItem>
                                                                </SelectContent>
                                                            </Select>
                                                        </td>
                                                        <td className="pr-2 pb-1">
                                                            <Input
                                                                className="h-8 w-16"
                                                                type="number"
                                                                value={range.age_min_years ?? ''}
                                                                onChange={(e) => setRange(pIndex, rIndex, { age_min_years: e.target.value })}
                                                            />
                                                        </td>
                                                        <td className="pr-2 pb-1">
                                                            <Input
                                                                className="h-8 w-16"
                                                                type="number"
                                                                value={range.age_max_years ?? ''}
                                                                onChange={(e) => setRange(pIndex, rIndex, { age_max_years: e.target.value })}
                                                            />
                                                        </td>
                                                        <td className="pr-2 pb-1">
                                                            <Input
                                                                className="h-8 w-20"
                                                                type="number"
                                                                step="any"
                                                                value={range.low ?? ''}
                                                                onChange={(e) => setRange(pIndex, rIndex, { low: e.target.value })}
                                                            />
                                                        </td>
                                                        <td className="pr-2 pb-1">
                                                            <Input
                                                                className="h-8 w-20"
                                                                type="number"
                                                                step="any"
                                                                value={range.high ?? ''}
                                                                onChange={(e) => setRange(pIndex, rIndex, { high: e.target.value })}
                                                            />
                                                            <InputError
                                                                message={
                                                                    errors[
                                                                        `parameters.${pIndex}.ranges.${rIndex}.high` as keyof typeof errors
                                                                    ] as string
                                                                }
                                                            />
                                                        </td>
                                                        <td className="pr-2 pb-1">
                                                            <Input
                                                                className="h-8 w-20"
                                                                type="number"
                                                                step="any"
                                                                value={range.critical_low ?? ''}
                                                                onChange={(e) => setRange(pIndex, rIndex, { critical_low: e.target.value })}
                                                            />
                                                        </td>
                                                        <td className="pr-2 pb-1">
                                                            <Input
                                                                className="h-8 w-20"
                                                                type="number"
                                                                step="any"
                                                                value={range.critical_high ?? ''}
                                                                onChange={(e) => setRange(pIndex, rIndex, { critical_high: e.target.value })}
                                                            />
                                                        </td>
                                                        <td className="pr-2 pb-1">
                                                            <Input
                                                                className="h-8 w-32"
                                                                value={range.normal_text ?? ''}
                                                                placeholder="e.g. Negative"
                                                                onChange={(e) => setRange(pIndex, rIndex, { normal_text: e.target.value })}
                                                            />
                                                        </td>
                                                        <td className="pb-1">
                                                            {parameter.ranges.length > 1 && (
                                                                <button type="button" onClick={() => removeRange(pIndex, rIndex)}>
                                                                    <X className="size-4" />
                                                                </button>
                                                            )}
                                                        </td>
                                                    </tr>
                                                ))}
                                            </tbody>
                                        </table>
                                    </div>
                                    <p className="text-muted-foreground text-xs">
                                        Leave a row blank for tests without a numeric range. Use “All” unless the normal range differs by
                                        sex or age.
                                    </p>
                                </div>
                            )}
                        </CardContent>
                    </Card>
                ))}

                {data.is_panel && (
                    <div>
                        <Button type="button" variant="outline" onClick={addParam}>
                            <Plus className="size-4" /> Add parameter
                        </Button>
                    </div>
                )}

                <div className="flex justify-end gap-2">
                    <Button type="button" variant="outline" onClick={() => history.back()}>
                        Cancel
                    </Button>
                    <Button type="submit" disabled={processing}>
                        {processing && <LoaderCircle className="size-4 animate-spin" />}
                        {mode === 'create' ? 'Create test' : 'Save changes'}
                    </Button>
                </div>
            </form>
        </AppLayout>
    );
}
