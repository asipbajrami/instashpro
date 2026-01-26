'use client';

import { useState, useRef, useEffect, Suspense } from 'react';
import { useRouter, useSearchParams } from 'next/navigation';
import { Search, Tag, Loader2, X } from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useGroup } from '@/components/providers/group-provider';
import { useSearchSuggestions } from '@/hooks/use-products';

const groupLabels: Record<string, string> = {
  car: 'vehicles',
  tech: 'technology',
};

function SearchContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { selectedGroup } = useGroup();
  const inputRef = useRef<HTMLInputElement>(null);

  const [searchQuery, setSearchQuery] = useState(searchParams.get('q') || '');

  const { data: suggestionsData, isLoading } = useSearchSuggestions(
    searchQuery || undefined,
    selectedGroup || undefined,
    true
  );

  useEffect(() => {
    setTimeout(() => inputRef.current?.focus(), 100);
  }, []);

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    if (searchQuery.trim()) {
      router.push(`/?q=${encodeURIComponent(searchQuery.trim())}`);
    } else {
      // Empty search clears and goes home
      router.push('/');
    }
  };

  const handleSuggestionClick = (query: string) => {
    router.push(`/?q=${encodeURIComponent(query)}`);
  };

  const handleCategoryClick = (categoryId: number) => {
    router.push(`/?category_id=${categoryId}`);
  };

  const handleSellerClick = (username: string) => {
    router.push(`/?profile=${username}`);
  };

  const suggestions = suggestionsData?.data;
  const hasQuery = searchQuery.length > 0;
  const groupLabel = selectedGroup ? groupLabels[selectedGroup] : 'products';

  return (
    <div className="min-h-screen bg-background flex flex-col">
      {/* Header - Mobile only (desktop uses main header) */}
      <div className="sticky top-0 z-50 bg-background border-b pt-[env(safe-area-inset-top)] sm:hidden">
        <div className="flex items-center gap-2 px-3 py-2">
          <form onSubmit={handleSearch} className="flex-1">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                ref={inputRef}
                type="text"
                placeholder={`Search ${groupLabel}...`}
                className="pl-9 pr-9 h-9 bg-muted/50 border-0 rounded-lg"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
              {searchQuery && (
                <button
                  type="button"
                  onClick={() => setSearchQuery('')}
                  className="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-muted-foreground hover:text-foreground"
                >
                  <X className="h-4 w-4" />
                </button>
              )}
            </div>
          </form>
          <Button
            variant="ghost"
            size="sm"
            className="shrink-0 text-sm font-medium"
            onClick={() => router.back()}
          >
            Cancel
          </Button>
        </div>
      </div>

      {/* Content */}
      <div className="flex-1 overflow-y-auto">
        <div className="max-w-2xl mx-auto">
          {isLoading ? (
            <div className="flex items-center justify-center py-12">
              <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
            </div>
          ) : hasQuery ? (
            <div className="py-2 lg:py-4">
              {/* Products */}
              {suggestions?.products && suggestions.products.length > 0 && (
                <div className="mb-4 lg:mb-6">
                  <p className="px-4 py-2 text-xs font-medium text-muted-foreground uppercase">Products</p>
                  {suggestions.products.map((item, index) => (
                    <button
                      key={index}
                      onClick={() => handleSuggestionClick(item.text)}
                      className="flex items-center gap-3 w-full px-4 py-2.5 lg:py-3 hover:bg-muted/50 text-left rounded-lg lg:mx-2 lg:w-[calc(100%-16px)]"
                    >
                      <Search className="h-4 w-4 text-muted-foreground shrink-0" />
                      <span className="text-sm lg:text-base flex-1 truncate">{item.text}</span>
                      <span className="text-xs lg:text-sm text-muted-foreground">{item.count}</span>
                    </button>
                  ))}
                </div>
              )}

              {/* Categories */}
              {suggestions?.categories && suggestions.categories.length > 0 && (
                <div className="mb-4 lg:mb-6">
                  <p className="px-4 py-2 text-xs font-medium text-muted-foreground uppercase">Categories</p>
                  {suggestions.categories.map((item) => (
                    <button
                      key={item.id}
                      onClick={() => handleCategoryClick(item.id!)}
                      className="flex items-center gap-3 w-full px-4 py-2.5 lg:py-3 hover:bg-muted/50 text-left rounded-lg lg:mx-2 lg:w-[calc(100%-16px)]"
                    >
                      <Tag className="h-4 w-4 text-muted-foreground shrink-0" />
                      <span className="text-sm lg:text-base flex-1 truncate">{item.text}</span>
                      <span className="text-xs lg:text-sm text-muted-foreground">{item.count}</span>
                    </button>
                  ))}
                </div>
              )}

              {/* Sellers */}
              {suggestions?.sellers && suggestions.sellers.length > 0 && (
                <div className="mb-4 lg:mb-6">
                  <p className="px-4 py-2 text-xs font-medium text-muted-foreground uppercase">Sellers</p>
                  {suggestions.sellers.map((item, index) => (
                    <button
                      key={index}
                      onClick={() => handleSellerClick(item.username!)}
                      className="flex items-center gap-3 w-full px-4 py-2.5 lg:py-3 hover:bg-muted/50 text-left rounded-lg lg:mx-2 lg:w-[calc(100%-16px)]"
                    >
                      <div className="h-6 w-6 lg:h-8 lg:w-8 rounded-full bg-gradient-to-br from-pink-500 to-orange-400 flex items-center justify-center text-white text-xs lg:text-sm font-medium shrink-0">
                        {item.username?.charAt(0).toUpperCase()}
                      </div>
                      <span className="text-sm lg:text-base flex-1 truncate">{item.text}</span>
                      <span className="text-xs lg:text-sm text-muted-foreground">{item.count}</span>
                    </button>
                  ))}
                </div>
              )}

              {/* No results - show search query as option */}
              {!suggestions?.products?.length && !suggestions?.categories?.length && !suggestions?.sellers?.length && (
                <div className="mb-4 lg:mb-6">
                  <p className="px-4 py-2 text-xs font-medium text-muted-foreground uppercase">Search for</p>
                  <button
                    onClick={() => router.push(`/?q=${encodeURIComponent(searchQuery)}`)}
                    className="flex items-center gap-3 w-full px-4 py-2.5 lg:py-3 hover:bg-muted/50 text-left rounded-lg lg:mx-2 lg:w-[calc(100%-16px)]"
                  >
                    <Search className="h-4 w-4 text-muted-foreground shrink-0" />
                    <span className="text-sm lg:text-base flex-1 truncate">{searchQuery}</span>
                  </button>
                </div>
              )}
            </div>
          ) : (
            <div className="py-2 lg:py-4">
              {/* Popular */}
              {suggestions?.popular && suggestions.popular.length > 0 && (
                <div className="mb-4 lg:mb-6">
                  <p className="px-4 py-2 text-xs font-medium text-muted-foreground uppercase">Popular</p>
                  {suggestions.popular.slice(0, 8).map((item, index) => (
                    <button
                      key={index}
                      onClick={() => handleSuggestionClick(item.text)}
                      className="flex items-center gap-3 w-full px-4 py-2.5 lg:py-3 hover:bg-muted/50 text-left rounded-lg lg:mx-2 lg:w-[calc(100%-16px)]"
                    >
                      <Search className="h-4 w-4 text-muted-foreground shrink-0" />
                      <span className="text-sm lg:text-base truncate">{item.text}</span>
                    </button>
                  ))}
                </div>
              )}

              {/* Categories */}
              {suggestions?.categories && suggestions.categories.length > 0 && (
                <div className="mb-4 lg:mb-6">
                  <p className="px-4 py-2 text-xs font-medium text-muted-foreground uppercase">Categories</p>
                  {suggestions.categories.map((item) => (
                    <button
                      key={item.id}
                      onClick={() => handleCategoryClick(item.id!)}
                      className="flex items-center gap-3 w-full px-4 py-2.5 lg:py-3 hover:bg-muted/50 text-left rounded-lg lg:mx-2 lg:w-[calc(100%-16px)]"
                    >
                      <Tag className="h-4 w-4 text-muted-foreground shrink-0" />
                      <span className="text-sm lg:text-base truncate">{item.text}</span>
                    </button>
                  ))}
                </div>
              )}

              {/* Sellers */}
              {suggestions?.sellers && suggestions.sellers.length > 0 && (
                <div className="lg:mb-6">
                  <p className="px-4 py-2 text-xs font-medium text-muted-foreground uppercase">Top Sellers</p>
                  {suggestions.sellers.slice(0, 5).map((item, index) => (
                    <button
                      key={index}
                      onClick={() => handleSellerClick(item.username!)}
                      className="flex items-center gap-3 w-full px-4 py-2.5 lg:py-3 hover:bg-muted/50 text-left rounded-lg lg:mx-2 lg:w-[calc(100%-16px)]"
                    >
                      <div className="h-6 w-6 lg:h-8 lg:w-8 rounded-full bg-gradient-to-br from-pink-500 to-orange-400 flex items-center justify-center text-white text-xs lg:text-sm font-medium shrink-0">
                        {item.username?.charAt(0).toUpperCase()}
                      </div>
                      <span className="text-sm lg:text-base flex-1 truncate">{item.text}</span>
                      <span className="text-xs lg:text-sm text-muted-foreground">{item.count}</span>
                    </button>
                  ))}
                </div>
              )}

              {/* Empty */}
              {!suggestions?.popular?.length && !suggestions?.categories?.length && !suggestions?.sellers?.length && (
                <div className="text-center py-12 px-4">
                  <p className="text-muted-foreground lg:text-lg">Start typing to search</p>
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  );
}

export default function SearchPage() {
  return (
    <Suspense fallback={
      <div className="min-h-screen bg-background flex items-center justify-center">
        <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
      </div>
    }>
      <SearchContent />
    </Suspense>
  );
}
