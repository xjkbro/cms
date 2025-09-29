import AppLayout from '@/layouts/app-layout';
import { dashboard } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/react';
import { StatCard } from '@/components/StatCard';
import { PostsChart } from '@/components/PostsChart';
import { QuickActions } from '@/components/QuickActions';
import { FileText, FilePenLine, FolderOpen, TrendingUp } from 'lucide-react';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboard().url,
    },
];

interface DashboardProps {
    stats: {
        published_posts: number;
        draft_posts: number;
        total_posts: number;
        categories: number;
    };
    posts_over_time: Array<{
        date: string;
        count: number;
        formatted_date: string;
    }>;
    current_timeframe: string;
}

export default function Dashboard({ stats, posts_over_time, current_timeframe }: DashboardProps) {
    const handleTimeframeChange = (timeframe: string) => {
        router.get(dashboard().url, { timeframe }, {
            preserveState: true,
            preserveScroll: true,
        });
    };

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-6 p-6">
                {/* Statistics Cards */}
                <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                    <StatCard
                        title="Published Posts"
                        value={stats.published_posts}
                        icon={FileText}
                        description="Live posts visible to readers"
                    />
                    <StatCard
                        title="Draft Posts"
                        value={stats.draft_posts}
                        icon={FilePenLine}
                        description="Posts in progress"
                    />
                    <StatCard
                        title="Total Posts"
                        value={stats.total_posts}
                        icon={TrendingUp}
                        description="All posts created"
                    />
                    <StatCard
                        title="Categories"
                        value={stats.categories}
                        icon={FolderOpen}
                        description="Content categories"
                    />
                </div>

                {/* Charts and Actions Grid */}
                <div className="grid gap-6 lg:grid-cols-4">
                    {/* Posts Over Time Chart */}
                    <div className="lg:col-span-3">
                        <PostsChart
                            data={posts_over_time}
                            currentTimeframe={current_timeframe}
                            onTimeframeChange={handleTimeframeChange}
                        />
                    </div>
                    
                    {/* Quick Actions */}
                    <div className="lg:col-span-1">
                        <QuickActions stats={stats} />
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
