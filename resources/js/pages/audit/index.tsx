import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import AppLayout from '@/layouts/app-layout';
import { formatDateTime } from '@/lib/format';
import { type BreadcrumbItem, type Paginated } from '@/types';
import { Head, Link, router } from '@inertiajs/react';
import { Search } from 'lucide-react';
import { useState } from 'react';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Audit log', href: '/audit-log' }];

interface Change {
    field: string;
    old: string | null;
    new: string | null;
}

interface ActivityRow {
    id: number;
    log_name: string | null;
    event: string | null;
    description: string;
    subject_type: string | null;
    subject_id: number | null;
    causer: string | null;
    created_at: string | null;
    changes: Change[];
}

const eventVariant: Record<string, 'default' | 'secondary' | 'destructive'> = {
    created: 'default',
    updated: 'secondary',
    deleted: 'destructive',
};

export default function AuditIndex({
    activities,
    filters,
    logNames,
}: {
    activities: Paginated<ActivityRow>;
    filters: { q: string | null; log: string | null; event: string | null };
    logNames: string[];
}) {
    const [query, setQuery] = useState(filters.q ?? '');

    const apply = (next: Record<string, string | null>) => {
        const params: Record<string, string> = {};
        const merged = { q: filters.q, log: filters.log, event: filters.event, ...next };
        for (const [key, value] of Object.entries(merged)) {
            if (value) params[key] = value;
        }
        router.get('/audit-log', params, { preserveState: true, replace: true });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Audit log" />
            <div className="flex flex-col gap-4 p-4">
                <div className="flex flex-wrap items-center gap-2">
                    <form
                        onSubmit={(e) => {
                            e.preventDefault();
                            apply({ q: query });
                        }}
                        className="relative w-full max-w-xs"
                    >
                        <Search className="text-muted-foreground absolute top-2.5 left-2.5 size-4" />
                        <Input
                            placeholder="Search description…"
                            value={query}
                            onChange={(e) => setQuery(e.target.value)}
                            className="pl-8"
                        />
                    </form>

                    <Select value={filters.log ?? 'all'} onValueChange={(v) => apply({ log: v === 'all' ? null : v })}>
                        <SelectTrigger className="w-40">
                            <SelectValue placeholder="All models" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All models</SelectItem>
                            {logNames.map((name) => (
                                <SelectItem key={name} value={name}>
                                    {name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>

                    <Select value={filters.event ?? 'all'} onValueChange={(v) => apply({ event: v === 'all' ? null : v })}>
                        <SelectTrigger className="w-36">
                            <SelectValue placeholder="All actions" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All actions</SelectItem>
                            <SelectItem value="created">Created</SelectItem>
                            <SelectItem value="updated">Updated</SelectItem>
                            <SelectItem value="deleted">Deleted</SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div className="overflow-x-auto rounded-xl border">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="bg-muted/50 text-muted-foreground border-b text-left">
                                <th className="px-3 py-2 font-medium">When</th>
                                <th className="px-3 py-2 font-medium">User</th>
                                <th className="px-3 py-2 font-medium">Action</th>
                                <th className="px-3 py-2 font-medium">Record</th>
                                <th className="px-3 py-2 font-medium">Changes</th>
                            </tr>
                        </thead>
                        <tbody>
                            {activities.data.map((activity) => (
                                <tr key={activity.id} className="hover:bg-accent/50 border-b align-top last:border-0">
                                    <td className="text-muted-foreground px-3 py-2 whitespace-nowrap">
                                        {formatDateTime(activity.created_at)}
                                    </td>
                                    <td className="px-3 py-2 whitespace-nowrap">{activity.causer ?? 'system'}</td>
                                    <td className="px-3 py-2">
                                        <Badge variant={eventVariant[activity.event ?? ''] ?? 'secondary'}>
                                            {activity.event ?? activity.description}
                                        </Badge>
                                    </td>
                                    <td className="px-3 py-2 whitespace-nowrap">
                                        {activity.subject_type ?? activity.log_name}
                                        {activity.subject_id !== null && (
                                            <span className="text-muted-foreground ml-1 text-xs">#{activity.subject_id}</span>
                                        )}
                                    </td>
                                    <td className="px-3 py-2">
                                        {activity.changes.length === 0 ? (
                                            <span className="text-muted-foreground">—</span>
                                        ) : (
                                            <div className="flex flex-col gap-0.5">
                                                {activity.changes.slice(0, 6).map((change) => (
                                                    <div key={change.field} className="text-xs">
                                                        <span className="font-medium">{change.field}:</span>{' '}
                                                        {activity.event === 'updated' ? (
                                                            <>
                                                                <span className="text-muted-foreground line-through">
                                                                    {change.old ?? '∅'}
                                                                </span>{' '}
                                                                → <span>{change.new ?? '∅'}</span>
                                                            </>
                                                        ) : (
                                                            <span>{change.new ?? '∅'}</span>
                                                        )}
                                                    </div>
                                                ))}
                                                {activity.changes.length > 6 && (
                                                    <span className="text-muted-foreground text-xs">
                                                        +{activity.changes.length - 6} more
                                                    </span>
                                                )}
                                            </div>
                                        )}
                                    </td>
                                </tr>
                            ))}
                            {activities.data.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="text-muted-foreground px-3 py-8 text-center">
                                        No activity recorded.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>

                {activities.last_page > 1 && (
                    <div className="flex flex-wrap gap-1">
                        {activities.links.map((link, index) =>
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
