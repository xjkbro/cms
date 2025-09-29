import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Switch } from '@/components/ui/switch';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface Project {
  id: number;
  name: string;
  description: string;
  is_active: boolean;
}

interface ProjectFormData {
  name: string;
  description: string;
  is_active: boolean;
}

interface ProjectsEditProps {
  project: Project;
}

export default function ProjectsEdit({ project }: ProjectsEditProps) {
  const { data, setData, put, processing, errors } = useForm<ProjectFormData>({
    name: project.name,
    description: project.description || '',
    is_active: project.is_active,
  });

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault();
    put(`/projects/${project.id}`);
  };

  return (
    <AppLayout
      breadcrumbs={[
        { title: 'Projects', href: '/projects' },
        { title: 'Edit', href: `/projects/${project.id}/edit` },
      ]}
    >
      <Head title={`Edit ${project.name}`} />
      
      <div className="flex h-full flex-1 flex-col gap-6 p-6">
        <div>
          <h1 className="text-2xl font-bold">Edit Project</h1>
          <p className="text-muted-foreground">
            Update your project information and settings
          </p>
        </div>

        <Card className="max-w-2xl">
          <CardHeader>
            <CardTitle>Project Information</CardTitle>
          </CardHeader>
          <CardContent>
            <form onSubmit={handleSubmit} className="space-y-6">
              <div className="space-y-2">
                <Label htmlFor="name">Project Name</Label>
                <Input
                  id="name"
                  value={data.name}
                  onChange={(e) => setData('name', e.target.value)}
                  placeholder="Enter project name"
                  required
                />
                {errors.name && (
                  <div className="text-sm text-destructive">{errors.name}</div>
                )}
              </div>

              <div className="space-y-2">
                <Label htmlFor="description">Description</Label>
                <Textarea
                  id="description"
                  value={data.description}
                  onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => setData('description', e.target.value)}
                  placeholder="Enter project description (optional)"
                  rows={4}
                />
                {errors.description && (
                  <div className="text-sm text-destructive">{errors.description}</div>
                )}
              </div>

              <div className="flex items-center justify-between">
                <div className="space-y-1">
                  <Label htmlFor="is_active">Active Project</Label>
                  <p className="text-sm text-muted-foreground">
                    Inactive projects are hidden from the project switcher
                  </p>
                </div>
                <Switch
                  id="is_active"
                  checked={data.is_active}
                  onCheckedChange={(checked) => setData('is_active', checked)}
                />
              </div>
              {errors.is_active && (
                <div className="text-sm text-destructive">{errors.is_active}</div>
              )}

              <div className="flex gap-4">
                <Button type="submit" disabled={processing}>
                  {processing ? 'Updating...' : 'Update Project'}
                </Button>
                <Button 
                  type="button" 
                  variant="outline" 
                  onClick={() => window.history.back()}
                >
                  Cancel
                </Button>
              </div>
            </form>
          </CardContent>
        </Card>
      </div>
    </AppLayout>
  );
}
