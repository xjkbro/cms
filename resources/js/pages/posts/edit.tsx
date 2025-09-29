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
import { TagInput } from '@/components/TagInput';
import { Switch } from '@/components/ui/switch';
import { MediaBrowser } from '@/components/MediaBrowser';

interface PostFormData {
    title: string;
    content: string;
    category_id: string | number;
    excerpt: string;
    tags: string[];
    is_draft?: boolean;
}

interface Post {
    id: number;
    title: string;
    content: string;
    category_id: number;
    excerpt: string;
    tags: Tag[];
}

interface Category {
    id: number;
    name: string;
}

interface Tag {
    id: number;
    name: string;
}

interface PostEditProps {
    post?: Post | null;
    categories?: Category[];
    existingTags?: string[];
}

export default function PostEdit({ post = null, categories = [], existingTags = [] }: PostEditProps) {
    const { data, setData, post: submit, put, processing, errors } = useForm<PostFormData>({
        title: post?.title ?? '',
        content: post?.content ?? '',
        category_id: post?.category_id ?? '',
        excerpt: post?.excerpt ?? '',
        tags: existingTags.length > 0 ? existingTags : (post?.tags ? post.tags.map((tag: Tag) => tag.name) : []),
        // eslint-disable-next-line @typescript-eslint/no-explicit-any
        is_draft: post ? Boolean((post as any).is_draft) : true,
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
        .ProseMirror ul {
            list-style-type: disc;
            list-style-position: outside;
        }
        .ProseMirror ol {
            list-style-type: decimal;
            list-style-position: outside;
        }
        .ProseMirror li {
            margin: 0.25rem 0;
            display: list-item;
        }
        /* Nested list styles */
        .ProseMirror ul ul {
            list-style-type: circle;
        }
        .ProseMirror ul ul ul {
            list-style-type: square;
        }
        .ProseMirror ol ol {
            list-style-type: lower-alpha;
        }
        .ProseMirror ol ol ol {
            list-style-type: lower-roman;
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
                                        <div className="flex items-center gap-2">
                                            <Switch
                                                checked={!data.is_draft}
                                                onCheckedChange={(checked) => setData('is_draft', !checked)}
                                                id="published-switch"
                                            />
                                            <Label htmlFor="published-switch" className="text-sm cursor-pointer">
                                                Published
                                            </Label>
                                        </div>
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
                                            className="h-8 w-8 p-0 hover:cursor-pointer"
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
                                            className="h-8 w-8 p-0 hover:cursor-pointer"
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
                                            className="h-8 w-8 p-0 hover:cursor-pointer"
                                            onClick={() => editor?.chain().focus().toggleBulletList().run()}
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                            </svg>
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={editor?.isActive('orderedList') ? 'default' : 'ghost'}
                                            className="h-8 w-8 p-0 hover:cursor-pointer"
                                            onClick={() => editor?.chain().focus().toggleOrderedList().run()}
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M8.242 5.992h12m-12 6.003H20.24m-12 5.999h12M4.117 7.495v-3.75H2.99m1.125 3.75H2.99m1.125 0H5.24m-1.92 2.577a1.125 1.125 0 1 1 1.591 1.59l-1.83 1.83h2.16M2.99 15.745h1.125a1.125 1.125 0 0 1 0 2.25H3.74m0-.002h.375a1.125 1.125 0 0 1 0 2.25H2.99" />
                                            </svg>
                                        </Button>

                                        <div className="w-px h-6 bg-border mx-2" />

                                        {/* Quote */}
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={editor?.isActive('blockquote') ? 'default' : 'ghost'}
                                            className="h-8 w-8 p-0 hover:cursor-pointer"
                                            onClick={() => editor?.chain().focus().toggleBlockquote().run()}
                                        >
                                            {/* <TextQuote height={36} width={36} className=''/> */}
                                            <span className='text-5xl mt-6 font-bold'>{'"'}</span>
                                        </Button>

                                        <div className="w-px h-6 bg-border mx-2" />

                                        {/* Text Formatting */}
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={editor?.isActive('bold') ? 'default' : 'ghost'}
                                            className="h-8 w-8 p-0 hover:cursor-pointer font-bold"
                                            onClick={() => editor?.chain().focus().toggleBold().run()}
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
                                                <path strokeLinejoin="round" d="M6.75 3.744h-.753v8.25h7.125a4.125 4.125 0 0 0 0-8.25H6.75Zm0 0v.38m0 16.122h6.747a4.5 4.5 0 0 0 0-9.001h-7.5v9h.753Zm0 0v-.37m0-15.751h6a3.75 3.75 0 1 1 0 7.5h-6m0-7.5v7.5m0 0v8.25m0-8.25h6.375a4.125 4.125 0 0 1 0 8.25H6.75m.747-15.38h4.875a3.375 3.375 0 0 1 0 6.75H7.497v-6.75Zm0 7.5h5.25a3.75 3.75 0 0 1 0 7.5h-5.25v-7.5Z" />
                                            </svg>
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={editor?.isActive('italic') ? 'default' : 'ghost'}
                                            className="h-8 w-8 p-0 hover:cursor-pointer italic"
                                            onClick={() => editor?.chain().focus().toggleItalic().run()}
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M5.248 20.246H9.05m0 0h3.696m-3.696 0 5.893-16.502m0 0h-3.697m3.697 0h3.803" />
                                            </svg>
                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={editor?.isActive('strike') ? 'default' : 'ghost'}
                                            className="h-8 w-8 p-0 hover:cursor-pointer"
                                            onClick={() => editor?.chain().focus().toggleStrike().run()}
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M12 12a8.912 8.912 0 0 1-.318-.079c-1.585-.424-2.904-1.247-3.76-2.236-.873-1.009-1.265-2.19-.968-3.301.59-2.2 3.663-3.29 6.863-2.432A8.186 8.186 0 0 1 16.5 5.21M6.42 17.81c.857.99 2.176 1.812 3.761 2.237 3.2.858 6.274-.23 6.863-2.431.233-.868.044-1.779-.465-2.617M3.75 12h16.5" />
                                            </svg>

                                        </Button>
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={editor?.isActive('code') ? 'default' : 'ghost'}
                                            className="h-8 w-8 p-0 hover:cursor-pointer font-mono text-xs"
                                            onClick={() => editor?.chain().focus().toggleCode().run()}
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M17.25 6.75 22.5 12l-5.25 5.25m-10.5 0L1.5 12l5.25-5.25m7.5-3-4.5 16.5" />
                                            </svg>

                                        </Button>

                                        <div className="w-px h-6 bg-border mx-2" />

                                        {/* Link */}
                                        <Button
                                            type="button"
                                            size="sm"
                                            variant={editor?.isActive('link') ? 'default' : 'ghost'}
                                            className="h-8 w-8 p-0 hover:cursor-pointer"
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
                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
                                                <path strokeLinecap="round" strokeLinejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />
                                            </svg>
                                        </Button>

                                        <div className="w-px h-6 bg-border mx-2" />

                                        {/* Image */}
                                        <MediaBrowser
                                            onSelect={(url) => {
                                                editor?.chain().focus().setImage({ src: url }).run();
                                            }}
                                            trigger={
                                                <Button
                                                    type="button"
                                                    size="sm"
                                                    variant="ghost"
                                                    className="h-8 w-8 p-0 hover:cursor-pointer"
                                                    title="Insert image"
                                                >
                                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" strokeWidth={1.5} stroke="currentColor" className="size-6">
                                                        <path strokeLinecap="round" strokeLinejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                                    </svg>

                                                </Button>
                                            }
                                        />
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
                                <TagInput
                                    value={data.tags}
                                    onChange={(tags) => setData('tags', tags)}
                                    placeholder="Add tags for your post"
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
