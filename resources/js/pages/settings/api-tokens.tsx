import { useState, useEffect } from 'react';
import { router, Head } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Copy, Plus, Trash2, RotateCcw } from 'lucide-react';
import AppLayout from '@/layouts/app-layout';
import SettingsLayout from '@/layouts/settings/layout';
import HeadingSmall from '@/components/heading-small';
interface ApiToken {
    id: number;
    name: string;
    display_token: string;
    abilities: string[];
    last_used_at: string | null;
    expires_at: string | null;
    is_expired: boolean;
    is_active: boolean;
    created_at: string;
}

interface Props {
    tokens: ApiToken[];
    newToken?: {
        plaintext: string;
        name: string;
    };
    flash?: {
        success?: string;
    };
}

export default function ApiTokens({ tokens, newToken, flash }: Props) {
    const [showCreateModal, setShowCreateModal] = useState(false);
    const [showToken, setShowToken] = useState<string | null>(null);
    const [isCreating, setIsCreating] = useState(false);

    // Show token modal when we have a new token
    useEffect(() => {
        if (newToken?.plaintext) {
            console.log('New token received:', newToken);
            setShowToken(newToken.plaintext);
        }
    }, [newToken]);
    const [formData, setFormData] = useState({
        name: '',
        expires_at: '',
    });

    const handleCreateToken = async (e: React.FormEvent) => {
        e.preventDefault();
        setIsCreating(true);

        router.post('/settings/api-tokens', {
            ...formData,
            expires_at: formData.expires_at || null,
        }, {
            onSuccess: () => {
                setShowCreateModal(false);
                setFormData({ name: '', expires_at: '' });
            },
            onError: () => {
                alert('Failed to create API token');
            },
            onFinish: () => {
                setIsCreating(false);
            },
        });
    };

    const handleDeleteToken = (tokenId: number) => {
        if (confirm('Are you sure you want to revoke this token?')) {
            router.delete(`/settings/api-tokens/${tokenId}`);
        }
    };

    const handleRegenerateToken = (tokenId: number) => {
        if (confirm('Are you sure you want to regenerate this token?')) {
            router.post(`/settings/api-tokens/${tokenId}/regenerate`, {}, {
                preserveScroll: true,
            });
        }
    };

    const copyToClipboard = (text: string) => {
        navigator.clipboard.writeText(text);
        alert('Token copied to clipboard!');
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleDateString();
    };

    const formatDateTime = (dateString: string) => {
        return new Date(dateString).toLocaleString();
    };

    const getStatusBadge = (token: ApiToken) => {
        if (token.is_expired) {
            return <Badge variant="destructive">Expired</Badge>;
        }
        if (token.is_active) {
            return <Badge variant="default">Active</Badge>;
        }
        return <Badge variant="secondary">Inactive</Badge>;
    };

    return (
        <AppLayout>
            <Head title="API Tokens" />
            <SettingsLayout>
                <div className="space-y-6">
                    {flash?.success && (
                        <div className="p-4 mb-4 bg-green-50 border border-green-200 rounded-md">
                            <div className="text-green-800">
                                {flash.success} {newToken && `for "${newToken.name}"`}
                            </div>
                        </div>
                    )}

                    <div className="flex items-center justify-between">
                        <div>
                            <HeadingSmall title="API Tokens" />
                            <p className="text-muted-foreground">
                                Manage your API tokens for accessing the CMS API endpoints.
                            </p>
                        </div>
                
                <Dialog open={showCreateModal} onOpenChange={setShowCreateModal}>
                    <DialogTrigger asChild>
                        <Button>
                            <Plus className="mr-2 h-4 w-4" />
                            Create Token
                        </Button>
                    </DialogTrigger>
                    <DialogContent className="sm:max-w-[425px]">
                        <DialogHeader>
                            <DialogTitle>Create API Token</DialogTitle>
                            <DialogDescription>
                                Create a new API token to access your CMS data programmatically.
                            </DialogDescription>
                        </DialogHeader>
                        <form onSubmit={handleCreateToken} className="space-y-4">
                            <div>
                                <Label htmlFor="name">Token Name</Label>
                                <Input
                                    id="name"
                                    value={formData.name}
                                    onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                                    placeholder="e.g., Mobile App"
                                    required
                                />
                            </div>
                            
                            <div>
                                <Label htmlFor="expires_at">Expiration (Optional)</Label>
                                <Input
                                    id="expires_at"
                                    type="datetime-local"
                                    value={formData.expires_at}
                                    onChange={(e) => setFormData({ ...formData, expires_at: e.target.value })}
                                    min={new Date().toISOString().slice(0, 16)}
                                />
                            </div>

                            <div className="flex justify-end space-x-2">
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={() => setShowCreateModal(false)}
                                >
                                    Cancel
                                </Button>
                                <Button type="submit" disabled={isCreating}>
                                    {isCreating ? 'Creating...' : 'Create Token'}
                                </Button>
                            </div>
                        </form>
                    </DialogContent>
                </Dialog>
            </div>

            {/* Token Display Modal */}
            {showToken && (
                <Dialog open={!!showToken} onOpenChange={() => setShowToken(null)}>
                    <DialogContent className="sm:max-w-[500px]">
                        <DialogHeader>
                            <DialogTitle>🎉 Your API Token is Ready!</DialogTitle>
                            <DialogDescription>
                                <strong>Important:</strong> Copy this token now - it won't be shown again for security reasons.
                            </DialogDescription>
                        </DialogHeader>
                        <div className="space-y-4">
                            <div className="p-4 bg-muted rounded border">
                                <code className="text-sm break-all select-all">{showToken}</code>
                            </div>
                            <div className="text-sm text-muted-foreground">
                                <p><strong>How to use this token:</strong></p>
                                <p>Include it in the Authorization header of your API requests:</p>
                                <code className="block mt-2 p-2 bg-gray-100 rounded text-xs">
                                    Authorization: Bearer {showToken.substring(0, 20)}...
                                </code>
                            </div>
                            <Button
                                onClick={() => copyToClipboard(showToken)}
                                className="w-full"
                            >
                                <Copy className="mr-2 h-4 w-4" />
                                Copy Token to Clipboard
                            </Button>
                        </div>
                    </DialogContent>
                </Dialog>
            )}

            {/* Tokens List */}
            <div className="grid gap-4">
                {tokens.length === 0 ? (
                    <Card>
                        <CardContent className="py-8">
                            <p className="text-muted-foreground text-center">
                                No API tokens created yet.
                            </p>
                        </CardContent>
                    </Card>
                ) : (
                    tokens.map((token) => (
                        <Card key={token.id}>
                            <CardHeader>
                                <div className="flex items-center justify-between">
                                    <div>
                                        <CardTitle className="text-lg">{token.name}</CardTitle>
                                        <CardDescription>
                                            Created {formatDate(token.created_at)}
                                        </CardDescription>
                                    </div>
                                    <div className="flex items-center space-x-2">
                                        {getStatusBadge(token)}
                                        <Button
                                            variant="outline"
                                            size="sm"
                                            onClick={() => handleRegenerateToken(token.id)}
                                            disabled={token.is_expired}
                                        >
                                            <RotateCcw className="h-4 w-4" />
                                        </Button>
                                        <Button 
                                            variant="outline" 
                                            size="sm"
                                            onClick={() => handleDeleteToken(token.id)}
                                        >
                                            <Trash2 className="h-4 w-4" />
                                        </Button>
                                    </div>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-3">
                                    <div>
                                        <Label>Token</Label>
                                        <code className="block text-sm bg-muted p-2 rounded mt-1">
                                            {token.display_token}
                                        </code>
                                    </div>
                                    
                                    <div className="grid grid-cols-2 gap-4 text-sm">
                                        <div>
                                            <Label>Last Used</Label>
                                            <p>{token.last_used_at ? formatDateTime(token.last_used_at) : 'Never'}</p>
                                        </div>
                                        <div>
                                            <Label>Expires</Label>
                                            <p>{token.expires_at ? formatDateTime(token.expires_at) : 'Never'}</p>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>
                    ))
                )}
            </div>
                </div>
            </SettingsLayout>
        </AppLayout>
    );
}
