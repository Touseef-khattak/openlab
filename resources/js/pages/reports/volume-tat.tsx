import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatMoney } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { RangeControl } from './report-controls';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
    { title: 'Volume & turnaround', href: '/reports/volume-tat' },
];

interface Tat {
    samples: number;
    registration_to_report_hours: number | null;
    collection_to_report_hours: number | null;
    entry_to_verification_hours: number | null;
}

function hours(value: number | null): string {
    if (value === null) return '—';
    if (value < 1) return `${Math.round(value * 60)} min`;
    return `${value.toFixed(1)} h`;
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

export default function VolumeTat({
    from,
    to,
    volume,
    tat,
    backlog,
}: {
    from: string;
    to: string;
    volume: { code: string; name: string; orders: number; revenue: number }[];
    tat: Tat;
    backlog: { status: string; total: number; oldest_hours: number | null }[];
}) {
    const maxOrders = Math.max(...volume.map((v) => v.orders), 1);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Volume & turnaround" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <h1 className="text-xl font-semibold">Test volume & turnaround</h1>
                    <RangeControl path="/reports/volume-tat" from={from} to={to} />
                </div>

                <div>
                    <h2 className="text-muted-foreground mb-2 text-sm font-medium">
                        Turnaround (over {tat.samples} reported sample{tat.samples === 1 ? '' : 's'})
                    </h2>
                    <div className="grid gap-3 sm:grid-cols-3">
                        <Stat label="Registration → report" value={hours(tat.registration_to_report_hours)} />
                        <Stat label="Collection → report" value={hours(tat.collection_to_report_hours)} />
                        <Stat label="Result entry → verification" value={hours(tat.entry_to_verification_hours)} />
                    </div>
                </div>

                <div className="grid gap-4 lg:grid-cols-3">
                    <Card className="lg:col-span-2">
                        <CardHeader>
                            <CardTitle className="text-base">Test volume</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <table className="w-full text-sm">
                                <thead>
                                    <tr className="text-muted-foreground border-b text-left">
                                        <th className="py-1.5 font-medium">Test</th>
                                        <th className="py-1.5 text-right font-medium">Orders</th>
                                        <th className="w-40 py-1.5 font-medium"></th>
                                        <th className="py-1.5 text-right font-medium">Revenue</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {volume.map((row) => (
                                        <tr key={row.code} className="border-b last:border-0">
                                            <td className="py-1.5">
                                                <span className="text-muted-foreground mr-2 text-xs">{row.code}</span>
                                                {row.name}
                                            </td>
                                            <td className="py-1.5 text-right tabular-nums">{row.orders}</td>
                                            <td className="py-1.5">
                                                <div className="bg-muted h-2 w-full overflow-hidden rounded">
                                                    <div
                                                        className="h-full rounded bg-blue-500 dark:bg-blue-400"
                                                        style={{ width: `${(row.orders / maxOrders) * 100}%` }}
                                                    />
                                                </div>
                                            </td>
                                            <td className="py-1.5 text-right tabular-nums">{formatMoney(row.revenue)}</td>
                                        </tr>
                                    ))}
                                    {volume.length === 0 && (
                                        <tr>
                                            <td colSpan={4} className="text-muted-foreground py-6 text-center">
                                                No tests ordered in this period.
                                            </td>
                                        </tr>
                                    )}
                                </tbody>
                            </table>
                        </CardContent>
                    </Card>

                    <Card>
                        <CardHeader>
                            <CardTitle className="text-base">Pending backlog (now)</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex flex-col gap-2">
                                {backlog.map((row) => (
                                    <div key={row.status} className="flex items-center justify-between text-sm">
                                        <Badge variant="secondary">{row.status.replace('_', ' ')}</Badge>
                                        <span className="tabular-nums">
                                            {row.total}
                                            {row.oldest_hours !== null && (
                                                <span className="text-muted-foreground ml-2 text-xs">
                                                    oldest {hours(row.oldest_hours)}
                                                </span>
                                            )}
                                        </span>
                                    </div>
                                ))}
                                {backlog.length === 0 && (
                                    <p className="text-muted-foreground text-center text-sm">Nothing pending — all clear.</p>
                                )}
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </AppLayout>
    );
}
