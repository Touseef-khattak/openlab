import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/react';
import { Download } from 'lucide-react';
import { useState } from 'react';

/** Single-date picker (for the daily report). */
export function DateControl({ path, date }: { path: string; date: string }) {
    const [value, setValue] = useState(date);

    return (
        <div className="flex items-end gap-2">
            <div>
                <Label htmlFor="date">Date</Label>
                <Input
                    id="date"
                    type="date"
                    value={value}
                    onChange={(e) => setValue(e.target.value)}
                    className="w-44"
                />
            </div>
            <Button variant="outline" onClick={() => router.get(path, { date: value }, { preserveState: true })}>
                Show
            </Button>
            <Button variant="outline" asChild>
                <a href={`${path}?date=${value}&format=csv`}>
                    <Download className="size-4" /> CSV
                </a>
            </Button>
        </div>
    );
}

/** From/to range picker (for period reports). */
export function RangeControl({ path, from, to }: { path: string; from: string; to: string }) {
    const [f, setF] = useState(from);
    const [t, setT] = useState(to);

    return (
        <div className="flex flex-wrap items-end gap-2">
            <div>
                <Label htmlFor="from">From</Label>
                <Input id="from" type="date" value={f} onChange={(e) => setF(e.target.value)} className="w-44" />
            </div>
            <div>
                <Label htmlFor="to">To</Label>
                <Input id="to" type="date" value={t} onChange={(e) => setT(e.target.value)} className="w-44" />
            </div>
            <Button variant="outline" onClick={() => router.get(path, { from: f, to: t }, { preserveState: true })}>
                Show
            </Button>
            <Button variant="outline" asChild>
                <a href={`${path}?from=${f}&to=${t}&format=csv`}>
                    <Download className="size-4" /> CSV
                </a>
            </Button>
        </div>
    );
}
