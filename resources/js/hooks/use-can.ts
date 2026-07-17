import { type SharedData } from '@/types';
import { usePage } from '@inertiajs/react';

export function useCan() {
    const { auth } = usePage<SharedData>().props;

    return (permission: string): boolean => auth.is_admin || auth.permissions.includes(permission);
}
