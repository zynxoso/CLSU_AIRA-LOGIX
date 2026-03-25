import AppLayout from '@/layouts/app-layout';
import { useAppearance } from '@/hooks/use-appearance';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import mermaid from 'mermaid';
import React, { useCallback, useEffect, useRef, useState } from 'react';

import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Documentation', href: '/dashboard/documentation' },
];

interface DocumentItem {
    fileName: string;
    title: string;
}

interface Props {
    documents: DocumentItem[];
    activeDocument: DocumentItem | null;
    contentHtml: string;
}

const DocumentationArticle = React.memo(
    ({ contentHtml, appearance, onDiagramClick }: { contentHtml: string; appearance: string; onDiagramClick: (svg: string) => void }) => {
        const articleRef = useRef<HTMLDivElement>(null);

        useEffect(() => {
            const isDark = appearance === 'dark' || (appearance === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

            mermaid.initialize({
                startOnLoad: false,
                theme: isDark ? 'dark' : 'default',
                securityLevel: 'loose',
                fontFamily: 'Inter, system-ui, sans-serif',
            });

            const renderMermaid = async () => {
                if (!articleRef.current) return;

                const blocks = Array.from(articleRef.current.querySelectorAll('pre code, pre, div.mermaid'));
                const mermaidKeywords = [
                    'graph',
                    'flowchart',
                    'sequenceDiagram',
                    'erDiagram',
                    'stateDiagram',
                    'classDiagram',
                    'gantt',
                    'pie',
                    'journey',
                    'gitGraph',
                ];

                for (let i = 0; i < blocks.length; i++) {
                    const element = blocks[i] as HTMLElement;
                    const code = (element.innerText || element.textContent || '').trim();
                    const parent = element.closest('pre') || element;

                    const hasMermaidClass =
                        element.classList.contains('language-mermaid') || element.classList.contains('mermaid') || parent.classList.contains('language-mermaid');

                    const startsWithKeyword = mermaidKeywords.some((keyword) => code.startsWith(keyword));

                    if (
                        code &&
                        parent &&
                        parent.parentNode &&
                        (hasMermaidClass || (startsWithKeyword && (element.tagName === 'CODE' || element.tagName === 'PRE')))
                    ) {
                        if (parent.dataset.mermaidProcessed) continue;
                        parent.dataset.mermaidProcessed = 'true';

                        try {
                            const id = `mermaid-chart-${i}-${Date.now()}`;
                            const { svg } = await mermaid.render(id, code);

                            const container = document.createElement('div');
                            container.className =
                                'mermaid-rendered group relative my-8 flex cursor-zoom-in flex-col items-center justify-center overflow-hidden rounded-2xl border border-border/50 bg-card/40 p-6 shadow-sm backdrop-blur-sm transition-all hover:bg-card/60 hover:shadow-md';
                            container.innerHTML = `
                            ${svg}
                            <div class="absolute right-4 top-4 rounded-full bg-background/50 p-2 opacity-0 shadow-sm backdrop-blur-md transition-all group-hover:opacity-100">
                                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-foreground/70"><polyline points="15 3 21 3 21 9"/><polyline points="9 21 3 21 3 15"/><line x1="21" y1="3" x2="14" y2="10"/><line x1="3" y1="21" x2="10" y2="14"/></svg>
                            </div>
                        `;

                            container.onclick = () => onDiagramClick(svg);

                            const svgElement = container.querySelector('svg');
                            if (svgElement) {
                                svgElement.style.maxWidth = '100%';
                                svgElement.style.height = 'auto';
                            }

                            parent.parentNode.replaceChild(container, parent);
                        } catch (err) {
                            console.error('Mermaid error at index', i, err);
                            parent.dataset.mermaidProcessed = 'error';
                        }
                    }
                }
            };

            const timer = setTimeout(renderMermaid, 150);
            return () => clearTimeout(timer);
        }, [contentHtml, appearance, onDiagramClick]);

        return (
            <article
                ref={articleRef}
                className="max-w-none text-sm leading-7 text-foreground/95 [&_a]:text-primary [&_a]:underline [&_a]:underline-offset-4 [&_blockquote]:border-l-2 [&_blockquote]:border-border [&_blockquote]:pl-4 [&_code]:rounded-md [&_code]:bg-muted [&_code]:px-1.5 [&_code]:py-0.5 [&_code]:text-[0.85em] [&_h1]:mb-4 [&_h1]:text-4xl [&_h1]:font-extrabold [&_h1]:tracking-tight [&_h2]:mb-3 [&_h2]:mt-10 [&_h2]:text-3xl [&_h2]:font-bold [&_h2]:tracking-tight [&_h3]:mb-2 [&_h3]:mt-8 [&_h3]:text-2xl [&_h3]:font-semibold [&_h4]:mb-2 [&_h4]:mt-6 [&_h4]:text-xl [&_h4]:font-semibold [&_li]:my-1 [&_ol]:my-4 [&_ol]:list-decimal [&_ol]:pl-6 [&_p]:my-4 [&_pre]:my-5 [&_pre]:overflow-x-auto [&_pre]:rounded-lg [&_pre]:border [&_pre]:border-border/60 [&_pre]:bg-background [&_pre]:p-3 [&_strong]:font-bold [&_table]:my-6 [&_table]:w-full [&_table]:border-collapse [&_td]:border [&_td]:border-border/60 [&_td]:px-3 [&_td]:py-2 [&_th]:border [&_th]:border-border/60 [&_th]:bg-muted/40 [&_th]:px-3 [&_th]:py-2 [&_ul]:my-4 [&_ul]:list-disc [&_ul]:pl-6"
                dangerouslySetInnerHTML={{ __html: contentHtml }}
            />
        );
    },
);

DocumentationArticle.displayName = 'DocumentationArticle';

export default function Documentation({ documents, activeDocument, contentHtml }: Props) {
    const { appearance } = useAppearance();
    const [isModalOpen, setIsModalOpen] = useState(false);
    const [activeDiagramSvg, setActiveDiagramSvg] = useState<string>('');

    const handleDiagramClick = useCallback((svg: string) => {
        setActiveDiagramSvg(svg);
        setIsModalOpen(true);
    }, []);

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Documentation" />

            <div className="h-[calc(100svh-4.5rem)] p-2 md:p-4">
                <div className="grid h-full grid-cols-1 gap-3 rounded-2xl border border-border/70 bg-background/40 p-2 backdrop-blur-sm lg:grid-cols-[320px_1fr]">
                    <aside className="h-full overflow-hidden rounded-xl border border-border/60 bg-card/80">
                        <div className="h-full overflow-y-auto p-2 no-scrollbar">
                            <div className="space-y-1.5">
                                {documents.map((doc) => {
                                    const isActive = activeDocument?.fileName === doc.fileName;

                                    return (
                                        <Link
                                            key={doc.fileName}
                                            href={`/dashboard/documentation?doc=${encodeURIComponent(doc.fileName)}`}
                                            preserveScroll
                                            className={`block rounded-lg border px-3 py-2 text-sm font-medium transition-colors ${
                                                isActive
                                                    ? 'border-primary/40 bg-primary/20 text-primary'
                                                    : 'border-border/60 bg-muted/30 text-muted-foreground hover:border-primary/25 hover:bg-primary/10 hover:text-foreground'
                                            }`}
                                        >
                                            {doc.title}
                                        </Link>
                                    );
                                })}
                            </div>
                        </div>
                    </aside>

                    <section className="h-full overflow-hidden rounded-xl border border-border/60 bg-card/90">
                        <div className="flex h-full flex-col">
                            <div className="flex items-center justify-between border-b border-border/60 px-5 py-4">
                                <h1 className="text-lg font-bold tracking-tight text-foreground">{activeDocument?.title ?? 'Documentation'}</h1>
                                <span className="max-w-[45%] truncate text-xs text-muted-foreground">{activeDocument?.fileName ?? 'No file selected'}</span>
                            </div>

                            <div className="min-h-0 flex-1 overflow-y-auto px-5 py-6 no-scrollbar">
                                {activeDocument ? (
                                    <DocumentationArticle contentHtml={contentHtml} appearance={appearance} onDiagramClick={handleDiagramClick} />
                                ) : (
                                    <div className="rounded-lg border border-dashed border-border bg-muted/20 p-8 text-center text-sm text-muted-foreground">
                                        No markdown documentation files were found in docs/DOCUMENTATION_MD.
                                    </div>
                                )}
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <Dialog open={isModalOpen} onOpenChange={setIsModalOpen}>
                <DialogContent className="max-w-[95vw] w-full max-h-[95vh] h-full flex flex-col p-4 md:p-6 overflow-hidden border-none bg-background/95 backdrop-blur-xl">
                    <DialogHeader className="flex flex-row items-center justify-between space-y-0 p-1 pr-10">
                        <DialogTitle className="text-xl font-bold tracking-tight">Diagram Preview</DialogTitle>
                    </DialogHeader>
                    <div className="flex-1 min-h-0 w-full flex items-center justify-center p-4 md:p-8 bg-card/10 rounded-2xl border border-border/40 mt-2 overflow-auto no-scrollbar">
                        <div
                            className="w-full h-full flex items-center justify-center [&_svg]:max-w-full [&_svg]:max-h-[80vh] [&_svg]:w-auto [&_svg]:h-auto"
                            dangerouslySetInnerHTML={{ __html: activeDiagramSvg }}
                        />
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
