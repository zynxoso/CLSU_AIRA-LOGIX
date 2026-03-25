import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type IctServiceRequest } from '@/types';
import { Head } from '@inertiajs/react';
import {
    CheckCircle2,
    Loader2,
    AlertCircle,
    FileText,
    Image as ImageIcon,
    FileSpreadsheet,
    X,
    Zap,
    Info,
    Download,
    CloudUpload,
    ExternalLink
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Badge } from '@/components/ui/badge';
import { useState, useCallback } from 'react';
import { cn } from '@/lib/utils';
import axios from 'axios';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'CLSU-ICT',
        href: '/dashboard',
    },
    {
        title: 'Smart Scan',
        href: '/dashboard/smart-scan',
    },
];

export default function SmartScan() {
    const [isDragging, setIsDragging] = useState(false);
    const [file, setFile] = useState<File | null>(null);
    const [status, setStatus] = useState<'idle' | 'uploading' | 'extracting' | 'completed' | 'error'>('idle');
    const [progress, setProgress] = useState(0);
    const [error, setError] = useState<string | null>(null);
    const [extractedData, setExtractedData] = useState<Partial<IctServiceRequest> | Partial<IctServiceRequest>[] | null>(null);
    const [isSaving, setIsSaving] = useState(false);

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
        const validTypes = [
            'image/jpeg', 'image/png', 'image/webp',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/msword',
            'text/csv',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'application/vnd.ms-excel',
            'application/pdf'
        ];

        setError(null);
        if (!validTypes.includes(selectedFile.type) &&
            !selectedFile.name.endsWith('.docx') &&
            !selectedFile.name.endsWith('.xlsx')) {
            setError('Unsupported file type. Please upload images, Word docs, PDFs or spreadsheets.');
            return;
        }

        setFile(selectedFile);
        setStatus('idle');
        setExtractedData(null);
    };

    const startScan = async () => {
        if (!file) return;

        setStatus('uploading');
        setProgress(0);
        setError(null);

        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await axios.post('/api/extract', formData, {
                onUploadProgress: (progressEvent) => {
                    const total = progressEvent.total || 1;
                    const percentCompleted = Math.round((progressEvent.loaded * 100) / total);
                    setProgress(percentCompleted * 0.3); // 30% for upload
                    if (percentCompleted === 100) setStatus('extracting');
                }
            });

            if (response.data.success && response.data.job_id) {
                const jobId = response.data.job_id;
                pollStatus(jobId);
            } else {
                throw new Error(response.data.message || 'Extraction failed to initiate');
            }
        } catch (err: unknown) {
            console.error(err);
            setStatus('error');
            setError(err instanceof Error ? err.message : 'Processing failed.');
        }
    };

    const pollStatus = (jobId: string) => {
        const interval = setInterval(async () => {
            try {
                const response = await axios.get(`/api/extract/${jobId}/status`);
                const { status: jobStatus, data: result, error: jobError } = response.data;

                if (jobStatus === 'processing') {
                    setProgress(prev => Math.min(prev + 5, 90));
                } else if (jobStatus === 'completed') {
                    clearInterval(interval);
                    setProgress(100);
                    setStatus('completed');

                    if (Array.isArray(result)) {
                        setExtractedData(result.map(row => ({
                            ...row,
                            request_description: row.request_description || 'No description provided.',
                            date_of_request: row.date_of_request || new Date().toISOString()
                        })));
                    } else {
                        setExtractedData({
                            ...result,
                            request_description: result.request_description || 'No description provided.',
                            date_of_request: result.date_of_request || new Date().toISOString()
                        });
                    }
                } else if (jobStatus === 'failed') {
                    clearInterval(interval);
                    setStatus('error');
                    setError(jobError || 'AI analysis failed internally.');
                }
            } catch (err) {
                clearInterval(interval);
                setStatus('error');
                setError('Connection lost while polling extraction status.');
            }
        }, 2000);
    };

    const handleSave = async () => {
        if (!extractedData) return;
        setIsSaving(true);
        setStatus('uploading');

        try {
            const isMultiple = Array.isArray(extractedData);
            const payload = isMultiple ? { requests: extractedData } : extractedData;
            // Use correct API endpoint: /api/ict-requests for single, /api/ict-requests/batch for multiple
            // Wait, let's check the current endpoint used in the project
            const endpoint = isMultiple ? '/api/requests/batch' : '/api/requests';

            const response = await axios.post(endpoint, payload);
            if (response.data.success) {
                setExtractedData(null);
                setFile(null);
                setStatus('idle');
                window.location.href = '/dashboard';
            }
        } catch (err: unknown) {
            console.error('Save failed', err);
            setError(err instanceof Error ? err.message : 'Failed to save record.');
            setStatus('completed');
        } finally {
            setIsSaving(false);
        }
    };

    const getFileIcon = () => {
        if (!file) return <CloudUpload className="size-10 text-muted-foreground/60" />;
        if (file.type.startsWith('image/')) return <ImageIcon className="size-10 text-primary" />;
        if (file.name.endsWith('.docx') || file.name.endsWith('.doc')) return <FileText className="size-10 text-primary" />;
        if (file.name.endsWith('.xlsx') || file.name.endsWith('.csv')) return <FileSpreadsheet className="size-10 text-primary" />;
        return <FileText className="size-10 text-muted-foreground" />;
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Smart Form Scan" />

            <div className="flex flex-col gap-6 p-6 bg-background min-h-screen">
                {/* Page Header */}
                <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-border">
                    <div className="flex items-center gap-4">
                        <div className="p-2 rounded-lg bg-primary/10 border border-primary/20">
                            <Zap className="size-5 text-primary" />
                        </div>
                        <div>
                            <h1 className="text-xl font-bold text-foreground">Smart Form Scan</h1>
                            <p className="text-sm text-muted-foreground uppercase tracking-wider font-medium opacity-70">Automated Data Extraction Assistant</p>
                        </div>
                    </div>
                </div>

                <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    {/* Left Column: Input Control */}
                    <div className="lg:col-span-12 xl:col-span-4 flex flex-col gap-6">
                        <Card className="bg-card border-border rounded-lg shadow-sm">
                            <div className="px-6 py-4 border-b border-border bg-muted/30">
                                <h2 className="text-[11px] font-bold text-muted-foreground uppercase tracking-widest">Source Document</h2>
                            </div>
                            <CardContent className="p-6 text-left">
                                <div
                                    className={cn(
                                        "relative group flex flex-col items-center justify-center min-h-[200px] border border-dashed rounded-lg transition-all duration-200 cursor-pointer",
                                        isDragging ? "border-primary bg-primary/5" : "border-border bg-muted/5 hover:border-primary/50 hover:bg-muted/10"
                                    )}
                                    onDragOver={onDragOver}
                                    onDragLeave={onDragLeave}
                                    onDrop={onDrop}
                                    onClick={() => document.getElementById('file-input')?.click()}
                                >
                                    <input
                                        type="file"
                                        id="file-input"
                                        className="hidden"
                                        onChange={(e) => e.target.files && handleFileSelect(e.target.files[0])}
                                    />

                                    <div className="mb-4">
                                        {getFileIcon()}
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
                                                onClick={(e) => { e.stopPropagation(); setFile(null); setExtractedData(null); }}
                                            >
                                                Remove File
                                            </Button>
                                        </div>
                                    ) : (
                                        <div className="text-center px-4">
                                            <p className="text-sm font-bold text-foreground">Upload Document</p>
                                            <p className="text-[10px] text-muted-foreground mt-1 uppercase tracking-widest">PDF, DOCX, IMG, XLSX</p>
                                        </div>
                                    )}
                                </div>

                                {status !== 'idle' && status !== 'error' && (
                                    <div className="mt-6 space-y-2">
                                        <div className="flex items-center justify-between text-[10px] font-bold text-muted-foreground uppercase">
                                            <span>{status === 'extracting' ? 'Analyzing Content' : 'Processing'}</span>
                                            <span>{Math.round(progress)}%</span>
                                        </div>
                                        <div className="h-1.5 w-full bg-muted rounded-full overflow-hidden border border-border">
                                            <div
                                                className="h-full bg-primary rounded-full transition-all duration-300"
                                                style={{ width: `${progress}%` }}
                                            />
                                        </div>
                                    </div>
                                )}

                                {error && (
                                    <div className="mt-6 p-4 rounded-lg bg-rose-500/5 border border-rose-500/10 flex items-start gap-3 text-rose-600">
                                        <AlertCircle className="size-4 mt-0.5" />
                                        <div className="flex-1">
                                            <p className="text-[10px] font-bold uppercase tracking-widest">Extraction Error</p>
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
                                <CardContent className="p-6">
                                    <div className="space-y-4">
                                        <div className="flex items-center justify-between text-[10px] font-bold text-muted-foreground uppercase tracking-widest">
                                            <span>Status</span>
                                            <Badge variant="outline" className="text-[9px] font-bold uppercase tracking-tighter px-2 py-0 border-border bg-muted/50">
                                                {status === 'completed' ? 'Extraction Done' : status === 'extracting' ? 'Analyzing' : 'Ready'}
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
                                            ) : status === 'completed' ? (
                                                <>
                                                    <CheckCircle2 className="mr-2 size-4" /> Start Over
                                                </>
                                            ) : (
                                                <>
                                                    <Zap className="mr-2 size-4 fill-current" /> Analyze Document
                                                </>
                                            )}
                                        </Button>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>

                    {/* Right Column: Results */}
                    <div className="lg:col-span-12 xl:col-span-8 flex flex-col">
                        <Card className="bg-card border-border rounded-lg shadow-sm flex flex-col h-full overflow-hidden">
                            <div className="px-6 py-4 border-b border-border bg-muted/30 flex items-center justify-between">
                                <div className="flex items-center gap-3">
                                    <FileText className="size-4 text-primary" />
                                    <h2 className="text-[11px] font-bold text-muted-foreground uppercase tracking-widest">Extraction Results</h2>
                                </div>
                            </div>
                            <CardContent className="flex-1 p-6">
                                {!extractedData ? (
                                    <div className="h-full flex flex-col items-center justify-center text-center py-20 px-10">
                                        <div className="size-16 bg-muted/10 border border-dashed border-border rounded-lg flex items-center justify-center mb-6">
                                            <Info className="size-6 text-muted-foreground/30" />
                                        </div>
                                        <h3 className="text-base font-bold text-zinc-950 dark:text-zinc-50 mb-1">Ready for Analysis</h3>
                                        <p className="text-xs text-muted-foreground leading-relaxed max-w-xs mx-auto">
                                            Upload a file and click "Analyze Document" to see results.
                                        </p>
                                    </div>
                                ) : Array.isArray(extractedData) ? (
                                    <div className="space-y-6 animate-in fade-in duration-500">
                                        <div className="flex items-center justify-between mb-4">
                                            <div className="flex items-center gap-2">
                                                <Badge variant="secondary" className="rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-widest bg-primary/10 text-primary border-primary/20">
                                                    Batch {extractedData.length} Records
                                                </Badge>
                                            </div>
                                        </div>

                                        <div className="border border-border rounded-lg overflow-hidden bg-card">
                                            <div className="overflow-x-auto max-h-[500px]">
                                                <table className="w-full text-left border-collapse">
                                                    <thead>
                                                        <tr className="bg-muted/50 border-b border-border">
                                                            <th className="px-4 py-3 text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Name</th>
                                                            <th className="px-4 py-3 text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Type of Request</th>
                                                            <th className="px-4 py-3 text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Office/Unit</th>
                                                            <th className="px-4 py-3 text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Date of Request</th>
                                                            <th className="px-4 py-3 text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Brief Description of Request</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody className="divide-y divide-border">
                                                        {extractedData.map((row, idx) => (
                                                            <tr key={idx} className="hover:bg-muted/30 transition-colors">
                                                                <td className="px-4 py-3 text-xs font-bold text-foreground">{row.name || '---'}</td>
                                                                <td className="px-4 py-3 text-[10px] font-bold uppercase tracking-tight text-primary">{row.request_type || '---'}</td>
                                                                <td className="px-4 py-3 text-xs text-muted-foreground">{row.office_unit || '---'}</td>
                                                                <td className="px-4 py-3 text-[10px] font-medium text-muted-foreground">
                                                                    {row.date_of_request ? new Date(row.date_of_request).toLocaleDateString() : '---'}
                                                                </td>
                                                                <td className="px-4 py-3 text-xs text-muted-foreground truncate max-w-[200px]">{row.request_description || '---'}</td>
                                                            </tr>
                                                        ))}
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>

                                        <div className="pt-6 border-t border-border flex items-center justify-between gap-4">
                                            <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-tight">Review extraction before final import</p>
                                            <div className="flex items-center gap-3">
                                                <Button variant="ghost" size="sm" className="h-9 px-4 text-xs font-bold uppercase" onClick={() => setExtractedData(null)}>
                                                    Discard
                                                </Button>
                                                <Button className="h-9 px-6 bg-primary text-primary-foreground font-bold text-xs uppercase" disabled={isSaving} onClick={handleSave}>
                                                    {isSaving ? <Loader2 className="animate-spin size-4 mr-2" /> : <Download className="size-4 mr-2" />}
                                                    Import All
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                ) : (
                                    <div className="space-y-6 animate-in fade-in duration-500">
                                        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                                            <div className="space-y-4 text-left">
                                                <div className="space-y-1.5">
                                                    <label className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Control Number</label>
                                                    <Input
                                                        className="h-9 bg-card border-border text-sm font-medium"
                                                        value={extractedData.control_no || ''}
                                                        onChange={(e) => setExtractedData({ ...extractedData, control_no: e.target.value })}
                                                    />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <label className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Name</label>
                                                    <Input
                                                        className="h-9 bg-card border-border text-sm font-medium"
                                                        value={extractedData.name || ''}
                                                        onChange={(e) => setExtractedData({ ...extractedData, name: e.target.value })}
                                                    />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <label className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Office/Unit</label>
                                                    <Input
                                                        className="h-9 bg-card border-border text-sm font-medium"
                                                        value={extractedData.office_unit || ''}
                                                        onChange={(e) => setExtractedData({ ...extractedData, office_unit: e.target.value })}
                                                    />
                                                </div>
                                            </div>
                                            <div className="space-y-4 text-left">
                                                <div className="space-y-1.5">
                                                    <label className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Type of Request</label>
                                                    <Input
                                                        className="h-9 bg-card border-border text-sm font-medium"
                                                        value={extractedData.request_type || ''}
                                                        onChange={(e) => setExtractedData({ ...extractedData, request_type: e.target.value })}
                                                    />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <label className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Location / Venue</label>
                                                    <Input
                                                        className="h-9 bg-card border-border text-sm font-medium"
                                                        value={extractedData.location_venue || ''}
                                                        onChange={(e) => setExtractedData({ ...extractedData, location_venue: e.target.value })}
                                                    />
                                                </div>
                                                <div className="space-y-1.5">
                                                    <label className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Date of Request</label>
                                                    <Input
                                                        type="date"
                                                        className="h-9 relative block w-full bg-card border-border text-[11px] font-medium dark:[&::-webkit-calendar-picker-indicator]:invert dark:[&::-webkit-calendar-picker-indicator]:opacity-60 [&::-webkit-calendar-picker-indicator]:absolute [&::-webkit-calendar-picker-indicator]:right-3"
                                                        value={extractedData.date_of_request?.substring(0, 10) || ''}
                                                        onChange={(e) => setExtractedData({ ...extractedData, date_of_request: e.target.value })}
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <div className="space-y-1.5 text-left">
                                            <label className="text-[10px] font-bold text-muted-foreground uppercase tracking-widest">Brief Description of Request</label>
                                            <textarea
                                                className="w-full min-h-[140px] bg-card border border-border rounded-md p-3 text-sm font-medium text-foreground leading-relaxed resize-none focus:outline-none focus:ring-1 focus:ring-primary/20"
                                                value={extractedData.request_description || ''}
                                                onChange={(e) => setExtractedData({ ...extractedData, request_description: e.target.value })}
                                            />
                                        </div>

                                        <div className="pt-6 border-t border-border flex flex-col sm:flex-row items-center justify-between gap-4">
                                            <div className="flex items-center gap-2">
                                                <div className="size-2 rounded-full bg-amber-500" />
                                                <p className="text-[10px] font-bold text-muted-foreground uppercase tracking-tight">Review before final save</p>
                                            </div>

                                            <div className="flex items-center gap-3 w-full sm:w-auto">
                                                <Button
                                                    variant="ghost"
                                                    className="flex-1 sm:flex-none h-9 text-muted-foreground font-bold text-xs uppercase"
                                                    onClick={() => setExtractedData(null)}
                                                >
                                                    Discard
                                                </Button>
                                                <Button
                                                    className="flex-1 sm:flex-none h-9 bg-primary text-primary-foreground hover:bg-primary/90 font-bold text-xs uppercase px-8"
                                                    disabled={isSaving}
                                                    onClick={handleSave}
                                                >
                                                    {isSaving ? <Loader2 className="animate-spin size-4 mr-2" /> : <CheckCircle2 className="size-4 mr-2" />}
                                                    Save Record
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
