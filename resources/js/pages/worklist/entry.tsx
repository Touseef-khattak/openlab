import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, patientAge } from '@/lib/format';
import { type BreadcrumbItem, type SharedData } from '@/types';
import { Head, router, usePage } from '@inertiajs/react';
import { CheckCheck, LoaderCircle, Save } from 'lucide-react';
import { useMemo, useRef, useState } from 'react';

interface Range {
    low: number | null;
    high: number | null;
    critical_low: number | null;
    critical_high: number | null;
    normal_text: string | null;
}

interface ParameterRow {
    result_id: number;
    name: string;
    unit: string | null;
    result_type: 'numeric' | 'text' | 'select';
    decimals: number;
    options: string[] | null;
    range: Range | null;
    value: string | null;
    flag: string | null;
    previous: { value: string; flag: string | null; entered_at: string } | null;
}

interface EntryProps {
    sample: {
        id: number;
        sample_number: string | null;
        specimen_type: string;
        status: string;
        visit_number: string;
        visited_at: string;
    };
    patient: { name: string; mrn: string | null; gender: string; dob: string | null; age_years: number | null };
    tests: { visit_test_id: number; code: string; name: string; parameters: ParameterRow[] }[];
}

function computeFlag(raw: string, range: Range | null): string | null {
    if (!range || raw.trim() === '' || isNaN(Number(raw))) return null;
    const value = Number(raw);
    if (range.critical_low !== null && value <= range.critical_low) return 'critical_low';
    if (range.critical_high !== null && value >= range.critical_high) return 'critical_high';
    if (range.low !== null && value < range.low) return 'low';
    if (range.high !== null && value > range.high) return 'high';
    return 'normal';
}

function rangeLabel(range: Range | null, unit: string | null): string {
    if (!range) return '—';
    if (range.normal_text) return range.normal_text;
    const unitSuffix = unit ? ` ${unit}` : '';
    if (range.low !== null && range.high !== null) return `${range.low} – ${range.high}${unitSuffix}`;
    if (range.high !== null) return `< ${range.high}${unitSuffix}`;
    if (range.low !== null) return `> ${range.low}${unitSuffix}`;
    return '—';
}

const flagStyles: Record<string, string> = {
    normal: 'text-muted-foreground',
    low: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    high: 'bg-amber-100 text-amber-900 dark:bg-amber-950 dark:text-amber-200',
    critical_low: 'bg-red-100 text-red-900 dark:bg-red-950 dark:text-red-200',
    critical_high: 'bg-red-100 text-red-900 dark:bg-red-950 dark:text-red-200',
};

const flagLabels: Record<string, string> = {
    low: 'L',
    high: 'H',
    critical_low: 'CRIT ↓',
    critical_high: 'CRIT ↑',
};

export default function ResultEntry({ sample, patient, tests }: EntryProps) {
    const { errors } = usePage<SharedData & { errors: Record<string, string> }>().props;
    const [values, setValues] = useState<Record<number, string>>(() => {
        const initial: Record<number, string> = {};
        for (const test of tests) {
            for (const parameter of test.parameters) {
                initial[parameter.result_id] = parameter.value ?? '';
            }
        }
        return initial;
    });
    const [saving, setSaving] = useState<'draft' | 'submit' | null>(null);

    const inputRefs = useRef<(HTMLInputElement | null)[]>([]);
    const orderedIds = useMemo(() => tests.flatMap((test) => test.parameters.map((p) => p.result_id)), [tests]);

    const focusOffset = (index: number, offset: number) => {
        const target = inputRefs.current[index + offset];
        target?.focus();
        target?.select();
    };

    const handleKey = (event: React.KeyboardEvent, index: number) => {
        if (event.key === 'Enter' || event.key === 'ArrowDown') {
            event.preventDefault();
            focusOffset(index, 1);
        } else if (event.key === 'ArrowUp') {
            event.preventDefault();
            focusOffset(index, -1);
        }
    };

    const save = (action: 'draft' | 'submit') => {
        setSaving(action);
        router.put(
            `/samples/${sample.id}/results`,
            {
                action,
                results: orderedIds.map((id) => ({ id, value: values[id] ?? '' })),
            },
            { onFinish: () => setSaving(null), preserveScroll: true },
        );
    };

    const filledCount = orderedIds.filter((id) => (values[id] ?? '').trim() !== '').length;

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Worklist', href: '/worklist' },
        { title: sample.sample_number ?? `Sample ${sample.id}`, href: `/samples/${sample.id}/entry` },
    ];

    let inputIndex = -1;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Results · ${sample.sample_number}`} />
            <div className="flex flex-col gap-4 p-4">
                <Card>
                    <CardContent className="flex flex-wrap items-center justify-between gap-4 py-4">
                        <div className="flex flex-col">
                            <span className="font-semibold">
                                {patient.name}
                                <span className="text-muted-foreground ml-2 text-sm font-normal">
                                    {patient.mrn} · {patientAge(patient.dob, patient.age_years)} / {patient.gender}
                                </span>
                            </span>
                            <span className="text-muted-foreground text-sm">
                                {sample.sample_number} · {sample.specimen_type} · {sample.visit_number} · {formatDateTime(sample.visited_at)}
                            </span>
                        </div>
                        <div className="flex items-center gap-2">
                            <span className="text-muted-foreground text-sm tabular-nums">
                                {filledCount}/{orderedIds.length} filled
                            </span>
                            <Button variant="outline" onClick={() => save('draft')} disabled={saving !== null}>
                                {saving === 'draft' ? <LoaderCircle className="size-4 animate-spin" /> : <Save className="size-4" />}
                                Save draft
                            </Button>
                            <Button onClick={() => save('submit')} disabled={saving !== null || filledCount < orderedIds.length}>
                                {saving === 'submit' ? <LoaderCircle className="size-4 animate-spin" /> : <CheckCheck className="size-4" />}
                                Submit for verification
                            </Button>
                        </div>
                    </CardContent>
                </Card>

                <InputError message={errors.results} />

                {tests.map((test) => (
                    <Card key={test.visit_test_id}>
                        <CardHeader>
                            <CardTitle className="text-base">
                                <span className="text-muted-foreground mr-2 text-sm">{test.code}</span>
                                {test.name}
                            </CardTitle>
                        </CardHeader>
                        <CardContent>
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-muted-foreground border-b text-left">
                                        <th className="py-1.5 pr-2 font-medium">Parameter</th>
                                        <th className="w-44 py-1.5 pr-2 font-medium">Result</th>
                                        <th className="w-16 py-1.5 pr-2 font-medium">Flag</th>
                                        <th className="w-20 py-1.5 pr-2 font-medium">Unit</th>
                                        <th className="w-40 py-1.5 pr-2 font-medium">Reference</th>
                                        <th className="w-40 py-1.5 font-medium">Previous</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {test.parameters.map((parameter) => {
                                        inputIndex++;
                                        const index = inputIndex;
                                        const raw = values[parameter.result_id] ?? '';
                                        const flag =
                                            parameter.result_type === 'numeric' ? computeFlag(raw, parameter.range) : null;

                                        return (
                                            <tr key={parameter.result_id} className="border-b last:border-0">
                                                <td className="py-1.5 pr-2">{parameter.name}</td>
                                                <td className="py-1.5 pr-2">
                                                    {parameter.result_type === 'select' && parameter.options ? (
                                                        <Select
                                                            value={raw}
                                                            onValueChange={(value) =>
                                                                setValues((prev) => ({ ...prev, [parameter.result_id]: value }))
                                                            }
                                                        >
                                                            <SelectTrigger className="h-8">
                                                                <SelectValue placeholder="—" />
                                                            </SelectTrigger>
                                                            <SelectContent>
                                                                {parameter.options.map((option) => (
                                                                    <SelectItem key={option} value={option}>
                                                                        {option}
                                                                    </SelectItem>
                                                                ))}
                                                            </SelectContent>
                                                        </Select>
                                                    ) : (
                                                        <Input
                                                            ref={(el) => {
                                                                inputRefs.current[index] = el;
                                                            }}
                                                            className="h-8"
                                                            inputMode={parameter.result_type === 'numeric' ? 'decimal' : 'text'}
                                                            value={raw}
                                                            onChange={(event) =>
                                                                setValues((prev) => ({
                                                                    ...prev,
                                                                    [parameter.result_id]: event.target.value,
                                                                }))
                                                            }
                                                            onKeyDown={(event) => handleKey(event, index)}
                                                        />
                                                    )}
                                                </td>
                                                <td className="py-1.5 pr-2">
                                                    {flag && flag !== 'normal' && (
                                                        <span
                                                            className={`rounded px-1.5 py-0.5 text-xs font-semibold ${flagStyles[flag]}`}
                                                        >
                                                            {flagLabels[flag]}
                                                        </span>
                                                    )}
                                                </td>
                                                <td className="text-muted-foreground py-1.5 pr-2">{parameter.unit ?? ''}</td>
                                                <td className="text-muted-foreground py-1.5 pr-2">
                                                    {rangeLabel(parameter.range, parameter.unit)}
                                                </td>
                                                <td className="text-muted-foreground py-1.5">
                                                    {parameter.previous ? (
                                                        <span>
                                                            {parameter.previous.value}
                                                            {parameter.previous.flag && parameter.previous.flag !== 'normal' && (
                                                                <span className="ml-1 text-xs font-semibold">
                                                                    ({flagLabels[parameter.previous.flag]})
                                                                </span>
                                                            )}
                                                            <span className="ml-1 text-xs">
                                                                {new Date(parameter.previous.entered_at).toLocaleDateString('en-GB')}
                                                            </span>
                                                        </span>
                                                    ) : (
                                                        '—'
                                                    )}
                                                </td>
                                            </tr>
                                        );
                                    })}
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                ))}

                <div className="flex justify-end gap-2">
                    <Button variant="outline" onClick={() => save('draft')} disabled={saving !== null}>
                        Save draft
                    </Button>
                    <Button onClick={() => save('submit')} disabled={saving !== null || filledCount < orderedIds.length}>
                        Submit for verification
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
