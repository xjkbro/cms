import { useEffect, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Upload, Image as ImageIcon, Link } from 'lucide-react';

type Media = {
    id: number;
    name: string;
    file_name: string;
    mime_type: string;
    path: string;
    size: number;
    url: string;
    human_readable_size: string;
    created_at: string;
};

interface MediaBrowserProps {
    onSelect: (url: string, width?: number, height?: number) => void;
    trigger?: React.ReactNode;
}

export function MediaBrowser({ onSelect, trigger }: MediaBrowserProps) {
    const [media, setMedia] = useState<Media[]>([]);
    const [loading, setLoading] = useState(true);
    const [uploading, setUploading] = useState(false);
    const [open, setOpen] = useState(false);

    const fetchMedia = async () => {
        try {
            const response = await fetch('/media', {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                },
            });
            if (response.ok) {
                const data = await response.json();
                setMedia(data.media.data);
            }
        } catch (error) {
            console.error('Failed to fetch media:', error);
        } finally {
            setLoading(false);
        }
    };

    useEffect(() => {
        if (open) {
            fetchMedia();
        }
    }, [open]);

    const handleFileUpload = async (files: FileList | null) => {
        if (!files) return;

        setUploading(true);
        try {
            for (const file of Array.from(files)) {
                const formData = new FormData();
                formData.append('file', file);

                const response = await fetch('/media', {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '',
                        'Accept': 'application/json',
                    },
                });

                if (response.ok) {
                    const result = await response.json();
                    setMedia(prev => [result.media, ...prev]);
                }
            }
        } catch (error) {
            console.error('Upload error:', error);
            alert('Upload failed');
        } finally {
            setUploading(false);
        }
    };

    const handleSelect = (media: Media) => {
        if (!isImage(media.mime_type)) {
            onSelect(media.url);
            setOpen(false);
        }
    };

    const handleSizeSelect = (media: Media, width?: number, height?: number) => {
        let url = media.url;
        if (width || height) {
            const urlObj = new URL(media.url);
            if (width) urlObj.searchParams.set('w', width.toString());
            if (height) urlObj.searchParams.set('h', height.toString());
            url = urlObj.toString();
        }

        onSelect(url, width, height);
        setOpen(false);
    };

    const isImage = (mimeType: string) => mimeType.startsWith('image/');

    return (
        <Dialog open={open} onOpenChange={setOpen}>
            <DialogTrigger asChild>
                {trigger || (
                    <Button type="button" size="sm" variant="ghost">
                        <ImageIcon className="w-4 h-4" />
                    </Button>
                )}
            </DialogTrigger>
            <DialogContent className="sm:min-w-xl md:min-w-3xl lg:min-w-4xl max-h-[80vh] overflow-hidden">
                <DialogHeader>
                    <DialogTitle>Media Library</DialogTitle>
                </DialogHeader>

                <div className="space-y-4">
                    <Label htmlFor="media-upload" className="cursor-pointer">
                        {/* Upload Section */}
                        <div className="border-2 border-dashed border-muted-foreground/25 rounded-lg p-4 hover:bg-neutral-700/10 transition">
                            <div className="text-center">
                                <Upload className="w-8 h-8 mx-auto text-muted-foreground mb-2" />
                                    <span className="text-sm font-medium">Click to upload</span>
                                    <Input
                                        id="media-upload"
                                        type="file"
                                        multiple
                                        accept="image/*,.webp"
                                        className="hidden"
                                        onChange={(e) => handleFileUpload(e.target.files)}
                                        disabled={uploading}
                                    />
                                {uploading && <p className="text-sm text-muted-foreground mt-2">Uploading...</p>}
                            </div>
                        </div>
                    </Label>

                    {/* URL Section */}
                    <div className="flex gap-2 mt-2">
                        <Input
                            placeholder="Enter image URL"
                            id="url-input"
                            type="url"
                        />
                        <Button
                            onClick={async () => {
                                const urlInput = document.getElementById('url-input') as HTMLInputElement;
                                const url = urlInput.value.trim();
                                if (!url) return;

                                try {
                                    const response = await fetch('/media', {
                                        method: 'POST',
                                        headers: {
                                            'Content-Type': 'application/json',
                                            'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '',
                                            'Accept': 'application/json',
                                        },
                                        body: JSON.stringify({ url }),
                                    });

                                    if (response.ok) {
                                        const result = await response.json();
                                        setMedia(prev => [result.media, ...prev]);
                                        urlInput.value = '';
                                    } else {
                                        alert('Failed to add URL');
                                    }
                                } catch (error) {
                                    console.error('URL add error:', error);
                                    alert('Failed to add URL');
                                }
                            }}
                        >
                            <Link className="w-4 h-4 mr-2" />
                            Add URL
                        </Button>
                    </div>

                    {/* Media Grid */}
                    <div className="h-96 max-h-96 overflow-y-auto">
                        {loading ? (
                            <div className="text-center py-8">Loading media...</div>
                        ) : media.length === 0 ? (
                            <div className="text-center py-8 text-muted-foreground">
                                No media uploaded yet
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 md:grid-cols-3 gap-4">
                                {media.map((item) => (
                                    <div
                                        key={item.id}
                                        className="group relative flex border rounded-lg overflow-hidden hover:border-primary"
                                    >
                                        <div className="w-20 aspect-square bg-muted flex items-center justify-center">
                                            {isImage(item.mime_type) ? (
                                                <img
                                                    src={item.url}
                                                    alt={item.name}
                                                    className="w-full h-full object-cover"
                                                />
                                            ) : (
                                                <div className="text-2xl">📄</div>
                                            )}
                                        </div>
                                        <div className="p-2">
                                            <p className="text-xs font-medium truncate" title={item.name}>
                                                {item.name.length > 20 ? item.name.substring(0, 20) + '...' : item.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {item.human_readable_size}
                                            </p>
                                            {isImage(item.mime_type) ? (
                                                <div className="flex gap-1 mt-2">
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        className="h-6 px-2 text-xs cursor-pointer"
                                                        onClick={() => handleSizeSelect(item, undefined, undefined)}
                                                    >
                                                        Original
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        className="h-6 px-2 text-xs cursor-pointer"
                                                        onClick={() => handleSizeSelect(item, 800, undefined)}
                                                    >
                                                        W:800
                                                    </Button>
                                                    <Button
                                                        size="sm"
                                                        variant="outline"
                                                        className="h-6 px-2 text-xs cursor-pointer"
                                                        onClick={() => handleSizeSelect(item, undefined, 600)}
                                                    >
                                                        H:600
                                                    </Button>
                                                </div>
                                            ) : (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    className="h-6 w-full mt-2 text-xs"
                                                    onClick={() => handleSelect(item)}
                                                >
                                                    Select
                                                </Button>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        )}
                    </div>
                </div>
            </DialogContent>
        </Dialog>
    );
}
