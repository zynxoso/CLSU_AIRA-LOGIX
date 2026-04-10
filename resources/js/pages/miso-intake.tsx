import { useState } from 'react';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem, type MisoAccomplishment } from '@/types';
import { Head } from '@inertiajs/react';
import MisoRequestForm from '@/components/miso-request-form';
import SnapToLogBanner from '@/components/snap-to-log-banner';
import { Sparkles } from 'lucide-react';

interface Props {
    tab: string;
    category: MisoAccomplishment['category'];
}

export default function MisoIntake({ tab, category }: Props) {
    const [extractedData, setExtractedData] = useState<Partial<MisoAccomplishment> | null>(null);

    const breadcrumbs: BreadcrumbItem[] = [
        { title: 'Dashboard', href: '/dashboard' },
        { title: 'New MISO Accomplishment', href: `/miso-accomplishments/create?tab=${tab}` },
    ];

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="New MISO Accomplishment" />

            <div className="flex-1 space-y-12 p-3 md:p-6 lg:p-8 w-full">
                <div className="flex flex-col md:flex-row md:items-center justify-between gap-8 border-b border-border/50 pb-12 w-full">
                    <div className="flex items-center gap-7">
                        <div className="p-4 bg-primary/5 rounded-xl border border-primary/10 shadow-md transition-all hover:scale-105 duration-500 flex items-center justify-center">
                            <Sparkles className="size-10 text-primary animate-pulse" />
                        </div>
                        <div>
                            <h1 className="text-4xl md:text-5xl font-black text-foreground tracking-tighter leading-none">New MISO Accomplishment</h1>
                            <p className="text-muted-foreground text-sm font-medium mt-3 w-full max-w-xl">
                                Fill out the project status form or use Snap-to-Log to auto-fill extracted details.
                            </p>
                        </div>
                    </div>

                    <SnapToLogBanner
                        onExtracted={setExtractedData}
                        className="w-full md:w-auto"
                        extractEndpoint="/api/miso/extract"
                        statusEndpointTemplate="/api/miso/extract/{jobId}/status"
                        extraFormFields={{ category }}
                    />
                </div>

                <div className="relative w-full">
                    <div className="absolute top-0 right-0 w-[600px] h-[600px] bg-primary/5 blur-[180px] -z-10 rounded-full animate-pulse" />
                    <div className="absolute bottom-0 left-0 w-[600px] h-[600px] bg-zinc-500/5 dark:bg-zinc-500/2 blur-[180px] -z-10 rounded-full" />

                    <MisoRequestForm category={category} tab={tab} isEdit={false} extractedData={extractedData} />
                </div>
            </div>
        </AppLayout>
    );
}
