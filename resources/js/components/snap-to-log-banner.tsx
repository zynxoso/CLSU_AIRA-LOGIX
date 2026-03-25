import React, { useState, useRef } from 'react';
import { Camera, Loader2, Sparkles, X, Check } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';
import axios from 'axios';

interface SnapToLogBannerProps {
    onExtracted: (data: any) => void;
    className?: string;
}

export default function SnapToLogBanner({ onExtracted, className }: SnapToLogBannerProps) {
    const [isExtracting, setIsExtracting] = useState(false);
    const [status, setStatus] = useState<'idle' | 'processing' | 'success' | 'error'>('idle');
    const fileInputRef = useRef<HTMLInputElement>(null);

    const handleFileChange = async (e: React.ChangeEvent<HTMLInputElement>) => {
        const file = e.target.files?.[0];
        if (!file) return;

        setIsExtracting(true);
        setStatus('processing');

        const formData = new FormData();
        formData.append('file', file);

        try {
            const response = await axios.post('/api/extract', formData);
            
            if (response.data.success && response.data.job_id) {
                const jobId = response.data.job_id;
                
                const pollInterval = setInterval(async () => {
                    try {
                        const statusRes = await axios.get(`/api/extract/${jobId}/status`);
                        const { status: jobStatus, data: jobData, error } = statusRes.data;
                        
                        if (jobStatus === 'completed' && jobData) {
                            clearInterval(pollInterval);
                            
                            // Handle both array (batch) and object (single)
                            if (Array.isArray(jobData) && jobData.length > 0) {
                                onExtracted(jobData[0]);
                            } else {
                                onExtracted(jobData);
                            }
                            
                            setStatus('success');
                            setTimeout(() => setStatus('idle'), 3000);
                            setIsExtracting(false);
                        } else if (jobStatus === 'failed' || jobStatus === 'error' || error) {
                            clearInterval(pollInterval);
                            console.error('Extraction failed:', error);
                            setStatus('error');
                            setIsExtracting(false);
                        }
                    } catch (err) {
                        clearInterval(pollInterval);
                        console.error('Polling error:', err);
                        setStatus('error');
                        setIsExtracting(false);
                    }
                }, 2000);
                
            } else if (response.data.success && response.data.data) {
                // Fallback for synchronous response
                onExtracted(Array.isArray(response.data.data) ? response.data.data[0] : response.data.data);
                setStatus('success');
                setTimeout(() => setStatus('idle'), 3000);
                setIsExtracting(false);
            } else {
                setStatus('error');
                setIsExtracting(false);
            }
        } catch (error) {
            console.error('Extraction error:', error);
            setStatus('error');
            setIsExtracting(false);
        } finally {
            if (fileInputRef.current) fileInputRef.current.value = '';
        }
    };

    return (
        <div className={cn(
            "flex items-center gap-3 bg-muted/40 border border-border rounded-lg p-2 pr-3",
            status === 'success' && "border-primary/20 bg-primary/5",
            status === 'error' && "border-destructive/20 bg-destructive/5",
            className
        )}>
            <div className={cn(
                "p-1.5 rounded-md",
                status === 'idle' && "bg-background text-muted-foreground border border-border",
                status === 'processing' && "bg-primary/10 text-primary animate-pulse",
                status === 'success' && "bg-primary text-primary-foreground",
                status === 'error' && "bg-destructive text-destructive-foreground"
            )}>
                {status === 'processing' ? <Loader2 className="size-3.5 animate-spin" /> : 
                 status === 'success' ? <Check className="size-3.5" /> :
                 status === 'error' ? <X className="size-3.5" /> :
                 <Camera className="size-3.5" />}
            </div>

            <div className="flex flex-col flex-1">
                <span className="text-[11px] font-bold text-foreground leading-tight tracking-tight uppercase">Snap-to-Log</span>
                <span className="text-[10px] text-muted-foreground leading-none font-medium">
                    {status === 'processing' ? 'Analyzing...' : 
                     status === 'success' ? 'Extracted' :
                     status === 'error' ? 'Failed' :
                     'AI Auto-fill Vision'}
                </span>
            </div>

            <input 
                type="file" 
                ref={fileInputRef} 
                className="hidden" 
                accept="image/*,.pdf,.docx,.doc,.xlsx,.xls,.csv"
                onChange={handleFileChange}
            />

            <Button 
                type="button"
                variant={status === 'error' ? 'destructive' : status === 'success' ? 'secondary' : 'default'}
                size="sm"
                disabled={isExtracting}
                onClick={() => fileInputRef.current?.click()}
                className="h-7 px-3 text-[10px] font-bold uppercase tracking-tight"
            >
                {status === 'processing' ? '...' : 
                 status === 'success' ? 'Retry' :
                 status === 'error' ? 'Reset' : 
                 'Capture'}
            </Button>
        </div>
    );
}
