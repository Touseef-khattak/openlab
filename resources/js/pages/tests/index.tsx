import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useCan } from '@/hooks/use-can';
import AppLayout from '@/layouts/app-layout';
import { formatMoney } from '@/lib/format';
import { type BreadcrumbItem, type Paginated, type SharedData } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { Layers, Pencil, Plus, Power, Search, Trash2 } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Tests', href: '/tests' }];

interface TestRow {
    id: number;
    code: string;
    name: string;
    price: string;
    specimen_type: string | null;
    is_panel: boolean;
    is_active: boolean;
    parameters_count: number;
    category: { id: number; name: string } | null;
}

export default function TestsIndex({
    tests,
    filters,
}: {
    tests: Paginated<TestRow>;
    filters: { q: string | null; active: boolean | null };
}) {
    const can = useCan();
    const manage = can('catalog.manage');
    const { flash } = usePage<SharedData>().props;
    const [query, setQuery] = useState(filters.q ?? '');

    const apply = (next: { q?: string; active?: boolean | null }) => {
        const params: Record<string, string> = {};
        const q = next.q !== undefined ? next.q : (filters.q ?? '');
        const active = next.active !== undefined ? next.active : filters.active;
        if (q) params.q = q;
        if (active !== null && active !== undefined) params.active = active ? '1' : '0';
        router.get('/tests', params, { preserveState: true, replace: true });
    };

    const search = (event: React.FormEvent) => {
        event.preventDefault();
        apply({ q: query });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Tests" />
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

                <div className="flex flex-wrap items-center justify-between gap-3">
                    <form onSubmit={search} className="relative w-full max-w-sm">
                        <Search className="text-muted-foreground absolute top-2.5 left-2.5 size-4" />
                        <Input
                            placeholder="Search test by name or code…"
                            value={query}
                            onChange={(event) => setQuery(event.target.value)}
                            className="pl-8"
                        />
                    </form>
                    <div className="flex items-center gap-2">
                        <div className="flex rounded-md border p-0.5 text-sm">
                            {[
                                { label: 'All', value: null },
                                { label: 'Active', value: true },
                                { label: 'Inactive', value: false },
                            ].map((option) => (
                                <button
                                    key={option.label}
                                    onClick={() => apply({ active: option.value })}
                                    className={`rounded px-2.5 py-1 ${
                                        filters.active === option.value ? 'bg-primary text-primary-foreground' : 'hover:bg-accent'
                                    }`}
                                >
                                    {option.label}
                                </button>
                            ))}
                        </div>
                        {manage && (
                            <Button asChild>
                                <Link href="/tests/create">
                                    <Plus className="size-4" /> Add test
                                </Link>
                            </Button>
                        )}
                    </div>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-muted/50 text-muted-foreground border-b text-left">
                                <th className="px-3 py-2 font-medium">Code</th>
                                <th className="px-3 py-2 font-medium">Name</th>
                                <th className="px-3 py-2 font-medium">Category</th>
                                <th className="px-3 py-2 font-medium">Type</th>
                                <th className="px-3 py-2 text-right font-medium">Price</th>
                                <th className="px-3 py-2 font-medium">Status</th>
                                {manage && <th className="px-3 py-2 text-right font-medium">Actions</th>}
                            </tr>
                        </thead>
                        <tbody>
                            {tests.data.map((test) => (
                                <tr key={test.id} className="hover:bg-accent/50 border-b last:border-0">
                                    <td className="px-3 py-2 font-mono text-xs">{test.code}</td>
                                    <td className="px-3 py-2 font-medium">{test.name}</td>
                                    <td className="text-muted-foreground px-3 py-2">{test.category?.name ?? '—'}</td>
                                    <td className="px-3 py-2">
                                        {test.is_panel ? (
                                            <Badge variant="secondary" className="gap-1">
                                                <Layers className="size-3" /> Panel · {test.parameters_count}
                                            </Badge>
                                        ) : (
                                            <span className="text-muted-foreground">Single</span>
                                        )}
                                    </td>
                                    <td className="px-3 py-2 text-right tabular-nums">{formatMoney(test.price)}</td>
                                    <td className="px-3 py-2">
                                        <Badge variant={test.is_active ? 'default' : 'secondary'}>
                                            {test.is_active ? 'active' : 'inactive'}
                                        </Badge>
                                    </td>
                                    {manage && (
                                        <td className="px-3 py-2">
                                            <div className="flex items-center justify-end gap-1">
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    title={test.is_active ? 'Deactivate' : 'Activate'}
                                                    onClick={() => router.patch(`/tests/${test.id}/toggle`, {}, { preserveScroll: true })}
                                                >
                                                    <Power className={`size-4 ${test.is_active ? 'text-green-600' : 'text-muted-foreground'}`} />
                                                </Button>
                                                <Button variant="ghost" size="sm" asChild>
                                                    <Link href={`/tests/${test.id}/edit`}>
                                                        <Pencil className="size-4" />
                                                    </Link>
                                                </Button>
                                                <Button
                                                    variant="ghost"
                                                    size="sm"
                                                    title="Delete"
                                                    onClick={() => {
                                                        if (confirm(`Delete “${test.name}”? This cannot be undone.`)) {
                                                            router.delete(`/tests/${test.id}`, { preserveScroll: true });
                                                        }
                                                    }}
                                                >
                                                    <Trash2 className="size-4 text-red-600" />
                                                </Button>
                                            </div>
                                        </td>
                                    )}
                                </tr>
                            ))}
                            {tests.data.length === 0 && (
                                <tr>
                                    <td colSpan={manage ? 7 : 6} className="text-muted-foreground px-3 py-8 text-center">
                                        No tests found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {tests.last_page > 1 && (
                    <div className="flex flex-wrap gap-1">
                        {tests.links.map((link, index) =>
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
