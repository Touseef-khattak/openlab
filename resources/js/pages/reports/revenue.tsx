import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { formatMoney } from '@/lib/format';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { RangeControl } from './report-controls';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Reports', href: '/reports' },
    { title: 'Revenue', href: '/reports/revenue' },
];

interface DailyPoint {
    date: string;
    visits: number;
    gross: number;
    discount: number;
    net: number;
    collected: number;
}

/** Single-series bar chart of daily net revenue. Title names the series, so no legend. */
function RevenueChart({ data }: { data: DailyPoint[] }) {
    if (data.length === 0) {
        return <p className="text-muted-foreground py-8 text-center text-sm">No revenue in this period.</p>;
    }

    const width = 900;
    const height = 240;
    const pad = { top: 12, right: 8, bottom: 28, left: 56 };
    const plotW = width - pad.left - pad.right;
    const plotH = height - pad.top - pad.bottom;

    const max = Math.max(...data.map((d) => d.net), 1);
    const niceMax = Math.ceil(max / 4) * 4;
    const barGap = 2;
    const step = plotW / data.length;
    const barW = Math.max(1, step - barGap);

    const y = (value: number) => pad.top + plotH - (value / niceMax) * plotH;
    const ticks = [0, 0.25, 0.5, 0.75, 1].map((t) => niceMax * t);
    // Label at most ~12 dates to avoid collision.
    const labelEvery = Math.ceil(data.length / 12);

    return (
        <div className="overflow-x-auto">
            <svg viewBox={`0 0 ${width} ${height}`} className="min-w-[640px] text-muted-foreground" role="img" aria-label="Daily net revenue">
                {ticks.map((t, i) => (
                    <g key={i}>
                        <line x1={pad.left} x2={width - pad.right} y1={y(t)} y2={y(t)} className="stroke-border" strokeWidth={1} />
                        <text x={pad.left - 8} y={y(t) + 4} textAnchor="end" className="fill-muted-foreground text-[10px]">
                            {Math.round(t).toLocaleString()}
                        </text>
                    </g>
                ))}
                {data.map((d, i) => {
                    const bx = pad.left + i * step + barGap / 2;
                    const barH = pad.top + plotH - y(d.net);
                    return (
                        <g key={d.date}>
                            <rect
                                x={bx}
                                y={y(d.net)}
                                width={barW}
                                height={Math.max(0, barH)}
                                rx={3}
                                className="fill-blue-500 dark:fill-blue-400"
                            >
                                <title>
                                    {d.date}: {formatMoney(d.net)} net · {d.visits} visits
                                </title>
                            </rect>
                            {i % labelEvery === 0 && (
                                <text
                                    x={bx + barW / 2}
                                    y={height - 8}
                                    textAnchor="middle"
                                    className="fill-muted-foreground text-[9px]"
                                >
                                    {d.date.slice(5)}
                                </text>
                            )}
                        </g>
                    );
                })}
            </svg>
        </div>
    );
}

function Stat({ label, value, muted }: { label: string; value: string; muted?: boolean }) {
    return (
        <Card>
            <CardContent className="flex flex-col gap-1 py-4">
                <span className="text-muted-foreground text-sm">{label}</span>
                <span className={`text-xl font-semibold tabular-nums ${muted ? 'text-muted-foreground' : ''}`}>{value}</span>
            </CardContent>
        </Card>
    );
}

export default function Revenue({
    from,
    to,
    daily,
    byCategory,
    totals,
}: {
    from: string;
    to: string;
    daily: DailyPoint[];
    byCategory: { category: string; tests: number; revenue: number }[];
    totals: { visits: number; gross: number; discount: number; net: number; collected: number; outstanding: number };
}) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Revenue" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-end justify-between gap-4">
                    <h1 className="text-xl font-semibold">Revenue</h1>
                    <RangeControl path="/reports/revenue" from={from} to={to} />
                </div>

                <div className="grid gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    <Stat label="Visits" value={String(totals.visits)} />
                    <Stat label="Gross" value={formatMoney(totals.gross)} />
                    <Stat label="Discount" value={formatMoney(totals.discount)} muted />
                    <Stat label="Net" value={formatMoney(totals.net)} />
                    <Stat label="Collected" value={formatMoney(totals.collected)} />
                    <Stat label="Outstanding" value={formatMoney(totals.outstanding)} muted />
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Daily net revenue</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <RevenueChart data={daily} />
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-base">Revenue by category</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <table className="w-full text-sm">
                            <thead>
                                <tr className="text-muted-foreground border-b text-left">
                                    <th className="py-1.5 font-medium">Category</th>
                                    <th className="py-1.5 text-right font-medium">Tests</th>
                                    <th className="py-1.5 text-right font-medium">Revenue (gross)</th>
                                </tr>
                            </thead>
                            <tbody>
                                {byCategory.map((row) => (
                                    <tr key={row.category} className="border-b last:border-0">
                                        <td className="py-1.5">{row.category}</td>
                                        <td className="py-1.5 text-right tabular-nums">{row.tests}</td>
                                        <td className="py-1.5 text-right tabular-nums">{formatMoney(row.revenue)}</td>
                                    </tr>
                                ))}
                                {byCategory.length === 0 && (
                                    <tr>
                                        <td colSpan={3} className="text-muted-foreground py-6 text-center">
                                            No tests ordered in this period.
                                        </td>
                                    </tr>
                                )}
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
