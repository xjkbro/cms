import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { ProjectSection } from '@/components/ProjectSection';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { categories, dashboard, posts } from '@/routes';
import { type NavItem } from '@/types';
import { Link, usePage } from '@inertiajs/react';
import { BookOpen, Folder, LayoutGrid, Image, FileText, BookMarked } from 'lucide-react';
import AppLogo from './app-logo';

interface Project {
    id: number;
    name: string;
    is_default: boolean;
}

interface PageProps extends Record<string, unknown> {
    currentProject?: Project;
    userProjects?: Project[];
}

const mainNavItems: NavItem[] = [
    {
        title: 'Dashboard',
        href: dashboard(),
        icon: LayoutGrid,
    },
    {
        title: 'Posts',
        href: posts(),
        icon: FileText ,
    },
    {
        title: 'Categories',
        href: categories(),
        icon: BookMarked,
    },
    {
        title: 'Media',
        href: '/media',
        icon: Image,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/xjkbro/cms',
        icon: Folder,
    },
    {
        title: 'Documentation',
        href: '/api/documentation',
        icon: BookOpen,
    },
];

export function AppSidebar() {
    const { props } = usePage<PageProps>();
    const { currentProject, userProjects } = props;

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboard()} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                {currentProject && userProjects && (
                    <ProjectSection
                        currentProject={currentProject}
                        projects={userProjects}
                    />
                )}
                <NavMain items={mainNavItems} />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
