import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';

interface CategoryFormData {
    name: string;
    description: string;
}

interface Category {
    id: number;
    name: string;
    description: string;
    slug: string;
}

interface CategoryEditProps {
    category?: Category | null;
}

export default function CategoryEdit({ category = null }: CategoryEditProps) {
    const { data, setData, post: submit, put, processing, errors } = useForm<CategoryFormData>({
        name: category?.name ?? '',
        description: category?.description ?? '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (category) {
            put(`/categories/${category.id}`);
        } else {
            submit('/categories');
        }
    }

    return (
        <AppLayout>
            <Head title={category ? 'Edit Category' : 'Add Category'} />
            <div className="container mx-auto p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{category ? 'Edit Category' : 'Add Category'}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    value={data.name}
                                    onChange={e => setData('name', e.target.value)}
                                    placeholder="Enter category name"
                                />
                                {errors.name && <div className="text-sm text-red-600">{errors.name}</div>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="description">Description</Label>
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={e => setData('description', e.target.value)}
                                    placeholder="Enter category description (optional)"
                                    className="min-h-[100px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                {errors.description && <div className="text-sm text-red-600">{errors.description}</div>}
                            </div>

                            <div className="flex gap-4">
                                <Button type="submit" disabled={processing}>
                                    {category ? 'Update Category' : 'Create Category'}
                                </Button>
                                <Button type="button" variant="outline" asChild>
                                    <a href="/categories">Cancel</a>
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
