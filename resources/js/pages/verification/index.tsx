import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime, patientAge } from '@/lib/format';
import { type BreadcrumbItem, type Paginated, type SharedData } from '@/types';
import { Head, Link, usePage } from '@inertiajs/react';
import { ShieldCheck } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Verification', href: '/verification' }];

interface VerificationRow {
    id: number;
    sample_number: string | null;
    specimen_type: string;
    tests: string[];
    abnormal_count: number;
    critical_count: number;
    entered_at: string | null;
    visit: {
        id: number;
        visit_number: string;
        visited_at: string;
        patient: { id: number; name: string; mrn: string | null; gender: string; dob: string | null; age_years: number | null };
    };
}

export default function VerificationIndex({ samples }: { samples: Paginated<VerificationRow> }) {
    const { flash } = usePage<SharedData>().props;

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Verification" />
            <div className="flex flex-col gap-4 p-4">
                {flash.success && (
                    <div className="rounded-md border border-green-300 bg-green-50 px-4 py-2 text-sm text-green-800 dark:border-green-800 dark:bg-green-950 dark:text-green-200">
                        {flash.success}
                    </div>
                )}

                <h1 className="text-xl font-semibold">Awaiting verification ({samples.total})</h1>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-muted/50 text-muted-foreground border-b text-left">
                                <th className="px-3 py-2 font-medium">Sample</th>
                                <th className="px-3 py-2 font-medium">Patient</th>
                                <th className="px-3 py-2 font-medium">Tests</th>
                                <th className="px-3 py-2 font-medium">Flags</th>
                                <th className="px-3 py-2 font-medium">Entered</th>
                                <th className="px-3 py-2 text-right font-medium">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            {samples.data.map((sample) => (
                                <tr key={sample.id} className="hover:bg-accent/50 border-b last:border-0">
                                    <td className="px-3 py-2 font-medium">
                                        {sample.sample_number}
                                        <span className="text-muted-foreground ml-1 text-xs">{sample.specimen_type}</span>
                                    </td>
                                    <td className="px-3 py-2">
                                        {sample.visit.patient.name}
                                        <span className="text-muted-foreground ml-1 text-xs">
                                            {patientAge(sample.visit.patient.dob, sample.visit.patient.age_years)} /{' '}
                                            {sample.visit.patient.gender}
                                        </span>
                                    </td>
                                    <td className="px-3 py-2">
                                        <div className="flex flex-wrap gap-1">
                                            {sample.tests.map((code) => (
                                                <Badge key={code} variant="secondary">
                                                    {code}
                                                </Badge>
                                            ))}
                                        </div>
                                    </td>
                                    <td className="px-3 py-2">
                                        {sample.critical_count > 0 && (
                                            <Badge variant="destructive" className="mr-1">
                                                {sample.critical_count} critical
                                            </Badge>
                                        )}
                                        {sample.abnormal_count > 0 ? (
                                            <span className="text-amber-600 dark:text-amber-400">{sample.abnormal_count} abnormal</span>
                                        ) : (
                                            <span className="text-muted-foreground">all normal</span>
                                        )}
                                    </td>
                                    <td className="px-3 py-2">{formatDateTime(sample.entered_at)}</td>
                                    <td className="px-3 py-2 text-right">
                                        <Button size="sm" asChild>
                                            <Link href={`/samples/${sample.id}/verify`}>
                                                <ShieldCheck className="size-4" /> Review
                                            </Link>
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                            {samples.data.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="text-muted-foreground px-3 py-8 text-center">
                                        Nothing awaiting verification.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
