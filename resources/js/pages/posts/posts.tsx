import AppLayout from '@/layouts/app-layout';
import { posts as postsRoute } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useMemo } from 'react';
import { createColumnHelper, getCoreRowModel, useReactTable, flexRender } from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Posts',
        href: postsRoute().url,
    },
];

type Post = {
    id: number;
    title: string;
    slug: string;
    category?: Category | null;
    // eslint-disable-next-line @typescript-eslint/no-explicit-any
    user: any;
    content: string;
    excerpt: string | null;
    tags: string | null;
    created_at: string;
    updated_at: string;
};

type Category = {
    // Add category fields if needed
    id: number;
    user_id: number;
    name: string;
    slug: string;
    description: string;
    created_at: string;
    updated_at: string;
};

interface PostsProps {
    posts: Post[];
    categories: Category[];
}

export default function Posts({posts}: PostsProps) {
    const columnHelper = createColumnHelper<Post>();

    const columns = useMemo(() => [
        columnHelper.accessor('title', {
            header: 'Title',
            cell: info => info.getValue(),
        }),
        columnHelper.accessor(row => row.category?.name ?? 'Uncategorized', {
            id: 'category',
            header: 'Category',
            cell: info => info.getValue(),
        }),
        columnHelper.accessor(row => row.user?.name ?? '', {
            id: 'author',
            header: 'Author',
            cell: info => info.getValue(),
        }),
        columnHelper.accessor('excerpt', {
            header: 'Excerpt',
            cell: info => info.getValue(),
        }),
        columnHelper.accessor(row => row.id, {
            id: 'actions',
            header: 'Actions',
            cell: info => {
                const post = info.row.original;
                return (
                    <div className="flex gap-2">
                        <Button variant="outline" size="sm" asChild>
                            <Link href={`/posts/${post.id}/edit`}>Edit</Link>
                        </Button>
                        <Button variant="destructive" size="sm" asChild>
                            <Link
                                href={`/posts/${post.id}`}
                                method="delete"
                                as="button"
                                onClick={(e) => {
                                    if (!confirm('Are you sure you want to delete this post?')) {
                                        e.preventDefault();
                                    }
                                }}
                            >
                                Delete
                            </Link>
                        </Button>
                    </div>
                );
            },
        }),
    ], [columnHelper]);

    const table = useReactTable({
        data: posts,
        columns,
        getCoreRowModel: getCoreRowModel(),
    });

    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Posts" />
            <div className="flex items-center justify-between p-4">
                <h1 className="text-2xl font-bold">Posts</h1>
                <Button asChild>
                    <Link href="/posts/create">Add Post</Link>
                </Button>
            </div>
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Table>
                    <TableHeader>
                        {table.getHeaderGroups().map(headerGroup => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map(header => (
                                    <TableHead key={header.id}>
                                        {header.isPlaceholder ? null : flexRender(header.column.columnDef.header, header.getContext())}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {table.getRowModel().rows.map(row => (
                            <TableRow key={row.id}>
                                {row.getVisibleCells().map(cell => (
                                    <TableCell key={cell.id}>
                                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </AppLayout>
    );
}
