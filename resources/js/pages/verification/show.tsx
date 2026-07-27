import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, patientAge } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { LoaderCircle, ShieldCheck, Undo2 } from 'lucide-react';
import { useState } from 'react';

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
    range: Range | null;
    value: string | null;
    flag: string | null;
    previous: { value: string; flag: string | null; entered_at: string } | null;
}

interface VerifyProps {
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

export default function VerificationShow({ sample, patient, tests }: VerifyProps) {
    const [acting, setActing] = useState<'verify' | 'reject' | null>(null);

    const act = (action: 'verify' | 'reject') => {
        setActing(action);
        router.post(`/samples/${sample.id}/${action}`, {}, { onFinish: () => setActing(null) });
    };

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Verification', href: '/verification' },
        { title: sample.sample_number ?? `Sample ${sample.id}`, href: `/samples/${sample.id}/verify` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title={`Verify · ${sample.sample_number}`} />
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
                            <Button variant="outline" onClick={() => act('reject')} disabled={acting !== null}>
                                {acting === 'reject' ? <LoaderCircle className="size-4 animate-spin" /> : <Undo2 className="size-4" />}
                                Return to bench
                            </Button>
                            <Button onClick={() => act('verify')} disabled={acting !== null}>
                                {acting === 'verify' ? <LoaderCircle className="size-4 animate-spin" /> : <ShieldCheck className="size-4" />}
                                Verify & sign off
                            </Button>
                        </div>
                    </CardContent>
                </Card>

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
                                        <th className="w-32 py-1.5 pr-2 font-medium">Result</th>
                                        <th className="w-20 py-1.5 pr-2 font-medium">Flag</th>
                                        <th className="w-20 py-1.5 pr-2 font-medium">Unit</th>
                                        <th className="w-40 py-1.5 pr-2 font-medium">Reference</th>
                                        <th className="w-40 py-1.5 font-medium">Previous</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {test.parameters.map((parameter) => (
                                        <tr
                                            key={parameter.result_id}
                                            className={`border-b last:border-0 ${
                                                parameter.flag?.startsWith('critical') ? 'bg-red-50 dark:bg-red-950/30' : ''
                                            }`}
                                        >
                                            <td className="py-1.5 pr-2">{parameter.name}</td>
                                            <td className="py-1.5 pr-2 font-semibold">{parameter.value ?? '—'}</td>
                                            <td className="py-1.5 pr-2">
                                                {parameter.flag && parameter.flag !== 'normal' && (
                                                    <span className={`rounded px-1.5 py-0.5 text-xs font-semibold ${flagStyles[parameter.flag]}`}>
                                                        {flagLabels[parameter.flag]}
                                                    </span>
                                                )}
                                            </td>
                                            <td className="text-muted-foreground py-1.5 pr-2">{parameter.unit ?? ''}</td>
                                            <td className="text-muted-foreground py-1.5 pr-2">{rangeLabel(parameter.range, parameter.unit)}</td>
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
                                    ))}
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>
                ))}
            </div>
        </AppLayout>
    );
}
