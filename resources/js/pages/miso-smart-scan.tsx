import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type MisoAccomplishment } from '@/types';
import { Head } from '@inertiajs/react';
import {
    CheckCircle2,
    Loader2,
    AlertCircle,
    FileText,
    FileSpreadsheet,
    Zap,
    Info,
    Download,
    CloudUpload,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { useState, useCallback } from 'react';
import { cn } from '@/lib/utils';
import axios from 'axios';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

type MisoCategory = MisoAccomplishment['category'];

interface Props {
    tab: string;
    category: MisoCategory;
    categoryOptions: Record<string, string>;
}

const categoryTabMap: Record<MisoCategory, string> = {
    data_management: 'miso-data',
    network: 'miso-network',
    systems_development: 'miso-systems',
};

export default function MisoSmartScan({ tab, category, categoryOptions }: Props) {
    const [isDragging, setIsDragging] = useState(false);
    const [file, setFile] = useState<File | null>(null);
    const [status, setStatus] = useState<'idle' | 'uploading' | 'extracting' | 'completed' | 'error'>('idle');
    const [progress, setProgress] = useState(0);
    const [error, setError] = useState<string | null>(null);
    const [isSaving, setIsSaving] = useState(false);
    const [selectedCategory, setSelectedCategory] = useState<MisoCategory>(category);
    const [extractedData, setExtractedData] = useState<Partial<MisoAccomplishment>[]>([]);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'MISO Smart Scan', href: `/dashboard/miso-smart-scan?tab=${tab}` },
    ];

    const onDragOver = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(true);
    }, []);

    const onDragLeave = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
    }, []);

    const onDrop = useCallback((e: React.DragEvent) => {
        e.preventDefault();
        setIsDragging(false);
        const files = e.dataTransfer.files;

        if (files.length > 0) {
            handleFileSelect(files[0]);
        }
    }, []);

    const handleFileSelect = (selectedFile: File) => {
        const fileName = selectedFile.name.toLowerCase();
        const isSpreadsheet = fileName.endsWith('.csv') || fileName.endsWith('.xlsx') || fileName.endsWith('.xls');

        setError(null);

        if (!isSpreadsheet) {
            setError('Unsupported file type. Please upload CSV or XLSX files.');
            return;
        }

        setFile(selectedFile);
        setStatus('idle');
        setExtractedData([]);
    };

    const startScan = async () => {
        if (!file) return;

        setStatus('uploading');
        setProgress(0);
        setError(null);

        const formData = new FormData();
        formData.append('file', file);
        formData.append('category', selectedCategory);

        try {
            const response = await axios.post('/api/miso/extract', formData, {
                onUploadProgress: (progressEvent) => {
                    const total = progressEvent.total || 1;
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / total);
                    setProgress(percentCompleted * 0.3);

                    if (percentCompleted === 100) {
                        setStatus('extracting');
                    }
                },
            });

            if (response.data.success && response.data.job_id) {
                pollStatus(response.data.job_id);
                return;
            }

            throw new Error(response.data.message || 'Extraction failed to initiate');
        } catch (err: any) {
            setStatus('error');
            if (err.response?.status === 429) {
                setError('Too many requests. Please wait a minute before trying again.');
            } else {
                setError(err.response?.data?.message || err.message || 'Processing failed.');
            }
        }
    };

    const pollStatus = (jobId: string) => {
        const check = async () => {
            try {
                const response = await axios.get(`/api/miso/extract/${jobId}/status`);
                const { status: jobStatus, data: result, error: jobError } = response.data;

                if (jobStatus === 'processing' || jobStatus === 'queued') {
                    setProgress((prev) => Math.min(prev + 5, 90));
                    return;
                }

                if (jobStatus === 'completed') {
                    clearInterval(interval);
                    setProgress(100);
                    setStatus('completed');

                    const parsed = Array.isArray(result) ? result : result ? [result] : [];
                    const normalized = parsed.map((row: Partial<MisoAccomplishment>) => ({
                        ...row,
                        category: (row.category as MisoCategory) || selectedCategory,
                        overall_status: row.overall_status || 'Pending',
                    }));

                    setExtractedData(normalized);
                    return;
                }

                if (jobStatus === 'failed') {
                    clearInterval(interval);
                    setStatus('error');
                    setError(jobError || 'Extraction failed internally.');
                }
            } catch (_err) {
                clearInterval(interval);
                setStatus('error');
                setError('Connection lost while polling extraction status.');
            }
        };

        check();
        const interval = setInterval(check, 1000);
    };

    const handleSave = async () => {
        if (extractedData.length === 0) return;

        setIsSaving(true);

        try {
            const payload = {
                category: selectedCategory,
                requests: extractedData.map((row) => ({
                    category: selectedCategory,
                    record_no: row.record_no || null,
                    project_title: row.project_title || null,
                    project_lead: row.project_lead || null,
                    project_members: row.project_members || null,
                    budget_cost: row.budget_cost || null,
                    implementing_unit: row.implementing_unit || null,
                    target_activities: row.target_activities || null,
                    intended_duration: row.intended_duration || null,
                    start_date: row.start_date || null,
                    target_end_date: row.target_end_date || null,
                    reporting_period: row.reporting_period || null,
                    completion_percentage: row.completion_percentage || null,
                    overall_status: row.overall_status || 'Pending',
                    remarks: row.remarks || null,
                })),
            };

            const response = await axios.post('/api/miso/requests/batch', payload);
            if (response.data.success) {
                const redirectTab = categoryTabMap[selectedCategory] || tab;
                window.location.href = `/dashboard?tab=${redirectTab}`;
            }
        } catch (err: any) {
            setError(err?.response?.data?.message || 'Failed to save extracted records.');
        } finally {
            setIsSaving(false);
        }
    };

    const clearAll = () => {
        setFile(null);
        setStatus('idle');
        setProgress(0);
        setError(null);
        setExtractedData([]);
    };

    const handleCategoryChange = (value: string) => {
        const nextCategory = value as MisoCategory;
        setSelectedCategory(nextCategory);
        setExtractedData([]);

        if (status === 'completed') {
            setStatus('idle');
            setProgress(0);
        }
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="MISO Smart Scan" />

            <div className="flex flex-col gap-6 p-6 bg-background min-h-screen">
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-border">
                    <div className="flex items-center gap-4">
                        <div className="p-2 rounded-lg bg-primary/10 border border-primary/20">
                            <Zap className="size-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-foreground">MISO Smart Scan</h1>
                            <p className="text-sm text-muted-foreground uppercase tracking-wider font-medium opacity-70">Import and extract MISO accomplishment data</p>
                        </div>
                    </div>
                    <div className="w-full sm:w-[360px]">
                        <Select value={selectedCategory} onValueChange={handleCategoryChange}>
                            <SelectTrigger className="h-10 bg-card border-border text-muted-foreground">
                                <SelectValue placeholder="Select category" />
                            </SelectTrigger>
                            <SelectContent className="bg-popover border-border text-popover-foreground">
                                {Object.entries(categoryOptions).map(([value, label]) => (
                                    <SelectItem key={value} value={value}>{label}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <div className="lg:col-span-12 xl:col-span-4 flex flex-col gap-6">
                        <Card className="bg-card border-border rounded-lg shadow-sm">
                            <div className="px-6 py-4 border-b border-border bg-muted/30">
                                <h2 className="text-[11px] font-bold text-muted-foreground uppercase tracking-widest">Source Spreadsheet</h2>
                            </div>
                            <CardContent className="p-6 text-left">
                                <div
                                    className={cn(
                                        'relative group flex flex-col items-center justify-center min-h-[200px] border border-dashed rounded-lg transition-all duration-200 cursor-pointer',
                                        isDragging ? 'border-primary bg-primary/5' : 'border-border bg-muted/5 hover:border-primary/50 hover:bg-muted/10'
                                    )}
                                    onDragOver={onDragOver}
                                    onDragLeave={onDragLeave}
                                    onDrop={onDrop}
                                    onClick={() => document.getElementById('miso-file-input')?.click()}
                                >
                                    <input
                                        type="file"
                                        id="miso-file-input"
                                        className="hidden"
                                        onChange={(e) => e.target.files && handleFileSelect(e.target.files[0])}
                                    />

                                    <div className="mb-4">
                                        {file ? <FileSpreadsheet className="size-10 text-primary" /> : <CloudUpload className="size-10 text-muted-foreground/60" />}
                                    </div>

                                    {file ? (
                                        <div className="text-center px-4 w-full">
                                            <p className="text-sm font-bold text-foreground truncate">{file.name}</p>
                                            <p className="text-[10px] text-muted-foreground font-medium uppercase mt-1">
                                                {(file.size / 1024 / 1024).toFixed(2)} MB
                                            </p>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                className="mt-4 h-8 text-rose-500 hover:bg-rose-500/10 rounded-md font-bold text-[10px] uppercase tracking-wider"
                                                onClick={(e) => {
                                                    e.stopPropagation();
                                                    clearAll();
                                                }}
                                            >
                                                Remove File
                                            </Button>
                                        </div>
                                    ) : (
                                        <div className="text-center px-4">
                                            <p className="text-sm font-bold text-foreground">Upload Spreadsheet</p>
                                            <p className="text-[10px] text-muted-foreground mt-1 uppercase tracking-widest">CSV, XLS, XLSX</p>
                                        </div>
                                    )}
                                </div>

                                {status !== 'idle' && status !== 'error' && (
                                    <div className="mt-6 space-y-2">
                                        <div className="flex items-center justify-between text-[10px] font-bold text-muted-foreground uppercase">
                                            <span>{status === 'extracting' ? 'Extracting Rows' : 'Uploading'}</span>
                                            <span>{Math.round(progress)}%</span>
                                        </div>
                                        <div className="h-1.5 w-full bg-muted rounded-full overflow-hidden border border-border">
                                            <div className="h-full bg-primary rounded-full transition-all duration-300" style={{ width: `${progress}%` }} />
                                        </div>
                                    </div>
                                )}

                                {error && (
                                    <div className="mt-6 p-4 rounded-lg bg-rose-500/5 border border-rose-500/10 flex items-start gap-3 text-rose-600">
                                        <AlertCircle className="size-4 mt-0.5" />
                                        <div className="flex-1">
                                            <p className="text-[10px] font-bold uppercase tracking-widest">Import Error</p>
                                            <p className="text-xs font-medium mt-1 leading-normal">{error}</p>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>

                        {file && (
                            <Card className="bg-card border-border rounded-lg shadow-sm">
                                <div className="px-6 py-4 border-b border-border bg-muted/30">
                                    <h2 className="text-[11px] font-bold text-muted-foreground uppercase tracking-widest">Actions</h2>
                                </div>
                                <CardContent className="p-6 space-y-4">
                                    <div className="flex items-center justify-between text-[10px] font-bold text-muted-foreground uppercase tracking-widest">
                                        <span>Status</span>
                                        <Badge variant="outline" className="text-[9px] font-bold uppercase tracking-tighter px-2 py-0 border-border bg-muted/50">
                                            {status === 'completed' ? 'Extraction Done' : status === 'extracting' ? 'Extracting' : 'Ready'}
                                        </Badge>
                                    </div>
                                    <Button
                                        className="w-full h-10 font-bold text-xs uppercase tracking-wider rounded-md"
                                        onClick={startScan}
                                        disabled={status === 'uploading' || status === 'extracting'}
                                    >
                                        {status === 'extracting' ? (
                                            <>
                                                <Loader2 className="mr-2 size-4 animate-spin" /> Processing...
                                            </>
                                        ) : (
                                            <>
                                                <Zap className="mr-2 size-4 fill-current" /> Analyze Spreadsheet
                                            </>
                                        )}
                                    </Button>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    <div className="lg:col-span-12 xl:col-span-8 flex flex-col">
                        <Card className="bg-card border-border rounded-lg shadow-sm flex flex-col h-full overflow-hidden">
                            <div className="px-6 py-4 border-b border-border bg-muted/30 flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <FileText className="size-4 text-primary" />
                                    <h2 className="text-[11px] font-bold text-muted-foreground uppercase tracking-widest">Extraction Results</h2>
                                </div>
                            </div>
                            <CardContent className="flex-1 p-6">
                                {extractedData.length === 0 ? (
                                    <div className="h-full flex flex-col items-center justify-center text-center py-20 px-10">
                                        <div className="size-16 bg-muted/10 border border-dashed border-border rounded-lg flex items-center justify-center mb-6">
                                            <Info className="size-6 text-muted-foreground/30" />
                                        </div>
                                        <h3 className="text-base font-bold text-zinc-950 dark:text-zinc-50 mb-1">Ready for MISO Extraction</h3>
                                        <p className="text-xs text-muted-foreground leading-relaxed max-w-xs mx-auto">
                                            Upload a MISO spreadsheet and click Analyze Spreadsheet to preview rows before import.
                                        </p>
                                    </div>
                                ) : (
                                    <div className="space-y-6 animate-in fade-in duration-500">
                                        <div className="flex items-center justify-between mb-4">
                                            <Badge variant="secondary" className="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-widest bg-primary/10 text-primary border-primary/20">
                                                Batch {extractedData.length} Records
                                            </Badge>
                                        </div>

                                        <div className="border border-border rounded-lg overflow-hidden bg-card">
                                            <div className="overflow-x-auto max-h-[500px]">
                                                <table className="w-full text-left border-collapse">
                                                    <thead>
                                                        <tr className="bg-muted/50 border-b border-border">
                                                            <th className="px-4 py-3 text-[10px] font-bold text-muted-foreground uppercase tracking-widest">No.</th>
                                                            <th className="px-4 py-3 text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Project Title</th>
                                                            <th className="px-4 py-3 text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Project Lead</th>
                                                            <th className="px-4 py-3 text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Implementing Unit</th>
                                                            <th className="px-4 py-3 text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Reporting Period</th>
                                                            <th className="px-4 py-3 text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Completion</th>
                                                            <th className="px-4 py-3 text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Status</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-border">
                                                        {extractedData.map((row, idx) => (
                                                            <tr key={idx} className="hover:bg-muted/30 transition-colors">
                                                                <td className="px-4 py-3 text-xs font-mono text-muted-foreground">{row.record_no || idx + 1}</td>
                                                                <td className="px-4 py-3 text-xs font-bold text-foreground max-w-[280px] truncate" title={row.project_title || ''}>{row.project_title || '---'}</td>
                                                                <td className="px-4 py-3 text-xs text-muted-foreground">{row.project_lead || '---'}</td>
                                                                <td className="px-4 py-3 text-xs text-muted-foreground">{row.implementing_unit || '---'}</td>
                                                                <td className="px-4 py-3 text-xs text-muted-foreground">{row.reporting_period || '---'}</td>
                                                                <td className="px-4 py-3 text-xs text-muted-foreground">{row.completion_percentage || '---'}</td>
                                                                <td className="px-4 py-3 text-xs text-primary font-bold uppercase tracking-tight">{row.overall_status || 'Pending'}</td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div className="pt-6 border-t border-border flex items-center justify-between gap-4">
                                            <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-tight">Review extraction before final import</p>
                                            <div className="flex items-center gap-3">
                                                <Button variant="ghost" size="sm" className="h-9 px-4 text-xs font-bold uppercase" onClick={clearAll}>
                                                    Discard
                                                </Button>
                                                <Button className="h-9 px-6 bg-primary text-primary-foreground font-bold text-xs uppercase" disabled={isSaving} onClick={handleSave}>
                                                    {isSaving ? <Loader2 className="animate-spin size-4 mr-2" /> : <Download className="size-4 mr-2" />}
                                                    Import All
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
