import AppLayout from '@/layouts/app-layout';
import { Head } from '@inertiajs/react';
import { useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent } from '@/components/ui/card';
import { Dialog, DialogContent, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Badge } from '@/components/ui/badge';
import { Image, Upload, Trash2, Edit } from 'lucide-react';

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

interface MediaIndexProps {
    media: {
        data: Media[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
}

export default function MediaIndex({ media }: MediaIndexProps) {
    const [uploading, setUploading] = useState(false);
    const [editingMedia, setEditingMedia] = useState<Media | null>(null);
    const [editName, setEditName] = useState('');
    // eslint-disable-next-line @typescript-eslint/no-unused-vars
    const [itemView, setItemView] = useState<'list' | 'grid'>('grid');

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
                    },
                });

                if (!response.ok) {
                    throw new Error('Upload failed');
                }
            }

            // Reload the page to show new media
            window.location.reload();
        } catch (error) {
            console.error('Upload error:', error);
            alert('Upload failed');
        } finally {
            setUploading(false);
        }
    };

    const handleDelete = async (id: number) => {
        if (!confirm('Are you sure you want to delete this media?')) return;

        try {
            const response = await fetch(`/media/${id}`, {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '',
                },
            });

            if (response.ok) {
                window.location.reload();
            } else {
                alert('Delete failed');
            }
        } catch (error) {
            console.error('Delete error:', error);
            alert('Delete failed');
        }
    };

    const handleEdit = async () => {
        if (!editingMedia) return;

        try {
            const response = await fetch(`/media/${editingMedia.id}`, {
                method: 'PUT',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || '',
                },
                body: JSON.stringify({ name: editName }),
            });

            if (response.ok) {
                setEditingMedia(null);
                window.location.reload();
            } else {
                alert('Update failed');
            }
        } catch (error) {
            console.error('Update error:', error);
            alert('Update failed');
        }
    };

    const isImage = (mimeType: string) => mimeType.startsWith('image/');


    // const displayItemView = () => {
    //     if (itemView === 'list') {
    //         return (<></>)
    //     }
    //     if (itemView === 'grid') {
    //         return (<></>)
    //     }
    //     return null;
    // };

    return (
        <AppLayout
            breadcrumbs={[
                { title: 'Media', href: '/media' },
            ]}
        >
            <Head title="Media" />

            <div className="flex items-center justify-between p-4">
                <h1 className="text-2xl font-bold">Media</h1>
                <div className="flex gap-2">
                    <Dialog>
                        <DialogTrigger asChild>
                            <Button disabled={uploading}>
                                <Upload className="w-4 h-4 mr-2" />
                                {uploading ? 'Uploading...' : 'Upload Media'}
                            </Button>
                        </DialogTrigger>
                        <DialogContent>
                            <DialogHeader>
                                <DialogTitle>Upload Media</DialogTitle>
                            </DialogHeader>
                            <div className="space-y-4">
                                <div>
                                    <Label htmlFor="file-upload">Select files</Label>
                                    <Input
                                        id="file-upload"
                                        type="file"
                                        multiple
                                        accept="image/*,video/*,audio/*,application/*,.webp"
                                        onChange={(e) => handleFileUpload(e.target.files)}
                                        disabled={uploading}
                                    />
                                </div>
                                <p className="text-sm text-muted-foreground">
                                    You can upload multiple files at once. Supported formats: images, videos, audio, documents.
                                </p>
                            </div>
                        </DialogContent>
                    </Dialog>
                </div>
            </div>

            <div className="p-4">
                {media.data.length === 0 ? (
                    <Card>
                        <CardContent className="flex flex-col items-center justify-center py-12">
                            <Image className="w-12 h-12 text-muted-foreground mb-4" />
                            <h3 className="text-lg font-semibold mb-2">No media yet</h3>
                            <p className="text-muted-foreground text-center mb-4">
                                Upload your first media file to get started.
                            </p>
                            <Button onClick={() => document.getElementById('file-upload')?.click()}>
                                <Upload className="w-4 h-4 mr-2" />
                                Upload Media
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
                        {media.data.map((item) => (
                            <Card key={item.id} className="group relative">
                                <CardContent className="p-4">
                                    <div className="aspect-square bg-muted rounded-lg mb-3 flex items-center justify-center overflow-hidden">
                                        {isImage(item.mime_type) ? (
                                            <img
                                                src={item.url}
                                                alt={item.name}
                                                className="w-full h-full object-cover"
                                            />
                                        ) : (
                                            <div className="text-4xl">📄</div>
                                        )}
                                    </div>
                                    <div className="space-y-2">
                                        <h4 className="font-medium truncate" title={item.name}>
                                            {item.name}
                                        </h4>
                                        <div className="flex items-center justify-between text-sm text-muted-foreground">
                                            <span>{item.human_readable_size}</span>
                                            <Badge variant="secondary">{item.mime_type.split('/')[0]}</Badge>
                                        </div>
                                        <div className="flex gap-1">
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => {
                                                    setEditingMedia(item);
                                                    setEditName(item.name);
                                                }}
                                            >
                                                <Edit className="w-3 h-3" />
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => window.open(item.url, '_blank')}
                                            >
                                                View
                                            </Button>
                                            <Button
                                                variant="outline"
                                                size="sm"
                                                onClick={() => navigator.clipboard.writeText(item.url)}
                                            >
                                                Copy URL
                                            </Button>
                                            <Button
                                                variant="destructive"
                                                size="sm"
                                                onClick={() => handleDelete(item.id)}
                                            >
                                                <Trash2 className="w-3 h-3" />
                                            </Button>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        ))}
                    </div>
                )}

                {media.last_page > 1 && (
                    <div className="flex justify-center mt-8">
                        {/* Pagination would go here */}
                    </div>
                )}
            </div>

            {/* Edit Dialog */}
            <Dialog open={!!editingMedia} onOpenChange={() => setEditingMedia(null)}>
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Edit Media</DialogTitle>
                    </DialogHeader>
                    <div className="space-y-4">
                        <div>
                            <Label htmlFor="edit-name">Name</Label>
                            <Input
                                id="edit-name"
                                value={editName}
                                onChange={(e) => setEditName(e.target.value)}
                            />
                        </div>
                        <div className="flex justify-end gap-2">
                            <Button variant="outline" onClick={() => setEditingMedia(null)}>
                                Cancel
                            </Button>
                            <Button onClick={handleEdit}>
                                Save
                            </Button>
                        </div>
                    </div>
                </DialogContent>
            </Dialog>
        </AppLayout>
    );
}
