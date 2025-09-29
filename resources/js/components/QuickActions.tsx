import React from 'react';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Link } from '@inertiajs/react';
import { Plus, Edit, Eye, TrendingUp } from 'lucide-react';

interface QuickActionsProps {
  stats: {
    published_posts: number;
    draft_posts: number;
    total_posts: number;
    categories: number;
  };
}

export function QuickActions({ stats }: QuickActionsProps) {
  return (
    <Card>
      <CardHeader>
        <CardTitle className="flex items-center gap-2">
          <TrendingUp className="h-5 w-5" />
          Quick Actions
        </CardTitle>
      </CardHeader>
      <CardContent className="space-y-3">
        <Link href="/posts/create" className="block">
          <Button className="w-full justify-start" size="sm">
            <Plus className="h-4 w-4 mr-2" />
            Create New Post
          </Button>
        </Link>
        
        <Link href="/posts" className="block">
          <Button variant="outline" className="w-full justify-start" size="sm">
            <Edit className="h-4 w-4 mr-2" />
            Manage Posts ({stats.total_posts})
          </Button>
        </Link>
        
        <Link href="/categories" className="block">
          <Button variant="outline" className="w-full justify-start" size="sm">
            <Eye className="h-4 w-4 mr-2" />
            Manage Categories ({stats.categories})
          </Button>
        </Link>
        
        {stats.draft_posts > 0 && (
          <div className="pt-2 border-t">
            <p className="text-sm text-muted-foreground mb-2">
              You have {stats.draft_posts} draft{stats.draft_posts !== 1 ? 's' : ''} ready to publish
            </p>
            <Link href="/posts?filter=drafts" className="block">
              <Button variant="secondary" className="w-full" size="sm">
                Review Drafts
              </Button>
            </Link>
          </div>
        )}
      </CardContent>
    </Card>
  );
}
