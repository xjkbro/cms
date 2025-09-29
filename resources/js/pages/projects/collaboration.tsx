import { Head } from '@inertiajs/react';
import AppLayout from '@/layouts/app-layout';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Badge } from '@/components/ui/badge';
import { Link, useForm, router } from '@inertiajs/react';
import { useState } from 'react';

interface User {
    id: number;
    name: string;
    email: string;
    pivot?: {
        role: string;
        joined_at: string;
    };
}

interface Project {
    id: number;
    name: string;
    user: User;
}

interface Invitation {
    id: number;
    email: string;
    role: string;
    expires_at: string;
    invited_by: User;
}

interface CollaborationProps {
    project: Project;
    collaborators: User[];
    pendingInvitations: Invitation[];
}

const roleColors = {
    owner: 'bg-purple-100 text-purple-800',
    admin: 'bg-red-100 text-red-800',
    editor: 'bg-blue-100 text-blue-800',
    viewer: 'bg-gray-100 text-gray-800',
};

export default function Collaboration({ project, collaborators, pendingInvitations }: CollaborationProps) {
    const [showInviteForm, setShowInviteForm] = useState(false);

    const inviteForm = useForm({
        email: '',
        role: 'editor' as 'admin' | 'editor' | 'viewer',
    });

    const handleInvite = (e: React.FormEvent) => {
        e.preventDefault();
        inviteForm.post(`/projects/${project.id}/invite`, {
            onSuccess: () => {
                inviteForm.reset();
                setShowInviteForm(false);
            },
        });
    };

    const updateRole = (userId: number, newRole: string) => {
        router.post(`/projects/${project.id}/collaborators/${userId}/role`, {
            role: newRole,
        });
    };

    const removeCollaborator = (userId: number) => {
        if (confirm('Are you sure you want to remove this collaborator?')) {
            router.delete(`/projects/${project.id}/collaborators/${userId}`);
        }
    };

    const cancelInvitation = (invitationId: number) => {
        if (confirm('Are you sure you want to cancel this invitation?')) {
            router.delete(`/projects/${project.id}/invitations/${invitationId}`);
        }
    };

    return (
        <AppLayout>
            <Head title={`${project.name} - Collaboration`} />

            <div className="p-6 space-y-6">
                <div className="flex items-center justify-between">
                    <div>
                        <h1 className="text-2xl font-bold">Project Collaboration</h1>
                        <p className="text-muted-foreground">Manage team members and permissions for {project.name}</p>
                    </div>
                    <Button
                        onClick={() => setShowInviteForm(true)}
                        disabled={showInviteForm}
                    >
                        Invite Member
                    </Button>
                </div>

                {showInviteForm && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Invite New Member</CardTitle>
                            <CardDescription>
                                Send an invitation to collaborate on this project
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={handleInvite} className="space-y-4">
                                <div>
                                    <Input
                                        placeholder="Email address"
                                        type="email"
                                        value={inviteForm.data.email}
                                        onChange={(e) => inviteForm.setData('email', e.target.value)}
                                        required
                                    />
                                    {inviteForm.errors.email && (
                                        <p className="text-sm text-red-600 mt-1">{inviteForm.errors.email}</p>
                                    )}
                                </div>
                                <div>
                                    <Select
                                        value={inviteForm.data.role}
                                        onValueChange={(value: 'admin' | 'editor' | 'viewer') => inviteForm.setData('role', value)}
                                    >
                                        <SelectTrigger>
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            <SelectItem value="admin">Admin - Can manage project and members</SelectItem>
                                            <SelectItem value="editor">Editor - Can create and edit content</SelectItem>
                                            <SelectItem value="viewer">Viewer - Can only view content</SelectItem>
                                        </SelectContent>
                                    </Select>
                                </div>
                                <div className="flex gap-2">
                                    <Button type="submit" disabled={inviteForm.processing}>
                                        Send Invitation
                                    </Button>
                                    <Button
                                        type="button"
                                        variant="outline"
                                        onClick={() => setShowInviteForm(false)}
                                    >
                                        Cancel
                                    </Button>
                                </div>
                            </form>
                        </CardContent>
                    </Card>
                )}

                <Card>
                    <CardHeader>
                        <CardTitle>Team Members ({collaborators.length + 1})</CardTitle>
                        <CardDescription>
                            Current members and their roles in this project
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Member</TableHead>
                                    <TableHead>Role</TableHead>
                                    <TableHead>Joined</TableHead>
                                    <TableHead className="text-right">Actions</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {/* Project Owner */}
                                <TableRow>
                                    <TableCell>
                                        <div>
                                            <div className="font-medium">{project.user.name}</div>
                                            <div className="text-sm text-muted-foreground">{project.user.email}</div>
                                        </div>
                                    </TableCell>
                                    <TableCell>
                                        <Badge className={roleColors.owner}>Owner</Badge>
                                    </TableCell>
                                    <TableCell>-</TableCell>
                                    <TableCell className="text-right">-</TableCell>
                                </TableRow>

                                {/* Collaborators */}
                                {collaborators.map((collaborator) => (
                                    <TableRow key={collaborator.id}>
                                        <TableCell>
                                            <div>
                                                <div className="font-medium">{collaborator.name}</div>
                                                <div className="text-sm text-muted-foreground">{collaborator.email}</div>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge className={roleColors[collaborator.pivot?.role as keyof typeof roleColors]}>
                                                {collaborator.pivot?.role}
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {collaborator.pivot?.joined_at &&
                                                new Date(collaborator.pivot.joined_at).toLocaleDateString()
                                            }
                                        </TableCell>
                                        <TableCell className="text-right">
                                            <div className="flex gap-2 justify-end">
                                                <Select
                                                    value={collaborator.pivot?.role}
                                                    onValueChange={(role) => updateRole(collaborator.id, role)}
                                                >
                                                    <SelectTrigger className="w-24 h-8">
                                                        <SelectValue />
                                                    </SelectTrigger>
                                                    <SelectContent>
                                                        <SelectItem value="admin">Admin</SelectItem>
                                                        <SelectItem value="editor">Editor</SelectItem>
                                                        <SelectItem value="viewer">Viewer</SelectItem>
                                                    </SelectContent>
                                                </Select>
                                                <Button
                                                    variant="destructive"
                                                    size="sm"
                                                    onClick={() => removeCollaborator(collaborator.id)}
                                                >
                                                    Remove
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                </Card>

                {pendingInvitations.length > 0 && (
                    <Card>
                        <CardHeader>
                            <CardTitle>Pending Invitations ({pendingInvitations.length})</CardTitle>
                            <CardDescription>
                                Invitations that haven't been accepted yet
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Role</TableHead>
                                        <TableHead>Invited By</TableHead>
                                        <TableHead>Expires</TableHead>
                                        <TableHead className="text-right">Actions</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {pendingInvitations.map((invitation) => (
                                        <TableRow key={invitation.id}>
                                            <TableCell>{invitation.email}</TableCell>
                                            <TableCell>
                                                <Badge className={roleColors[invitation.role as keyof typeof roleColors]}>
                                                    {invitation.role}
                                                </Badge>
                                            </TableCell>
                                            <TableCell>{invitation.invited_by.name}</TableCell>
                                            <TableCell>{new Date(invitation.expires_at).toLocaleDateString()}</TableCell>
                                            <TableCell className="text-right">
                                                <Button
                                                    variant="outline"
                                                    size="sm"
                                                    onClick={() => cancelInvitation(invitation.id)}
                                                >
                                                    Cancel
                                                </Button>
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>
                )}

                <div className="flex justify-between">
                    <Button variant="outline" asChild>
                        <Link href="/projects">← Back to Projects</Link>
                    </Button>
                </div>
            </div>
        </AppLayout>
    );
}
