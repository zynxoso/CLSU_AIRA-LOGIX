import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { type BreadcrumbItem } from '@/types';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'AI Consumption', href: '/dashboard/ai-consumption' },
];

interface Summary {
    totalTokens: number;
    estimatedCost: number;
    visionRequests: number;
    textParserRequests: number;
}

interface LogRow {
    id: number;
    timestamp: string | null;
    service: string;
    model: string;
    user: string;
    tokens: number;
    cost: number;
}

interface Props {
    summary: Summary;
    logs: LogRow[];
}

export default function AiConsumption({ summary, logs }: Props) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="AI Consumption" />
            <div className="p-6 space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">AI Consumption Dashboard</h1>
                        <p className="text-sm text-muted-foreground">
                            Monitor Gemini API usage and infrastructure costs
                        </p>
                    </div>
                    <span className="rounded-full bg-emerald-500/15 px-3 py-1 text-sm text-emerald-400">
                        Service Active
                    </span>
                </div>

                <div className="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                    <StatCard label="Total Tokens" value={summary.totalTokens.toLocaleString()} />
                    <StatCard label="Estimated Cost" value={`$${summary.estimatedCost.toFixed(4)}`} accent="text-emerald-400" />
                    <StatCard label="Vision OCR Requests" value={summary.visionRequests.toLocaleString()} accent="text-blue-400" />
                    <StatCard label="Text Parser Requests" value={summary.textParserRequests.toLocaleString()} accent="text-violet-400" />
                </div>

                <div className="rounded-xl border bg-card overflow-hidden">
                    <div className="border-b px-6 py-4">
                        <h2 className="text-lg font-semibold">Recent API Transactions</h2>
                    </div>

                    <table className="w-full text-sm">
                        <thead className="bg-muted/40 text-muted-foreground">
                            <tr>
                                <th className="px-6 py-3 text-left">Timestamp</th>
                                <th className="px-6 py-3 text-left">Service</th>
                                <th className="px-6 py-3 text-left">Model</th>
                                <th className="px-6 py-3 text-left">User</th>
                                <th className="px-6 py-3 text-right">Tokens</th>
                                <th className="px-6 py-3 text-right">Cost</th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.length > 0 ? (
                                logs.map((log) => (
                                    <tr key={log.id} className="border-t">
                                        <td className="px-6 py-3">{log.timestamp ?? '-'}</td>
                                        <td className="px-6 py-3">{log.service}</td>
                                        <td className="px-6 py-3">{log.model}</td>
                                        <td className="px-6 py-3">{log.user}</td>
                                        <td className="px-6 py-3 text-right">{log.tokens.toLocaleString()}</td>
                                        <td className="px-6 py-3 text-right">${log.cost.toFixed(6)}</td>
                                    </tr>
                                ))
                            ) : (
                                <tr>
                                    <td className="px-6 py-10 text-center text-muted-foreground" colSpan={6}>
                                        No usage logs found.
                                    </td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}

function StatCard({
    label,
    value,
    accent,
}: {
    label: string;
    value: string;
    accent?: string;
}) {
    return (
        <div className="rounded-xl border bg-card p-5">
            <p className="text-sm text-muted-foreground">{label}</p>
            <p className={`mt-2 text-3xl font-bold ${accent ?? ''}`}>{value}</p>
        </div>
    );
}