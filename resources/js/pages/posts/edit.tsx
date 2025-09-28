import AppLayout from '@/layouts/app-layout';
import { Head, useForm } from '@inertiajs/react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { useEditor, EditorContent } from '@tiptap/react';
import StarterKit from '@tiptap/starter-kit';
import Image from '@tiptap/extension-image';
import Link from '@tiptap/extension-link';

import MarkdownIt from 'markdown-it';
import TurndownService from 'turndown';
// ...existing code...

interface PostFormData {
    title: string;
    content: string;
    category_id: string | number;
    excerpt: string;
    tags: string;
    is_draft?: boolean;
}

interface Post {
    id: number;
    title: string;
    content: string;
    category_id: number;
    excerpt: string;
    tags: string;
}

interface Category {
    id: number;
    name: string;
}

interface PostEditProps {
    post?: Post | null;
    categories?: Category[];
}

export default function PostEdit({ post = null, categories = [] }: PostEditProps) {
    const { data, setData, post: submit, put, processing, errors } = useForm<PostFormData>({
        title: post?.title ?? '',
        content: post?.content ?? '',
        category_id: post?.category_id ?? '',
        excerpt: post?.excerpt ?? '',
        tags: post?.tags ?? '',
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        is_draft: post ? Boolean((post as any).is_draft) : false,
    });

    // Add custom styles for the TipTap editor with shadcn theming
    const editorStyles = `
        .tiptap-editor {
            color: hsl(var(--foreground));
            background-color: hsl(var(--background));
        }
        .ProseMirror {
            outline: none;
            padding: 0;
            color: inherit;
            font-size: 14px;
            line-height: 1.6;
        }
        .ProseMirror h1 {
            font-size: 2rem;
            font-weight: 700;
            margin: 1.5rem 0 0.75rem 0;
            color: hsl(var(--foreground));
            line-height: 1.2;
        }
        .ProseMirror h2 {
            font-size: 1.5rem;
            font-weight: 600;
            margin: 1.25rem 0 0.5rem 0;
            color: hsl(var(--foreground));
            line-height: 1.3;
        }
        .ProseMirror h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin: 1rem 0 0.5rem 0;
            color: hsl(var(--foreground));
            line-height: 1.4;
        }
        .ProseMirror p {
            margin: 0.75rem 0;
        }
        .ProseMirror p:first-child {
            margin-top: 0;
        }
        .ProseMirror p:last-child {
            margin-bottom: 0;
        }
        .ProseMirror strong {
            font-weight: 600;
            color: hsl(var(--foreground));
        }
        .ProseMirror em {
            font-style: italic;
        }
        .ProseMirror ul, .ProseMirror ol {
            padding-left: 1.5rem;
            margin: 0.75rem 0;
        }
        .ProseMirror li {
            margin: 0.25rem 0;
        }
        .ProseMirror img {
            max-width: 100%;
            height: auto;
            border-radius: calc(var(--radius) - 2px);
            border: 1px solid hsl(var(--border));
        }
        .ProseMirror blockquote {
            border-left: 4px solid hsl(var(--border));
            padding-left: 1rem;
            margin: 1rem 0;
            font-style: italic;
            color: hsl(var(--muted-foreground));
        }
        .ProseMirror code {
            background-color: hsl(var(--muted));
            color: hsl(var(--muted-foreground));
            padding: 0.125rem 0.25rem;
            border-radius: calc(var(--radius) - 2px);
            font-family: ui-monospace, SFMono-Regular, "SF Mono", Consolas, "Liberation Mono", Menlo, monospace;
            font-size: 0.875em;
        }
        .ProseMirror pre {
            background-color: hsl(var(--muted));
            color: hsl(var(--muted-foreground));
            padding: 1rem;
            border-radius: var(--radius);
            margin: 1rem 0;
            overflow-x: auto;
        }
        .ProseMirror pre code {
            background: none;
            padding: 0;
            font-size: inherit;
        }
        .ProseMirror a {
            color: hsl(var(--primary));
            text-decoration: underline;
            text-underline-offset: 2px;
        }
        .ProseMirror a:hover {
            text-decoration: none;
        }
        .ProseMirror hr {
            border: none;
            border-top: 1px solid hsl(var(--border));
            margin: 2rem 0;
        }
        .ProseMirror:focus {
            outline: none;
        }
        .ProseMirror > *:first-child {
            margin-top: 0;
        }
        .ProseMirror > *:last-child {
            margin-bottom: 0;
        }
        /* Placeholder styling */
        .ProseMirror p.is-editor-empty:first-child::before {
            content: attr(data-placeholder);
            float: left;
            color: hsl(var(--muted-foreground));
            pointer-events: none;
            height: 0;
        }
    `;

    const md = new MarkdownIt();
    const turndown = new TurndownService();

    const editor = useEditor({
        extensions: [
            StarterKit,
            Image,
            Link.configure({
                openOnClick: false,
                HTMLAttributes: {
                    class: 'text-primary hover:underline',
                },
            }),
        ],
        content: data.content ? md.render(data.content) : '<p></p>',
        editorProps: {
            attributes: {
                class: 'tiptap-editor focus:outline-none',
                'data-placeholder': 'Start writing your post...',
            },
        },
        onUpdate: ({ editor }) => {
            // convert editor HTML to markdown on change and update form data
            const html = editor.getHTML();
            const markdown = turndown.turndown(html);
            setData('content', markdown);
        }
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        if (post) {
            put(`/posts/${post.id}`);
        } else {
            submit('/posts');
        }
    }

    return (
        <AppLayout>
            <Head title={post ? 'Edit Post' : 'Add Post'} />
            <style dangerouslySetInnerHTML={{ __html: editorStyles }} />
            <div className="container mx-auto p-6">
                <Card>
                    <CardHeader>
                        <CardTitle>{post ? 'Edit Post' : 'Add Post'}</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <form onSubmit={handleSubmit} className="space-y-6">
                            <div className="space-y-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={e => setData('title', e.target.value)}
                                />
                                {errors.title && <div className="text-sm text-red-600">{errors.title}</div>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="category">Category</Label>
                                <Select value={String(data.category_id)} onValueChange={value => setData('category_id', value)}>
                                    <SelectTrigger>
                                        <SelectValue placeholder="Select category" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map(c => (
                                            <SelectItem value={c.id.toString()} key={c.id}>{c.name}</SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="excerpt">Excerpt</Label>
                                <Input
                                    id="excerpt"
                                    value={data.excerpt}
                                    onChange={e => setData('excerpt', e.target.value)}
                                />
                            </div>

                            <div className="space-y-2">
                                <div className="flex items-center justify-between">
                                    <Label htmlFor="content">Content</Label>
                                    <div className="flex items-center gap-4">
                                        <label className="flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                checked={Boolean(data.is_draft)}
                                                onChange={e => setData('is_draft', e.target.checked)}
                                            />
                                            <span className="text-sm">Draft</span>
                                        </label>
                                    </div>
                                </div>
                                <div className="space-y-0">
                                    {/* Rich Text Toolbar */}
                                    <div className="flex items-center gap-1 flex-wrap p-3 bg-background border border-border rounded-t-md">
                                        {/* Undo/Redo */}
                                        <Button 
                                            type="button" 
                                            size="sm" 
                                            variant="ghost" 
                                            className="h-8 w-8 p-0" 
                                            disabled={!editor?.can().undo()}
                                            onClick={() => editor?.chain().focus().undo().run()}
                                            title="Undo (Ctrl+Z)"
                                        >
                                            ↶
                                        </Button>
                                        <Button 
                                            type="button" 
                                            size="sm" 
                                            variant="ghost" 
                                            className="h-8 w-8 p-0" 
                                            disabled={!editor?.can().redo()}
                                            onClick={() => editor?.chain().focus().redo().run()}
                                            title="Redo (Ctrl+Y)"
                                        >
                                            ↷
                                        </Button>                                        <div className="w-px h-6 bg-border mx-2" />

                                        {/* Headings */}
                                        <select
                                            className="text-sm border border-input rounded-md px-3 py-1 bg-background text-foreground hover:bg-accent hover:text-accent-foreground focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2"
                                            value={
                                                editor?.isActive('heading', { level: 1 }) ? '1' :
                                                editor?.isActive('heading', { level: 2 }) ? '2' :
                                                editor?.isActive('heading', { level: 3 }) ? '3' : '0'
                                            }
                                            onChange={(e) => {
                                                const level = parseInt(e.target.value);
                                                if (level === 0) {
                                                    editor?.chain().focus().setParagraph().run();
                                                } else {
                                                    editor?.chain().focus().toggleHeading({ level: level as 1 | 2 | 3 }).run();
                                                }
                                            }}
                                        >
                                            <option value="0">Normal</option>
                                            <option value="1">Heading 1</option>
                                            <option value="2">Heading 2</option>
                                            <option value="3">Heading 3</option>
                                        </select>

                                        <div className="w-px h-6 bg-border mx-2" />

                                        {/* Lists */}
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={editor?.isActive('bulletList') ? 'default' : 'ghost'}
                                            className="h-8 w-8 p-0"
                                            onClick={() => editor?.chain().focus().toggleBulletList().run()}
                                        >
                                            ☰
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={editor?.isActive('orderedList') ? 'default' : 'ghost'}
                                            className="h-8 w-8 p-0"
                                            onClick={() => editor?.chain().focus().toggleOrderedList().run()}
                                        >
                                            ☷
                                        </Button>

                                        <div className="w-px h-6 bg-border mx-2" />

                                        {/* Quote */}
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={editor?.isActive('blockquote') ? 'default' : 'ghost'}
                                            className="h-8 w-8 p-0"
                                            onClick={() => editor?.chain().focus().toggleBlockquote().run()}
                                        >
                                            "
                                        </Button>

                                        <div className="w-px h-6 bg-border mx-2" />

                                        {/* Text Formatting */}
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={editor?.isActive('bold') ? 'default' : 'ghost'}
                                            className="h-8 w-8 p-0 font-bold"
                                            onClick={() => editor?.chain().focus().toggleBold().run()}
                                        >
                                            B
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={editor?.isActive('italic') ? 'default' : 'ghost'}
                                            className="h-8 w-8 p-0 italic"
                                            onClick={() => editor?.chain().focus().toggleItalic().run()}
                                        >
                                            I
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={editor?.isActive('strike') ? 'default' : 'ghost'}
                                            className="h-8 w-8 p-0"
                                            onClick={() => editor?.chain().focus().toggleStrike().run()}
                                        >
                                            <span className="line-through">S</span>
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={editor?.isActive('code') ? 'default' : 'ghost'}
                                            className="h-8 w-8 p-0 font-mono text-xs"
                                            onClick={() => editor?.chain().focus().toggleCode().run()}
                                        >
                                            {'<>'}
                                        </Button>

                                        <div className="w-px h-6 bg-border mx-2" />

                                        {/* Link */}
                                        <Button 
                                            type="button" 
                                            size="sm" 
                                            variant={editor?.isActive('link') ? 'default' : 'ghost'} 
                                            className="h-8 w-8 p-0" 
                                            onClick={() => {
                                                if (editor?.isActive('link')) {
                                                    editor.chain().focus().unsetLink().run();
                                                } else {
                                                    const previousUrl = editor?.getAttributes('link').href;
                                                    const url = prompt('Enter URL', previousUrl || 'https://');
                                                    if (url === null) return; // User cancelled
                                                    if (url === '') {
                                                        editor?.chain().focus().unsetLink().run();
                                                        return;
                                                    }
                                                    // Add https:// if no protocol is specified
                                                    const finalUrl = url.match(/^https?:\/\//) ? url : `https://${url}`;
                                                    editor?.chain().focus().setLink({ href: finalUrl }).run();
                                                }
                                            }}
                                            title={editor?.isActive('link') ? 'Remove link' : 'Add link'}
                                        >
                                            🔗
                                        </Button>                                        <div className="w-px h-6 bg-border mx-2" />

                                        {/* Image */}
                                        <Button type="button" size="sm" variant="ghost" className="h-8 w-8 p-0" onClick={() => {
                                            const input = document.createElement('input');
                                            input.type = 'file';
                                            input.accept = 'image/*';
                                            input.onchange = async (e) => {
                                                const file = (e.target as HTMLInputElement).files?.[0];
                                                if (!file) return;
                                                const form = new FormData();
                                                form.append('file', file);
                                                const resp = await fetch('/posts/upload-image', {
                                                    method: 'POST',
                                                    body: form,
                                                    headers: {
                                                        'X-Requested-With': 'XMLHttpRequest',
                                                        'X-CSRF-TOKEN': (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement)?.getAttribute('content') || ''
                                                    }
                                                });
                                                if (resp.ok) {
                                                    const json = await resp.json();
                                                    const url = json.url;
                                                    editor?.chain().focus().setImage({ src: url }).run();
                                                } else {
                                                    alert('Upload failed');
                                                }
                                            };
                                            input.click();
                                        }}>
                                            🖼️
                                        </Button>
                                    </div>

                                    {/* Editor */}
                                    <div className="min-h-[300px] border border-border rounded-b-md border-t-0 bg-background">
                                        <EditorContent
                                            editor={editor}
                                            className="min-h-[280px] focus:outline-none p-4 text-foreground"
                                        />
                                    </div>
                                </div>
                                {errors.content && <div className="text-sm text-red-600">{errors.content}</div>}
                            </div>

                            <div className="space-y-2">
                                <Label htmlFor="tags">Tags</Label>
                                <Input
                                    id="tags"
                                    value={data.tags}
                                    onChange={e => setData('tags', e.target.value)}
                                />
                            </div>

                            <Button type="submit" disabled={processing}>
                                {post ? 'Update' : 'Create'}
                            </Button>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </AppLayout>
    );
}
