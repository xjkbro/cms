import React from 'react';
import { router } from '@inertiajs/react';
import {
  DropdownMenu,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
  DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { ChevronDown, Plus, Settings, CheckCircle } from 'lucide-react';

interface Project {
  id: number;
  name: string;
  description?: string;
  is_default: boolean;
  is_owner?: boolean;
  user_role?: string;
}

interface ProjectSwitcherProps {
  currentProject: Project;
  projects: Project[];
}

export function ProjectSwitcher({ currentProject, projects }: ProjectSwitcherProps) {
  const handleProjectSwitch = (projectId: number) => {
    router.post('/projects/switch', { project_id: projectId }, {
      preserveState: false,
      onSuccess: () => {
        // Refresh the page to update all project-specific data
        window.location.reload();
      },
    });
  };

  const handleManageProjects = () => {
    router.get('/projects');
  };

  return (
    <DropdownMenu>
      <DropdownMenuTrigger asChild>
        <Button variant="outline" className="w-full justify-between">
          <div className="flex items-center gap-2 min-w-0">
            <span className="truncate">{currentProject.name}</span>
            {currentProject.is_default && (
              <Badge variant="secondary" className="text-xs h-4">
                <CheckCircle className="h-2 w-2 mr-1" />
                Default
              </Badge>
            )}
            {!currentProject.is_owner && currentProject.user_role && (
              <Badge variant="outline" className="text-xs h-4">
                {currentProject.user_role}
              </Badge>
            )}
          </div>
          <ChevronDown className="h-4 w-4 opacity-50" />
        </Button>
      </DropdownMenuTrigger>
      <DropdownMenuContent className="w-64" align="start">
        <div className="px-2 py-1.5 text-sm text-muted-foreground">
          Switch Project
        </div>
        <DropdownMenuSeparator />

        {projects.map((project) => (
          <DropdownMenuItem
            key={project.id}
            onClick={() => handleProjectSwitch(project.id)}
            className={`flex items-center justify-between ${
              project.id === currentProject.id ? 'bg-accent' : ''
            }`}
          >
            <div className="flex items-center gap-2 min-w-0">
              <span className="truncate">{project.name}</span>
              {project.is_default && (
                <Badge variant="secondary" className="text-xs h-4">
                  Default
                </Badge>
              )}
              {!project.is_owner && project.user_role && (
                <Badge variant="outline" className="text-xs h-4">
                  {project.user_role}
                </Badge>
              )}
            </div>
            {project.id === currentProject.id && (
              <CheckCircle className="h-4 w-4 text-primary" />
            )}
          </DropdownMenuItem>
        ))}

        <DropdownMenuSeparator />
        <DropdownMenuItem onClick={handleManageProjects}>
          <Settings className="h-4 w-4 mr-2" />
          Manage Projects
        </DropdownMenuItem>
        <DropdownMenuItem onClick={() => router.get('/projects/create')}>
          <Plus className="h-4 w-4 mr-2" />
          Create New Project
        </DropdownMenuItem>
      </DropdownMenuContent>
    </DropdownMenu>
  );
}
