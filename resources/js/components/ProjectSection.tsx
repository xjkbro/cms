import React, { useState } from 'react';
import { router } from '@inertiajs/react';
import {
  SidebarMenu,
  SidebarMenuButton,
  SidebarMenuItem,
  SidebarMenuSub,
  SidebarMenuSubButton,
  SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import {
  Collapsible,
  CollapsibleContent,
  CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { Dialog, DialogContent, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { Badge } from '@/components/ui/badge';
import { ChevronRight, FolderOpen, Plus, Settings, CheckCircle } from 'lucide-react';

interface Project {
  id: number;
  name: string;
  description?: string;
  is_default: boolean;
}

interface ProjectSectionProps {
  currentProject: Project;
  projects: Project[];
}

export function ProjectSection({ currentProject, projects }: ProjectSectionProps) {
  const [isOpen, setIsOpen] = useState(true);
  const [showCreateModal, setShowCreateModal] = useState(false);
  const [isCreating, setIsCreating] = useState(false);
  const [formData, setFormData] = useState({
    name: '',
    description: '',
  });

  const handleProjectSwitch = (projectId: number) => {
    router.post('/projects/switch', { project_id: projectId }, {
      preserveState: false,
    });
  };

  const handleCreateProject = async (e: React.FormEvent) => {
    e.preventDefault();
    setIsCreating(true);
    
    router.post('/projects', {
      ...formData,
      switch_to_project: true, // Switch to the new project after creation
    }, {
      onSuccess: () => {
        setShowCreateModal(false);
        setFormData({ name: '', description: '' });
      },
      onFinish: () => {
        setIsCreating(false);
      },
    });
  };

  return (
    <>
      <SidebarMenu>
        <Collapsible open={isOpen} onOpenChange={setIsOpen} className="group/collapsible">
          <SidebarMenuItem>
            <CollapsibleTrigger asChild>
              <SidebarMenuButton tooltip="Projects">
                <FolderOpen className="h-4 w-4" />
                <span>Projects</span>
                <ChevronRight className="ml-auto transition-transform duration-200 group-data-[state=open]/collapsible:rotate-90" />
              </SidebarMenuButton>
            </CollapsibleTrigger>
            <CollapsibleContent>
              <SidebarMenuSub>
                {projects.map((project) => (
                  <SidebarMenuSubItem key={project.id}>
                    <SidebarMenuSubButton 
                      onClick={() => handleProjectSwitch(project.id)}
                      isActive={project.id === currentProject.id}
                      className="flex items-center justify-between"
                    >
                      <div className="flex items-center gap-2 min-w-0">
                        <span className="truncate">{project.name}</span>
                        {project.is_default && (
                          <Badge variant="secondary" className="text-xs h-4 px-1">
                            Default
                          </Badge>
                        )}
                      </div>
                      {project.id === currentProject.id && (
                        <CheckCircle className="h-3 w-3 text-primary flex-shrink-0" />
                      )}
                    </SidebarMenuSubButton>
                  </SidebarMenuSubItem>
                ))}
                
                <SidebarMenuSubItem>
                  <SidebarMenuSubButton onClick={() => setShowCreateModal(true)}>
                    <Plus className="h-4 w-4" />
                    <span>New Project</span>
                  </SidebarMenuSubButton>
                </SidebarMenuSubItem>
                
                <SidebarMenuSubItem>
                  <SidebarMenuSubButton onClick={() => router.get('/projects')}>
                    <Settings className="h-4 w-4" />
                    <span>Manage Projects</span>
                  </SidebarMenuSubButton>
                </SidebarMenuSubItem>
              </SidebarMenuSub>
            </CollapsibleContent>
          </SidebarMenuItem>
        </Collapsible>
      </SidebarMenu>

      {/* Create Project Modal */}
      <Dialog open={showCreateModal} onOpenChange={setShowCreateModal}>
        <DialogContent className="sm:max-w-[425px]">
          <DialogHeader>
            <DialogTitle>Create New Project</DialogTitle>
          </DialogHeader>
          <form onSubmit={handleCreateProject} className="space-y-4">
            <div className="space-y-2">
              <Label htmlFor="project-name">Project Name</Label>
              <Input
                id="project-name"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                placeholder="Enter project name"
                required
              />
            </div>
            
            <div className="space-y-2">
              <Label htmlFor="project-description">Description (Optional)</Label>
              <Textarea
                id="project-description"
                value={formData.description}
                onChange={(e: React.ChangeEvent<HTMLTextAreaElement>) => 
                  setFormData({ ...formData, description: e.target.value })
                }
                placeholder="Enter project description"
                rows={3}
              />
            </div>
            
            <div className="flex justify-end gap-2">
              <Button 
                type="button" 
                variant="outline" 
                onClick={() => setShowCreateModal(false)}
                disabled={isCreating}
              >
                Cancel
              </Button>
              <Button type="submit" disabled={isCreating}>
                {isCreating ? 'Creating...' : 'Create Project'}
              </Button>
            </div>
          </form>
        </DialogContent>
      </Dialog>
    </>
  );
}
