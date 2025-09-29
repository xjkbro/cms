import AppLayout from '@/layouts/app-layout';
import { Head, Link, router } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import { Separator } from '@/components/ui/separator';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuTrigger
} from '@/components/ui/dropdown-menu';
import { Plus, MoreHorizontal, Edit, Trash2, CheckCircle, Settings } from 'lucide-react';

interface Project {
  id: number;
  name: string;
  description: string;
  slug: string;
  is_active: boolean;
  is_default: boolean;
  posts_count: number;
  categories_count: number;
  created_at: string;
}

interface ProjectsIndexProps {
  projects: Project[];
}

export default function ProjectsIndex({ projects }: ProjectsIndexProps) {
  const handleMakeDefault = (projectId: number) => {
    router.post(`/projects/${projectId}/make-default`);
  };

  const handleDelete = (projectId: number) => {
    if (confirm('Are you sure you want to delete this project? This action cannot be undone.')) {
      router.delete(`/projects/${projectId}`);
    }
  };

  return (
    <AppLayout
      breadcrumbs={[
        { title: 'Projects', href: '/projects' },
      ]}
    >
      <Head title="Projects" />

      <div className="flex h-full flex-1 flex-col gap-6 p-6">
        {/* Header */}
        <div className="flex items-center justify-between">
          <div>
            <h1 className="text-2xl font-bold">Projects</h1>
            <p className="text-muted-foreground">
              Manage your content projects and organize your work
            </p>
          </div>
          <Link href="/projects/create">
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              New Project
            </Button>
          </Link>
        </div>

        <Separator />

        {/* Projects Grid */}
        <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-3">
          {projects.map((project) => (
            <Card key={project.id} className="relative">
              <CardHeader className="pb-4">
                <div className="flex items-start justify-between">
                  <div className="space-y-1">
                    <CardTitle className="text-lg flex items-center gap-2">
                      {project.name}
                      {project.is_default && (
                        <Badge variant="secondary" className="text-xs">
                          <CheckCircle className="h-3 w-3 mr-1" />
                          Default
                        </Badge>
                      )}
                    </CardTitle>
                    {project.description && (
                      <CardDescription className="text-sm">
                        {project.description}
                      </CardDescription>
                    )}
                  </div>

                  <DropdownMenu>
                    <DropdownMenuTrigger asChild>
                      <Button variant="ghost" size="sm" className="h-8 w-8 p-0">
                        <MoreHorizontal className="h-4 w-4" />
                      </Button>
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end">
                      <DropdownMenuItem asChild>
                        <Link href={`/projects/${project.id}/edit`}>
                          <Edit className="h-4 w-4 mr-2" />
                          Edit
                        </Link>
                      </DropdownMenuItem>
                      <DropdownMenuItem asChild>
                        <Link href={`/projects/${project.id}/collaboration`}>
                          <Settings className="h-4 w-4 mr-2" />
                          Collaboration
                        </Link>
                      </DropdownMenuItem>
                      {!project.is_default && (
                        <DropdownMenuItem onClick={() => handleMakeDefault(project.id)}>
                          <CheckCircle className="h-4 w-4 mr-2" />
                          Make Default
                        </DropdownMenuItem>
                      )}
                      <DropdownMenuItem
                        onClick={() => handleDelete(project.id)}
                        className="text-destructive"
                      >
                        <Trash2 className="h-4 w-4 mr-2" />
                        Delete
                      </DropdownMenuItem>
                    </DropdownMenuContent>
                  </DropdownMenu>
                </div>
              </CardHeader>

              <CardContent className="pt-0">
                <div className="space-y-4">
                  {/* Stats */}
                  <div className="grid grid-cols-2 gap-4 text-sm">
                    <div>
                      <div className="font-medium">{project.posts_count}</div>
                      <div className="text-muted-foreground">Posts</div>
                    </div>
                    <div>
                      <div className="font-medium">{project.categories_count}</div>
                      <div className="text-muted-foreground">Categories</div>
                    </div>
                  </div>

                  {/* Actions */}
                  <div className="flex gap-2">
                    <Button
                      variant="outline"
                      size="sm"
                      className="flex-1"
                      asChild
                    >
                      <Link href="/dashboard" onClick={() => {
                        // Switch to this project
                        router.post('/projects/switch', { project_id: project.id });
                      }}>
                        <Settings className="h-4 w-4 mr-2" />
                        Switch To
                      </Link>
                    </Button>
                  </div>

                  <div className="text-xs text-muted-foreground">
                    Created {new Date(project.created_at).toLocaleDateString()}
                  </div>
                </div>
              </CardContent>
            </Card>
          ))}
        </div>

        {projects.length === 0 && (
          <div className="text-center py-12">
            <div className="text-muted-foreground mb-4">
              <Settings className="h-12 w-12 mx-auto mb-4 opacity-50" />
              <h3 className="text-lg font-medium mb-2">No projects yet</h3>
              <p>Create your first project to get started organizing your content.</p>
            </div>
            <Link href="/projects/create">
              <Button>
                <Plus className="h-4 w-4 mr-2" />
                Create Your First Project
              </Button>
            </Link>
          </div>
        )}
      </div>
    </AppLayout>
  );
}
