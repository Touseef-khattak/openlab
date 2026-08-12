import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatMoney } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { RangeControl } from './report-controls';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
    { title: 'Doctor commission', href: '/reports/doctor-commission' },
];

interface Row {
    doctor: string;
    specialty: string | null;
    percent: number;
    visits: number;
    net: number;
    commission: number;
}

function Stat({ label, value }: { label: string; value: string }) {
    return (
        <Card>
            <CardContent className="flex flex-col gap-1 py-4">
                <span className="text-muted-foreground text-sm">{label}</span>
                <span className="text-xl font-semibold tabular-nums">{value}</span>
            </CardContent>
        </Card>
    );
}

export default function DoctorCommission({
    from,
    to,
    rows,
    totals,
}: {
    from: string;
    to: string;
    rows: Row[];
    totals: { doctors: number; visits: number; net: number; commission: number };
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Doctor commission" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <h1 className="text-xl font-semibold">Doctor commission</h1>
                    <RangeControl path="/reports/doctor-commission" from={from} to={to} />
                </div>

                <div className="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                    <Stat label="Referring doctors" value={String(totals.doctors)} />
                    <Stat label="Referred visits" value={String(totals.visits)} />
                    <Stat label="Referred net" value={formatMoney(totals.net)} />
                    <Stat label="Total commission" value={formatMoney(totals.commission)} />
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-muted/50 text-muted-foreground border-b text-left">
                                <th className="px-3 py-2 font-medium">Doctor</th>
                                <th className="px-3 py-2 font-medium">Specialty</th>
                                <th className="px-3 py-2 text-right font-medium">Commission %</th>
                                <th className="px-3 py-2 text-right font-medium">Visits</th>
                                <th className="px-3 py-2 text-right font-medium">Referred net</th>
                                <th className="px-3 py-2 text-right font-medium">Commission</th>
                            </tr>
                        </thead>
                        <tbody>
                            {rows.map((row) => (
                                <tr key={row.doctor} className="hover:bg-accent/50 border-b last:border-0">
                                    <td className="px-3 py-2 font-medium">{row.doctor}</td>
                                    <td className="text-muted-foreground px-3 py-2">{row.specialty ?? '—'}</td>
                                    <td className="px-3 py-2 text-right tabular-nums">{row.percent}%</td>
                                    <td className="px-3 py-2 text-right tabular-nums">{row.visits}</td>
                                    <td className="px-3 py-2 text-right tabular-nums">{formatMoney(row.net)}</td>
                                    <td className="px-3 py-2 text-right font-medium tabular-nums">{formatMoney(row.commission)}</td>
                                </tr>
                            ))}
                            {rows.length === 0 && (
                                <tr>
                                    <td colSpan={6} className="text-muted-foreground px-3 py-8 text-center">
                                        No referred visits in this period.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                <p className="text-muted-foreground text-xs">
                    Commission is calculated on the referred net (post-discount) billing for visits registered in the selected period.
                </p>
            </div>
        </AppLayout>
    );
}
