export function formatMoney(value: string | number): string {
    return `Rs ${Number(value).toLocaleString('en-PK', { maximumFractionDigits: 0 })}`;
}

export function formatDateTime(value: string | null | undefined): string {
    if (!value) return '—';
    return new Date(value).toLocaleString('en-GB', {
        day: '2-digit',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

export function patientAge(dob: string | null, ageYears: number | null): string {
    if (dob) {
        const years = Math.floor((Date.now() - new Date(dob).getTime()) / (365.25 * 24 * 3600 * 1000));
        return `${years} y`;
    }
    if (ageYears !== null && ageYears !== undefined) return `${ageYears} y`;
    return '—';
}
