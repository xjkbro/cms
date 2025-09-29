import React, { useState, useEffect, useRef } from 'react';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { X, Plus } from 'lucide-react';

interface Tag {
    id: number;
    name: string;
    slug: string;
}

interface TagInputProps {
    value: string[];
    onChange: (tags: string[]) => void;
    placeholder?: string;
    className?: string;
}

export function TagInput({ value, onChange, placeholder = "Add tags...", className }: TagInputProps) {
    const [inputValue, setInputValue] = useState('');
    const [suggestions, setSuggestions] = useState<Tag[]>([]);
    const [showSuggestions, setShowSuggestions] = useState(false);
    const [selectedSuggestionIndex, setSelectedSuggestionIndex] = useState(-1);
    const inputRef = useRef<HTMLInputElement>(null);
    const suggestionsRef = useRef<HTMLDivElement>(null);

    // Fetch suggestions when input changes
    useEffect(() => {
        const fetchSuggestions = async (query: string) => {
            try {
                const response = await fetch(`/api/tags?q=${encodeURIComponent(query)}`);
                if (response.ok) {
                    const tags: Tag[] = await response.json();
                    // Filter out tags that are already selected
                    const filteredTags = tags.filter(tag => !value.includes(tag.name));
                    setSuggestions(filteredTags);
                    setShowSuggestions(filteredTags.length > 0);
                    setSelectedSuggestionIndex(-1);
                }
            } catch (error) {
                console.error('Error fetching tag suggestions:', error);
            }
        };

        if (inputValue.trim().length > 0) {
            fetchSuggestions(inputValue.trim());
        } else {
            setSuggestions([]);
            setShowSuggestions(false);
        }
    }, [inputValue, value]);

    const addTag = (tagName: string) => {
        const trimmedTag = tagName.trim();
        if (trimmedTag && !value.includes(trimmedTag)) {
            onChange([...value, trimmedTag]);
        }
        setInputValue('');
        setShowSuggestions(false);
        setSelectedSuggestionIndex(-1);
        inputRef.current?.focus();
    };

    const removeTag = (tagToRemove: string) => {
        onChange(value.filter(tag => tag !== tagToRemove));
    };

    const handleKeyDown = (e: React.KeyboardEvent<HTMLInputElement>) => {
        if (e.key === ',') {
            e.preventDefault();
            if (inputValue.trim()) {
                addTag(inputValue);
            }
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (selectedSuggestionIndex >= 0 && suggestions[selectedSuggestionIndex]) {
                addTag(suggestions[selectedSuggestionIndex].name);
            } else if (inputValue.trim()) {
                addTag(inputValue);
            }
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            setSelectedSuggestionIndex(prev =>
                prev < suggestions.length - 1 ? prev + 1 : prev
            );
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setSelectedSuggestionIndex(prev => prev > 0 ? prev - 1 : -1);
        } else if (e.key === 'Escape') {
            setShowSuggestions(false);
            setSelectedSuggestionIndex(-1);
        }
    };

    const handleSuggestionClick = (tag: Tag) => {
        addTag(tag.name);
    };

    // Close suggestions when clicking outside
    useEffect(() => {
        const handleClickOutside = (event: MouseEvent) => {
            if (suggestionsRef.current && !suggestionsRef.current.contains(event.target as Node) &&
                inputRef.current && !inputRef.current.contains(event.target as Node)) {
                setShowSuggestions(false);
                setSelectedSuggestionIndex(-1);
            }
        };

        document.addEventListener('mousedown', handleClickOutside);
        return () => document.removeEventListener('mousedown', handleClickOutside);
    }, []);

    return (
        <div className={`space-y-2 ${className}`}>
            <div className="flex flex-wrap gap-2 min-h-[2.5rem] p-2 border border-input rounded-md bg-background">
                {value.map((tag, index) => (
                    <Badge key={index} variant="secondary" className="flex items-center gap-1">
                        {tag}
                        <button
                            type="button"
                            onClick={() => removeTag(tag)}
                            className="ml-1 hover:bg-secondary-foreground/20 rounded-full p-0.5"
                        >
                            <X className="h-3 w-3" />
                        </button>
                    </Badge>
                ))}
                <div className="flex-1 relative">
                    <Input
                        ref={inputRef}
                        value={inputValue}
                        onChange={(e) => setInputValue(e.target.value)}
                        onKeyDown={handleKeyDown}
                        onFocus={() => {
                            if (suggestions.length > 0) {
                                setShowSuggestions(true);
                            }
                        }}
                        placeholder={value.length === 0 ? placeholder : ""}
                        className="border-0 shadow-none focus-visible:ring-0 p-0 h-auto"
                    />
                    {showSuggestions && suggestions.length > 0 && (
                        <div
                            ref={suggestionsRef}
                            className="absolute top-full left-0 right-0 z-10 bg-popover border border-border rounded-md shadow-md max-h-40 overflow-y-auto"
                        >
                            {suggestions.map((tag, index) => (
                                <button
                                    key={tag.id}
                                    type="button"
                                    onClick={() => handleSuggestionClick(tag)}
                                    className={`w-full text-left px-3 py-2 hover:bg-accent hover:text-accent-foreground flex items-center gap-2 ${
                                        index === selectedSuggestionIndex ? 'bg-accent text-accent-foreground' : ''
                                    }`}
                                >
                                    <Plus className="h-4 w-4" />
                                    {tag.name}
                                </button>
                            ))}
                        </div>
                    )}
                </div>
            </div>
            <p className="text-sm text-muted-foreground">
                Type a tag and press comma or Enter to add it. Use arrow keys to navigate suggestions.
            </p>
        </div>
    );
}
