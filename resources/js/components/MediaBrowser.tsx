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
    onSelect: (url: string) => void;
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

    const handleSelect = (selectedMedia: Media) => {
        onSelect(selectedMedia.url);
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
            <DialogContent className="max-w-4xl max-h-[80vh] overflow-hidden">
                <DialogHeader>
                    <DialogTitle>Media Library</DialogTitle>
                </DialogHeader>

                <div className="space-y-4">
                    {/* Upload Section */}
                    <div className="border-2 border-dashed border-muted-foreground/25 rounded-lg p-4">
                        <div className="text-center">
                            <Upload className="w-8 h-8 mx-auto text-muted-foreground mb-2" />
                            <Label htmlFor="media-upload" className="cursor-pointer">
                                <span className="text-sm font-medium">Click to upload</span>
                                <Input
                                    id="media-upload"
                                    type="file"
                                    multiple
                                    accept="image/*"
                                    className="hidden"
                                    onChange={(e) => handleFileUpload(e.target.files)}
                                    disabled={uploading}
                                />
                            </Label>
                            {uploading && <p className="text-sm text-muted-foreground mt-2">Uploading...</p>}
                        </div>
                    </div>

                    {/* URL Section */}
                    <div className="flex gap-2">
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
                    <div className="max-h-96 overflow-y-auto">
                        {loading ? (
                            <div className="text-center py-8">Loading media...</div>
                        ) : media.length === 0 ? (
                            <div className="text-center py-8 text-muted-foreground">
                                No media uploaded yet
                            </div>
                        ) : (
                            <div className="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-4">
                                {media.map((item) => (
                                    <div
                                        key={item.id}
                                        className="group relative border rounded-lg overflow-hidden cursor-pointer hover:border-primary"
                                        onClick={() => handleSelect(item)}
                                    >
                                        <div className="aspect-square bg-muted flex items-center justify-center">
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
                                                {item.name}
                                            </p>
                                            <p className="text-xs text-muted-foreground">
                                                {item.human_readable_size}
                                            </p>
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
