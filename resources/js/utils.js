// Prices are stored as integer cents; format them for display.
export const money = (cents) =>
    'Rs ' +
    (Number(cents || 0) / 100).toLocaleString('en-PK', {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

export const statusClass = (status) =>
    ({
        approved: 'bg-emerald-100 text-emerald-700',
        paid: 'bg-emerald-100 text-emerald-700',
        shipped: 'bg-sky-100 text-sky-700',
        pending: 'bg-amber-100 text-amber-700',
        suspended: 'bg-red-100 text-red-700',
    })[status] || 'bg-slate-100 text-slate-600';
