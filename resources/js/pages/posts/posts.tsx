import AppLayout from '@/layouts/app-layout';
import { posts as postsRoute } from '@/routes';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { createColumnHelper, getCoreRowModel, getFilteredRowModel, getPaginationRowModel, getSortedRowModel, useReactTable, flexRender, type SortingState } from '@tanstack/react-table';
import { Link } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Posts',
        href: postsRoute().url,
    },
];

type User = {
    id: number;
    name: string;
    email: string;
};

type Post = {
    id: number;
    title: string;
    slug: string;
    category?: Category | null;
    user: User;
    authors?: User[];
    content: string;
    excerpt: string | null;
    tags: Tag[];
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

type Tag = {
    id: number;
    name: string;
    slug: string;
};

interface PostsProps {
    posts: Post[];
    categories: Category[];
    tags: Tag[];
}

export default function Posts({posts, tags}: PostsProps) {
    const [globalFilter, setGlobalFilter] = useState('');
    const [tagFilter, setTagFilter] = useState<string>('');
    const [sorting, setSorting] = useState<SortingState>([]);
    const columnHelper = createColumnHelper<Post>();

    const columns = useMemo(() => [
        columnHelper.accessor('title', {
            header: 'Title',
            cell: info => info.getValue(),
            enableSorting: true,
        }),
        columnHelper.accessor(row => row.category?.name ?? 'Uncategorized', {
            id: 'category',
            header: 'Category',
            cell: info => info.getValue(),
            enableSorting: true,
        }),
        columnHelper.accessor(row => row.user?.name ?? '', {
            id: 'author',
            header: 'Author(s)',
            cell: info => {
                const post = info.row.original;
                const primaryAuthor = post.user?.name;
                const coAuthors = post.authors?.filter(author => author.id !== post.user?.id);

                if (coAuthors && coAuthors.length > 0) {
                    const coAuthorNames = coAuthors.map(author => author.name).join(', ');
                    return (
                        <div>
                            <div className="font-medium">{primaryAuthor}</div>
                            <div className="text-xs text-muted-foreground">+ {coAuthorNames}</div>
                        </div>
                    );
                }

                return primaryAuthor;
            },
            enableSorting: true,
        }),
        columnHelper.accessor('excerpt', {
            header: 'Excerpt',
            cell: info => info.getValue(),
        }),
        columnHelper.accessor(row => row.tags.map(tag => tag.name).join(', '), {
            id: 'tags',
            header: 'Tags',
            cell: info => info.getValue(),
        }),
        columnHelper.accessor('is_draft', {
            header: 'Status',
            cell: info => info.getValue() ? 'Draft' : 'Published',
            enableSorting: true,
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

    // Apply tag filter
    const filteredData = useMemo(() => {
        if (!tagFilter) return posts;
        return posts.filter(post => post.tags.some(tag => tag.name === tagFilter));
    }, [posts, tagFilter]);

    const filteredTable = useReactTable({
        data: filteredData,
        columns,
        getCoreRowModel: getCoreRowModel(),
        getFilteredRowModel: getFilteredRowModel(),
        getPaginationRowModel: getPaginationRowModel(),
        getSortedRowModel: getSortedRowModel(),
        globalFilterFn: (row, columnId, filterValue) => {
            if (!filterValue) return true;
            const value = row.getValue(columnId);
            return String(value).toLowerCase().includes(filterValue.toLowerCase());
        },
        onGlobalFilterChange: setGlobalFilter,
        onSortingChange: setSorting,
        state: {
            globalFilter,
            sorting,
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
                <div className="flex gap-4">
                    <Input
                        placeholder="Search posts..."
                        value={globalFilter ?? ''}
                        onChange={(event) => setGlobalFilter(String(event.target.value))}
                        className="max-w-sm"
                    />
                    <Select value={tagFilter} onValueChange={setTagFilter}>
                        <SelectTrigger className="w-[180px]">
                            <SelectValue placeholder="Filter by tag" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem disabled value="all">All tags</SelectItem>
                            {tags.map((tag) => (
                                <SelectItem key={tag.id} value={tag.name}>
                                    {tag.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>
            </div>
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                <Table>
                    <TableHeader>
                        {filteredTable.getHeaderGroups().map(headerGroup => (
                            <TableRow key={headerGroup.id}>
                                {headerGroup.headers.map(header => (
                                    <TableHead key={header.id}>
                                        {header.isPlaceholder ? null : (
                                            <div
                                                className={header.column.getCanSort() ? 'cursor-pointer select-none flex items-center gap-2' : ''}
                                                onClick={header.column.getToggleSortingHandler()}
                                            >
                                                {flexRender(header.column.columnDef.header, header.getContext())}
                                                {{
                                                    asc: '↑',
                                                    desc: '↓',
                                                }[header.column.getIsSorted() as string] ?? null}
                                            </div>
                                        )}
                                    </TableHead>
                                ))}
                            </TableRow>
                        ))}
                    </TableHeader>
                    <TableBody>
                        {filteredTable.getRowModel().rows.length === 0 ? (
                            <TableRow>
                                <TableCell colSpan={filteredTable.getAllColumns().length} className="text-center text-muted-foreground">
                                    No posts found.
                                </TableCell>
                            </TableRow>
                        ) : (
                            filteredTable.getRowModel().rows.map(row => (
                            <TableRow key={row.id}>
                                {row.getVisibleCells().map(cell => (
                                    <TableCell key={cell.id}>
                                        {flexRender(cell.column.columnDef.cell, cell.getContext())}
                                    </TableCell>
                                ))}
                            </TableRow>
                        ))
                        )}
                    </TableBody>
                </Table>
                <div className="flex items-center justify-between px-2">
                    <div className="flex-1 text-sm text-muted-foreground">
                        {filteredTable.getFilteredSelectedRowModel().rows.length} of{' '}
                        {filteredTable.getFilteredRowModel().rows.length} row(s) selected.
                    </div>
                    <div className="flex items-center space-x-6 lg:space-x-8">
                        <div className="flex items-center space-x-2">
                            <p className="text-sm font-medium">Rows per page</p>
                            <select
                                value={filteredTable.getState().pagination.pageSize}
                                onChange={e => {
                                    filteredTable.setPageSize(Number(e.target.value))
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
                            Page {filteredTable.getState().pagination.pageIndex + 1} of{' '}
                            {filteredTable.getPageCount()}
                        </div>
                        <div className="flex items-center space-x-2">
                            <Button
                                variant="outline"
                                className="h-8 w-8 p-0"
                                onClick={() => filteredTable.setPageIndex(0)}
                                disabled={!filteredTable.getCanPreviousPage()}
                            >
                                <span className="sr-only">Go to first page</span>
                                {'<<'}
                            </Button>
                            <Button
                                variant="outline"
                                className="h-8 w-8 p-0"
                                onClick={() => filteredTable.previousPage()}
                                disabled={!filteredTable.getCanPreviousPage()}
                            >
                                <span className="sr-only">Go to previous page</span>
                                {'<'}
                            </Button>
                            <Button
                                variant="outline"
                                className="h-8 w-8 p-0"
                                onClick={() => filteredTable.nextPage()}
                                disabled={!filteredTable.getCanNextPage()}
                            >
                                <span className="sr-only">Go to next page</span>
                                {'>'}
                            </Button>
                            <Button
                                variant="outline"
                                className="h-8 w-8 p-0"
                                onClick={() => filteredTable.setPageIndex(filteredTable.getPageCount() - 1)}
                                disabled={!filteredTable.getCanNextPage()}
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
