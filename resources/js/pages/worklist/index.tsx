import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, patientAge } from '@/lib/format';
import { type BreadcrumbItem, type Paginated, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FlaskConical, PenLine } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Worklist', href: '/worklist' }];

interface SampleRow {
    id: number;
    sample_number: string | null;
    specimen_type: string;
    status: string;
    collected_at: string | null;
    created_at: string;
    visit: {
        id: number;
        visit_number: string;
        visited_at: string;
        patient: { id: number; name: string; mrn: string | null; gender: string; dob: string | null; age_years: number | null };
    };
    visit_tests: { id: number; test: { id: number; code: string; name: string } }[];
}

const STATUSES = [
    { value: 'registered', label: 'Awaiting collection' },
    { value: 'collected', label: 'Collected' },
    { value: 'in_progress', label: 'In progress' },
    { value: 'resulted', label: 'Awaiting verification' },
    { value: 'verified', label: 'Verified' },
    { value: 'reported', label: 'Reported' },
];

export default function WorklistIndex({
    samples,
    counts,
    activeStatus,
}: {
    samples: Paginated<SampleRow>;
    counts: Record<string, number>;
    activeStatus: string;
}) {
    const { flash } = usePage<SharedData>().props;
    const editable = ['collected', 'in_progress', 'resulted'].includes(activeStatus);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Worklist" />
            <div className="flex flex-col gap-4 p-4">
                {flash.success && (
                    <div className="rounded-md border border-green-300 bg-green-50 px-4 py-2 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                        {flash.success}
                    </div>
                )}
                {flash.error && (
                    <div className="rounded-md border border-red-300 bg-red-50 px-4 py-2 text-sm text-red-800 dark:border-red-800 dark:bg-red-950 dark:text-red-200">
                        {flash.error}
                    </div>
                )}

                <div className="flex flex-wrap gap-1.5">
                    {STATUSES.map((status) => (
                        <Link
                            key={status.value}
                            href={`/worklist?status=${status.value}`}
                            preserveState
                            className={`rounded-md border px-3 py-1.5 text-sm ${
                                activeStatus === status.value ? 'bg-primary text-primary-foreground border-primary' : 'hover:bg-accent'
                            }`}
                        >
                            {status.label}
                            <span className="ml-1.5 tabular-nums opacity-70">{counts[status.value] ?? 0}</span>
                        </Link>
                    ))}
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-muted/50 text-muted-foreground border-b text-left">
                                <th className="px-3 py-2 font-medium">Sample</th>
                                <th className="px-3 py-2 font-medium">Patient</th>
                                <th className="px-3 py-2 font-medium">Specimen</th>
                                <th className="px-3 py-2 font-medium">Tests</th>
                                <th className="px-3 py-2 font-medium">Registered</th>
                                <th className="px-3 py-2 text-right font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {samples.data.map((sample) => (
                                <tr key={sample.id} className="hover:bg-accent/50 border-b last:border-0">
                                    <td className="px-3 py-2 font-medium">{sample.sample_number}</td>
                                    <td className="px-3 py-2">
                                        {sample.visit.patient.name}
                                        <span className="text-muted-foreground ml-1 text-xs">
                                            {patientAge(sample.visit.patient.dob, sample.visit.patient.age_years)} /{' '}
                                            {sample.visit.patient.gender}
                                        </span>
                                    </td>
                                    <td className="px-3 py-2">{sample.specimen_type}</td>
                                    <td className="px-3 py-2">
                                        <div className="flex flex-wrap gap-1">
                                            {sample.visit_tests.map((visitTest) => (
                                                <Badge key={visitTest.id} variant="secondary">
                                                    {visitTest.test.code}
                                                </Badge>
                                            ))}
                                        </div>
                                    </td>
                                    <td className="px-3 py-2">{formatDateTime(sample.created_at)}</td>
                                    <td className="px-3 py-2 text-right">
                                        {activeStatus === 'registered' && (
                                            <Button size="sm" variant="outline" onClick={() => router.post(`/samples/${sample.id}/collect`)}>
                                                <FlaskConical className="size-4" /> Collect
                                            </Button>
                                        )}
                                        {editable && (
                                            <Button size="sm" asChild>
                                                <Link href={`/samples/${sample.id}/entry`}>
                                                    <PenLine className="size-4" /> Enter results
                                                </Link>
                                            </Button>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {samples.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="text-muted-foreground px-3 py-8 text-center">
                                        Nothing in this queue.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {samples.last_page > 1 && (
                    <div className="flex flex-wrap gap-1">
                        {samples.links.map((link, index) =>
                            link.url ? (
                                <Link
                                    key={index}
                                    href={link.url}
                                    preserveState
                                    className={`rounded-md border px-3 py-1 text-sm ${link.active ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'}`}
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ) : (
                                <span
                                    key={index}
                                    className="text-muted-foreground rounded-md border px-3 py-1 text-sm"
                                    dangerouslySetInnerHTML={{ __html: link.label }}
                                />
                            ),
                        )}
                    </div>
                )}
            </div>
        </AppLayout>
    );
}
