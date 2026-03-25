import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import React from 'react';
import { cn } from '@/lib/utils';
import {
    BarChart,
    Bar,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    ResponsiveContainer,
    Cell,
    PieChart,
    Pie,
    AreaChart,
    Area,
} from 'recharts';
import { 
    BarChart3, 
    TrendingUp, 
    CheckCircle2, 
    Clock, 
    Building,
    Settings,
    Users,
    Download,
    FileSpreadsheet,
    FileText,
    Zap
} from 'lucide-react';
import { Button } from '@/components/ui/button';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Reports & Analytics', href: '/dashboard/reports' },
];

interface StatData {
    status: string;
    total: number;
}

interface TypeData {
    request_type: string;
    total: number;
}

interface OfficeData {
    office_unit: string;
    total: number;
}

interface MonthlyData {
    month: string;
    total: number;
}

interface PersonnelData {
    conducted_by: string;
    total: number;
}

interface ReportsProps {
    stats: {
        status: StatData[];
        type: TypeData[];
        offices: OfficeData[];
        monthly: MonthlyData[];
        personnel: PersonnelData[];
        total: number;
        resolved: number;
        pending: number;
        in_progress: number;
        avg_resolution_hours: number;
    };
}

const COLORS = ['#2dd4bf', '#fb923c', '#94a3b8', '#f87171', '#a78bfa', '#fcd34d', '#3b82f6'];

export default function Reports({ stats }: ReportsProps) {
    if (!stats) return <div className="p-20 text-center font-bold text-foreground">No stats data found.</div>;

    // Data Prep
    const statusData = (stats.status || []).map(s => ({
        name: String(s.status || 'Unknown'),
        value: Number(s.total || 0)
    }));

    const officeData = (stats.offices || []).map(o => ({
        name: (String(o.office_unit || 'N/A')).substring(0, 15) + (String(o.office_unit || '').length > 15 ? '...' : ''),
        total: Number(o.total || 0)
    }));

    const monthlyData = (stats.monthly || []).map(m => ({
        name: String(m.month || ''),
        total: Number(m.total || 0)
    }));

    const typeData = (stats.type || []).map(t => ({
        name: String(t.request_type || 'Other'),
        total: Number(t.total || 0)
    }));

    const personnelData = (stats.personnel || []).map(p => ({
        name: String(p.conducted_by || 'Unknown'),
        total: Number(p.total || 0)
    }));

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Reports & Analytics" />
            
            <div className="flex flex-col gap-8 p-6 lg:p-10 bg-background min-h-full">
                {/* Header with Export Hub */}
                <div className="flex flex-col md:flex-row justify-between items-start md:items-center gap-6">
                    <div className="flex flex-col gap-1.5">
                        <div className="flex items-center gap-3">
                            <div className="p-2.5 bg-primary/10 rounded-lg">
                                <BarChart3 className="size-6 text-primary" />
                            </div>
                            <h1 className="text-3xl font-black text-foreground tracking-tight">Reports Hub</h1>
                        </div>
                        <p className="text-muted-foreground font-medium">Real-time performance metrics and operations analysis.</p>
                    </div>

                    <div className="flex flex-wrap gap-3 bg-card border border-border p-2 rounded-xl shadow-sm">
                        <Button variant="ghost" size="sm" asChild className="gap-2 font-bold text-xs uppercase tracking-wider">
                            <a href={route('ict.export-csv')} download>
                                <FileText className="size-4 text-zinc-500" />
                                CSV
                            </a>
                        </Button>
                        <Button variant="ghost" size="sm" asChild className="gap-2 font-bold text-xs uppercase tracking-wider">
                            <a href={route('ict.export-xlsx')} download>
                                <FileSpreadsheet className="size-4 text-emerald-600" />
                                Excel
                            </a>
                        </Button>
                        <Button variant="outline" size="sm" asChild className="gap-2 font-bold text-xs uppercase tracking-wider border-primary/30 bg-primary/5 hover:bg-primary/10 text-primary">
                            <a href={route('ict.export-bulk-docx')} download>
                                <Download className="size-4" />
                                Templated Forms (ZIP)
                            </a>
                        </Button>
                    </div>
                </div>

                {/* Performance Snapshot */}
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-6">
                    <ReportStatCard label="Total Requests" value={stats.total} icon={TrendingUp} color="bg-zinc-500" iconColor="text-zinc-500" />
                    <ReportStatCard label="Efficiency" value={stats.avg_resolution_hours + 'h'} labelSuffix="Avg. Turnaround" icon={Zap} color="bg-yellow-500" iconColor="text-yellow-500" />
                    <ReportStatCard label="Resolved" value={stats.resolved} icon={CheckCircle2} color="bg-emerald-500" iconColor="text-emerald-500" />
                    <ReportStatCard label="In Queue" value={stats.pending} icon={Clock} color="bg-orange-500" iconColor="text-orange-500" />
                    <ReportStatCard label="Active" value={stats.in_progress} icon={Settings} color="bg-blue-500" iconColor="text-blue-500" />
                </div>

                {/* Analytics Grid */}
                <div className="grid grid-cols-1 lg:grid-cols-12 gap-8 items-stretch">
                    
                    {/* Monthly Volume */}
                    <ChartContainer title="Operational Volume Trend" className="lg:col-span-8 h-[400px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <AreaChart data={monthlyData}>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="currentColor" opacity={0.1} />
                                <XAxis dataKey="name" tick={{fill: 'currentColor', fontSize: 11, opacity: 0.5}} axisLine={false} tickLine={false} dy={10} />
                                <YAxis tick={{fill: 'currentColor', fontSize: 11, opacity: 0.5}} axisLine={false} tickLine={false} />
                                <Tooltip contentStyle={{backgroundColor: 'hsl(var(--card))', border: '1px solid hsl(var(--border))', borderRadius: '8px'}} />
                                <Area type="monotone" dataKey="total" stroke="#2dd4bf" strokeWidth={3} fill="#2dd4bf" fillOpacity={0.1} />
                            </AreaChart>
                        </ResponsiveContainer>
                    </ChartContainer>

                    {/* Status Distribution */}
                    <ChartContainer title="Request Status" className="lg:col-span-4 h-[100%]">
                        <ResponsiveContainer width="100%" height={300}>
                            <PieChart>
                                <Pie data={statusData} innerRadius={70} outerRadius={90} paddingAngle={5} dataKey="value">
                                    {statusData.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                                </Pie>
                                <Tooltip contentStyle={{backgroundColor: 'hsl(var(--card))', border: '1px solid hsl(var(--border))', borderRadius: '8px'}} />
                            </PieChart>
                        </ResponsiveContainer>
                        <div className="mt-4 grid grid-cols-2 gap-2">
                            {statusData.map((d, i) => (
                                <div key={i} className="flex items-center gap-2">
                                    <div className="size-2.5 rounded-full" style={{backgroundColor: COLORS[i % COLORS.length]}} />
                                    <span className="text-[10px] font-bold text-muted-foreground uppercase">{d.name}</span>
                                </div>
                            ))}
                        </div>
                    </ChartContainer>

                    {/* Personnel Workload */}
                    <ChartContainer title="Top Personnel Performance" icon={Users} className="lg:col-span-12 h-[350px]">
                        <ResponsiveContainer width="100%" height="100%">
                            <BarChart data={personnelData}>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="currentColor" opacity={0.1} />
                                <XAxis dataKey="name" tick={{fill: 'currentColor', fontSize: 11, opacity: 0.5}} axisLine={false} tickLine={false} dy={10} />
                                <YAxis tick={{fill: 'currentColor', fontSize: 11, opacity: 0.5}} axisLine={false} tickLine={false} />
                                <Tooltip contentStyle={{backgroundColor: 'hsl(var(--card))', border: '1px solid hsl(var(--border))', borderRadius: '8px'}} />
                                <Bar dataKey="total" fill="hsl(var(--primary))" radius={[6, 6, 0, 0]} barSize={40} />
                            </BarChart>
                        </ResponsiveContainer>
                    </ChartContainer>

                    {/* Detailed Breakdowns */}
                    <ChartContainer title="Requests by Category" className="lg:col-span-6 min-h-[400px]">
                        <ResponsiveContainer width="100%" height={350}>
                            <BarChart data={typeData} layout="vertical">
                                <CartesianGrid strokeDasharray="3 3" horizontal={false} stroke="currentColor" opacity={0.1} />
                                <XAxis type="number" hide />
                                <YAxis dataKey="name" type="category" tick={{fill: 'currentColor', fontSize: 11, opacity: 0.5}} axisLine={false} tickLine={false} width={120} />
                                <Tooltip contentStyle={{backgroundColor: 'hsl(var(--card))', border: '1px solid hsl(var(--border))'}} />
                                <Bar dataKey="total" radius={[0, 4, 4, 0]}>
                                    {typeData.map((_, i) => <Cell key={i} fill={COLORS[i % COLORS.length]} />)}
                                </Bar>
                            </BarChart>
                        </ResponsiveContainer>
                    </ChartContainer>

                    <ChartContainer title="Top Office Units" icon={Building} className="lg:col-span-6 min-h-[400px]">
                        <ResponsiveContainer width="100%" height={350}>
                            <BarChart data={officeData}>
                                <CartesianGrid strokeDasharray="3 3" vertical={false} stroke="currentColor" opacity={0.1} />
                                <XAxis dataKey="name" tick={{fill: 'currentColor', fontSize: 10, opacity: 0.5}} axisLine={false} tickLine={false} dy={10} />
                                <YAxis tick={{fill: 'currentColor', fontSize: 11, opacity: 0.5}} axisLine={false} tickLine={false} />
                                <Tooltip contentStyle={{backgroundColor: 'hsl(var(--card))', border: '1px solid hsl(var(--border))'}} />
                                <Bar dataKey="total" fill="#3b82f6" radius={[4, 4, 0, 0]} />
                            </BarChart>
                        </ResponsiveContainer>
                    </ChartContainer>
                </div>
            </div>
        </AppLayout>
    );
}

function ReportStatCard({ label, value, labelSuffix, icon: Icon, color, iconColor }: any) {
    return (
        <div className="bg-card border border-border rounded-2xl p-6 relative overflow-hidden group hover:border-primary/50 transition-all duration-300 shadow-sm flex flex-col justify-between min-h-[140px]">
            <div className="relative z-10">
                <div className="flex justify-between items-start">
                    <div className="flex flex-col">
                        <p className="text-[10px] font-black text-muted-foreground uppercase tracking-[0.2em]">{label}</p>
                        {labelSuffix && <span className="text-[9px] font-bold text-zinc-400 mt-0.5">{labelSuffix}</span>}
                    </div>
                    <div className={cn("p-2 rounded-lg bg-secondary/80 dark:bg-black/40 shadow-inner", iconColor)}>
                        <Icon className="size-5" />
                    </div>
                </div>
                <div className="mt-4 flex items-baseline gap-2">
                    <h3 className="text-4xl font-black text-foreground tracking-tighter">{value}</h3>
                </div>
            </div>
            <div className={cn("absolute -right-4 -bottom-4 size-24 blur-3xl opacity-10 dark:opacity-20 transition-opacity group-hover:opacity-30", color)} />
        </div>
    );
}

function ChartContainer({ children, title, className, icon: Icon }: any) {
    return (
        <div className={cn("bg-card border border-border rounded-2xl p-6 flex flex-col shadow-sm", className)}>
            <div className="flex items-center gap-2 mb-8 border-b border-border/50 pb-4">
                {Icon && <Icon className="size-4 text-primary" />}
                <h3 className="text-[10px] font-black text-muted-foreground uppercase tracking-[0.3em]">{title}</h3>
            </div>
            <div className="flex-1 w-full min-h-0">
                {children}
            </div>
        </div>
    );
}
