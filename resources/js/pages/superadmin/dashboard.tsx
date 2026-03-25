import AppLayout from '@/layouts/app-layout';
import { Head, Link } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';
import { Shield, Users, Cpu, BookOpen, ScanLine, LayoutGrid } from 'lucide-react';

interface Metrics {
    totalRequests: number;
    openRequests: number;
    inProgressRequests: number;
    resolvedRequests: number;
    adminCount: number;
    superAdminCount: number;
    totalTokens: number;
    estimatedCost: number;
}

interface Props {
    metrics: Metrics;
}

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Superadmin Dashboard', href: '/superadmin/dashboard' },
];

export default function SuperAdminDashboard({ metrics }: Props) {
    const featureCards = [
        {
            title: 'System Dashboard',
            description: 'View and manage all ICT service requests.',
            href: '/dashboard',
            icon: LayoutGrid,
        },
        {
            title: 'SMART Scan',
            description: 'Extract and ingest service requests from files.',
            href: '/dashboard/smart-scan',
            icon: ScanLine,
        },
        {
            title: 'Documentation',
            description: 'Access technical and operational documentation.',
            href: '/dashboard/documentation',
            icon: BookOpen,
        },
        {
            title: 'AI Consumption',
            description: 'Monitor token usage and estimated AI costs.',
            href: '/dashboard/ai-consumption',
            icon: Cpu,
        },
        {
            title: 'User Management',
            description: 'Create admins and manage their permissions.',
            href: '/superadmin/users',
            icon: Users,
        },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Superadmin Dashboard" />

            <div className="space-y-6 p-6">
                <div className="rounded-2xl border bg-card p-6">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <p className="inline-flex items-center gap-2 rounded-full border px-3 py-1 text-xs font-semibold uppercase tracking-wide text-primary">
                                <Shield className="size-3.5" /> Superadmin Panel
                            </p>
                            <h1 className="mt-3 text-3xl font-bold tracking-tight">System Control Center</h1>
                            <p className="mt-2 text-sm text-muted-foreground">
                                Full visibility across operations, AI usage, and admin management.
                            </p>
                        </div>
                    </div>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard label="Total Requests" value={metrics.totalRequests.toLocaleString()} />
                    <StatCard label="Open Requests" value={metrics.openRequests.toLocaleString()} />
                    <StatCard label="Admins" value={metrics.adminCount.toLocaleString()} />
                    <StatCard label="Total AI Tokens" value={metrics.totalTokens.toLocaleString()} />
                    <StatCard label="In Progress" value={metrics.inProgressRequests.toLocaleString()} />
                    <StatCard label="Resolved" value={metrics.resolvedRequests.toLocaleString()} />
                    <StatCard label="Superadmins" value={metrics.superAdminCount.toLocaleString()} />
                    <StatCard label="Estimated AI Cost" value={`$${metrics.estimatedCost.toFixed(4)}`} />
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    {featureCards.map((card) => (
                        <Link
                            key={card.title}
                            href={card.href}
                            className="group rounded-2xl border bg-card p-5 transition hover:border-primary/50 hover:bg-primary/5"
                        >
                            <div className="flex items-start gap-4">
                                <div className="rounded-lg border p-2 text-primary">
                                    <card.icon className="size-5" />
                                </div>
                                <div>
                                    <h2 className="font-semibold">{card.title}</h2>
                                    <p className="mt-1 text-sm text-muted-foreground">{card.description}</p>
                                </div>
                            </div>
                        </Link>
                    ))}
                </div>
            </div>
        </AppLayout>
    );
}

function StatCard({ label, value }: { label: string; value: string }) {
    return (
        <div className="rounded-xl border bg-card p-5">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className="mt-2 text-3xl font-bold">{value}</p>
        </div>
    );
}
