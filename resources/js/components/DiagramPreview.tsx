
import React, { useRef, useEffect, useState } from 'react';

interface DiagramPreviewProps {
  src: string;
  alt?: string;
}

export const DiagramPreview: React.FC<DiagramPreviewProps> = ({ src, alt }) => {
  const containerRef = useRef<HTMLDivElement>(null);
  const imgRef = useRef<HTMLImageElement>(null);
  const [modalOpen, setModalOpen] = useState(false);

  // Zoom & pan logic for both preview and modal
  const useZoomPan = (imgRef: React.RefObject<HTMLImageElement>, containerRef: React.RefObject<HTMLDivElement>) => {
    useEffect(() => {
      const container = containerRef.current;
      const img = imgRef.current;
      if (!container || !img) return;
      let scale = 1;
      let origin = { x: 0, y: 0 };
      let isPanning = false;
      let start = { x: 0, y: 0 };
      const onWheel = (e: WheelEvent) => {
        e.preventDefault();
        const delta = e.deltaY > 0 ? -0.1 : 0.1;
        scale = Math.max(0.2, Math.min(3, scale + delta));
        img.style.transform = `scale(${scale}) translate(${origin.x}px, ${origin.y}px)`;
      };
      const onMouseDown = (e: MouseEvent) => {
        isPanning = true;
        start = { x: e.clientX - origin.x, y: e.clientY - origin.y };
        container.style.cursor = 'grabbing';
      };
      const onMouseMove = (e: MouseEvent) => {
        if (!isPanning) return;
        origin = { x: e.clientX - start.x, y: e.clientY - start.y };
        img.style.transform = `scale(${scale}) translate(${origin.x}px, ${origin.y}px)`;
      };
      const onMouseUp = () => {
        isPanning = false;
        container.style.cursor = 'grab';
      };
      container.addEventListener('wheel', onWheel, { passive: false });
      container.addEventListener('mousedown', onMouseDown);
      window.addEventListener('mousemove', onMouseMove);
      window.addEventListener('mouseup', onMouseUp);
      container.style.cursor = 'grab';
      return () => {
        container.removeEventListener('wheel', onWheel);
        container.removeEventListener('mousedown', onMouseDown);
        window.removeEventListener('mousemove', onMouseMove);
        window.removeEventListener('mouseup', onMouseUp);
      };
    }, [imgRef, containerRef]);
  };

  // For modal zoom/pan
  const modalImgRef = useRef<HTMLImageElement>(null);
  const modalContainerRef = useRef<HTMLDivElement>(null);
  useZoomPan(imgRef, containerRef);
  useZoomPan(modalImgRef, modalContainerRef);

  return (
    <>
      <div
        ref={containerRef}
        style={{
          width: '100%',
          height: 400,
          overflow: 'hidden',
          borderRadius: 12,
          border: '1px solid #e5e7eb',
          background: '#fff',
          position: 'relative',
          userSelect: 'none',
          WebkitOverflowScrolling: 'touch',
          scrollbarWidth: 'none',
          msOverflowStyle: 'none',
          cursor: 'pointer',
        }}
        className="diagram-preview no-scrollbar group"
        onClick={() => setModalOpen(true)}
        title="Click to expand diagram"
      >
        <img
          ref={imgRef}
          src={src}
          alt={alt || 'Diagram preview'}
          style={{
            width: '100%',
            height: '100%',
            objectFit: 'contain',
            transition: 'transform 0.2s',
            pointerEvents: 'all',
            userSelect: 'none',
          }}
          draggable={false}
        />
        <div className="absolute bottom-2 right-2 bg-black/60 text-white text-xs px-2 py-1 rounded opacity-0 group-hover:opacity-100 transition-opacity pointer-events-none">
          Expand
        </div>
      </div>
      {modalOpen && (
        <div
          className="fixed inset-0 z-50 flex items-center justify-center bg-black/70"
          onClick={() => setModalOpen(false)}
          style={{ cursor: 'zoom-out' }}
        >
          <div
            ref={modalContainerRef}
            style={{
              width: '80vw',
              height: '80vh',
              background: '#fff',
              borderRadius: 16,
              overflow: 'hidden',
              boxShadow: '0 8px 32px rgba(0,0,0,0.25)',
              position: 'relative',
              display: 'flex',
              alignItems: 'center',
              justifyContent: 'center',
            }}
            onClick={e => e.stopPropagation()}
          >
            <img
              ref={modalImgRef}
              src={src}
              alt={alt || 'Diagram preview'}
              style={{
                width: '100%',
                height: '100%',
                objectFit: 'contain',
                transition: 'transform 0.2s',
                pointerEvents: 'all',
                userSelect: 'none',
              }}
              draggable={false}
            />
            <button
              onClick={() => setModalOpen(false)}
              className="absolute top-3 right-3 bg-black/70 text-white rounded-full p-2 hover:bg-black/90 focus:outline-none"
              style={{ zIndex: 10 }}
              aria-label="Close preview"
            >
              <svg width="20" height="20" fill="none" viewBox="0 0 24 24"><path stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" d="M18 6 6 18M6 6l12 12"/></svg>
            </button>
          </div>
        </div>
      )}
    </>
  );
};
