import AppLayout from '@/layouts/app-layout';
import { posts as postsRoute } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { createColumnHelper, getCoreRowModel, getFilteredRowModel, getPaginationRowModel, useReactTable, flexRender } from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
    is_draft: boolean;
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
    const [globalFilter, setGlobalFilter] = useState('');
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
        columnHelper.accessor('is_draft', {
            header: 'Status',
            cell: info => info.getValue() ? 'Draft' : 'Published',
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
        getFilteredRowModel: getFilteredRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        globalFilterFn: 'includesString',
        onGlobalFilterChange: setGlobalFilter,
        state: {
            globalFilter,
        },
        initialState: {
            pagination: {
                pageSize: 10,
            },
        },
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
            <div className="px-4">
                <Input
                    placeholder="Search posts..."
                    value={globalFilter ?? ''}
                    onChange={(event) => setGlobalFilter(String(event.target.value))}
                    className="max-w-sm"
                />
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
                <div className="flex items-center justify-between px-2">
                    <div className="flex-1 text-sm text-muted-foreground">
                        {table.getFilteredSelectedRowModel().rows.length} of{' '}
                        {table.getFilteredRowModel().rows.length} row(s) selected.
                    </div>
                    <div className="flex items-center space-x-6 lg:space-x-8">
                        <div className="flex items-center space-x-2">
                            <p className="text-sm font-medium">Rows per page</p>
                            <select
                                value={table.getState().pagination.pageSize}
                                onChange={e => {
                                    table.setPageSize(Number(e.target.value))
                                }}
                                className="h-8 w-[70px] rounded border border-input bg-background"
                            >
                                {[10, 20, 30, 40, 50].map(pageSize => (
                                    <option key={pageSize} value={pageSize}>
                                        {pageSize}
                                    </option>
                                ))}
                            </select>
                        </div>
                        <div className="flex w-[100px] items-center justify-center text-sm font-medium">
                            Page {table.getState().pagination.pageIndex + 1} of{' '}
                            {table.getPageCount()}
                        </div>
                        <div className="flex items-center space-x-2">
                            <Button
                                variant="outline"
                                className="h-8 w-8 p-0"
                                onClick={() => table.setPageIndex(0)}
                                disabled={!table.getCanPreviousPage()}
                            >
                                <span className="sr-only">Go to first page</span>
                                {'<<'}
                            </Button>
                            <Button
                                variant="outline"
                                className="h-8 w-8 p-0"
                                onClick={() => table.previousPage()}
                                disabled={!table.getCanPreviousPage()}
                            >
                                <span className="sr-only">Go to previous page</span>
                                {'<'}
                            </Button>
                            <Button
                                variant="outline"
                                className="h-8 w-8 p-0"
                                onClick={() => table.nextPage()}
                                disabled={!table.getCanNextPage()}
                            >
                                <span className="sr-only">Go to next page</span>
                                {'>'}
                            </Button>
                            <Button
                                variant="outline"
                                className="h-8 w-8 p-0"
                                onClick={() => table.setPageIndex(table.getPageCount() - 1)}
                                disabled={!table.getCanNextPage()}
                            >
                                <span className="sr-only">Go to last page</span>
                                {'>>'}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
