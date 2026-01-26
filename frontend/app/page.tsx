'use client';

import { useState, useCallback, useEffect, Suspense, useRef } from 'react';
import { useSearchParams, useRouter } from 'next/navigation';
import { useProducts, useSearchProducts, useAdvancedSearch } from '@/hooks/use-products';
import { ProductGrid } from '@/components/products/product-grid';
import { ProductFilters, FilterState } from '@/components/products/product-filters';
import { FilterSheet } from '@/components/layout/filter-sheet';
import { Footer } from '@/components/layout/footer';
import { Button } from '@/components/ui/button';
import { Skeleton } from '@/components/ui/skeleton';
import { Badge } from '@/components/ui/badge';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight, LayoutGrid, ArrowUpDown, Search, Moon, Sun, X } from 'lucide-react';
import { useTheme } from 'next-themes';
import Link from 'next/link';
import { useGroup } from '@/components/providers/group-provider';
import { AdvancedSearchParams } from '@/lib/api';

// ============================================================================
// URL is the single source of truth for filters.
// Browser history handles back/forward navigation automatically.
// No localStorage needed - this is the idiomatic React/Next.js approach.
// ============================================================================

function buildUrlParams(filters: FilterState, searchQuery?: string): string {
  const params = new URLSearchParams();
  if (filters.category) params.set('category', filters.category);
  if (filters.category_id !== undefined) params.set('category_id', String(filters.category_id));
  if (filters.min_price !== undefined) params.set('min_price', String(filters.min_price));
  if (filters.max_price !== undefined) params.set('max_price', String(filters.max_price));
  if (filters.type) params.set('type', filters.type);
  if (filters.currency) params.set('currency', filters.currency);
  if (filters.profile) params.set('profile', filters.profile);
  if (filters.sort) params.set('sort', filters.sort);
  if (filters.has_price) params.set('has_price', '1');
  if (searchQuery) params.set('q', searchQuery);

  // Serialize attributes as attr[AttributeName]=value1,value2
  if (filters.attributes) {
    Object.entries(filters.attributes).forEach(([attrName, values]) => {
      if (values.length > 0) {
        params.set(`attr[${attrName}]`, values.join(','));
      }
    });
  }

  const queryString = params.toString();
  return queryString ? `/?${queryString}` : '/';
}

function parseFiltersFromParams(searchParams: URLSearchParams): FilterState {
  // Parse attributes from attr[AttributeName]=value1,value2 format
  const attributes: Record<string, string[]> = {};
  searchParams.forEach((value, key) => {
    const match = key.match(/^attr\[(.+)\]$/);
    if (match) {
      const attrName = match[1];
      attributes[attrName] = value.split(',').filter(Boolean);
    }
  });

  return {
    category: searchParams.get('category') || undefined,
    category_id: searchParams.get('category_id')
      ? Number(searchParams.get('category_id'))
      : undefined,
    min_price: searchParams.get('min_price')
      ? Number(searchParams.get('min_price'))
      : undefined,
    max_price: searchParams.get('max_price')
      ? Number(searchParams.get('max_price'))
      : undefined,
    type: searchParams.get('type') || undefined,
    currency: searchParams.get('currency') || undefined,
    profile: searchParams.get('profile') || undefined,
    sort: (searchParams.get('sort') as FilterState['sort']) || undefined,
    has_price: searchParams.get('has_price') === '1' || undefined,
    attributes: Object.keys(attributes).length > 0 ? attributes : undefined,
  };
}

function HomePageSkeleton() {
  return (
    <div className="flex min-h-[calc(100vh-3.5rem)]">
      {/* Sidebar skeleton - desktop only */}
      <aside className="hidden lg:flex w-64 flex-col border-r bg-background flex-shrink-0">
        <div className="border-b px-4 py-3">
          <Skeleton className="h-5 w-20" />
        </div>
        <div className="px-4 py-2 space-y-4">
          {Array.from({ length: 6 }).map((_, i) => (
            <div key={i} className="space-y-2">
              <Skeleton className="h-4 w-24" />
              <Skeleton className="h-8 w-full" />
            </div>
          ))}
        </div>
      </aside>

      {/* Main content skeleton */}
      <main className="flex-1 flex flex-col">
        <div className="max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-6 w-full">
          <div className="grid grid-cols-2 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-4 2xl:grid-cols-5 gap-4 md:gap-5 lg:gap-6">
            {Array.from({ length: 8 }).map((_, i) => (
              <div key={i} className="rounded-xl border bg-card overflow-hidden">
                <Skeleton className="aspect-square w-full" />
                <div className="p-4 space-y-2">
                  <Skeleton className="h-4 w-3/4" />
                  <Skeleton className="h-5 w-1/3" />
                </div>
              </div>
            ))}
          </div>
        </div>
      </main>
    </div>
  );
}

export default function Home() {
  return (
    <Suspense fallback={<HomePageSkeleton />}>
      <HomeContent />
    </Suspense>
  );
}

function HomeContent() {
  const router = useRouter();
  const searchParams = useSearchParams();
  const { selectedGroup } = useGroup();
  const { theme, setTheme } = useTheme();
  const previousGroup = useRef(selectedGroup);

  // Derive filters directly from URL - single source of truth
  const filters = parseFiltersFromParams(searchParams);
  const searchQuery = searchParams.get('q') || '';
  const [page, setPage] = useState(1);

  // Parse advanced search params
  const advancedSearchParams: AdvancedSearchParams = {
    q: searchQuery || undefined,
    name: searchParams.get('name') || undefined,
    seller: searchParams.get('seller') || undefined,
    attr_value: searchParams.get('attr_value') || undefined,
    group: selectedGroup || undefined,
    per_page: 24,
    page,
  };

  // Check if this is an advanced search
  const isAdvancedSearch = Boolean(
    advancedSearchParams.name ||
    advancedSearchParams.seller ||
    advancedSearchParams.attr_value
  );

  // Reset page when filters change
  useEffect(() => {
    setPage(1);
  }, [searchParams]);

  // Reset filters when switching between groups (tech <-> cars)
  useEffect(() => {
    if (previousGroup.current !== selectedGroup && previousGroup.current !== null) {
      router.push('/', { scroll: false });
    }
    previousGroup.current = selectedGroup;
  }, [selectedGroup, router]);

  const { data: productsData, isLoading: productsLoading } = useProducts(
    searchQuery || isAdvancedSearch
      ? { group: selectedGroup || undefined }
      : {
          ...filters,
          group: selectedGroup || undefined,
          page,
          per_page: 24,
        },
  );

  const { data: searchData, isLoading: searchLoading } = useSearchProducts(
    searchQuery && !isAdvancedSearch ? searchQuery : '',
    24,
    selectedGroup || undefined,
  );

  const { data: advancedData, isLoading: advancedLoading } = useAdvancedSearch(
    advancedSearchParams,
    isAdvancedSearch || Boolean(searchQuery),
  );

  // Determine which data to use
  const isLoading = isAdvancedSearch || searchQuery
    ? (isAdvancedSearch ? advancedLoading : searchLoading)
    : productsLoading;
  const data = isAdvancedSearch || searchQuery
    ? (isAdvancedSearch ? advancedData : searchData)
    : productsData;

  // Update URL when filters change - this is the only place we modify filters
  const updateFilters = useCallback(
    (newFilters: FilterState) => {
      const url = buildUrlParams(newFilters, searchQuery);
      router.push(url, { scroll: false });
    },
    [router, searchQuery],
  );

  const handleSortChange = (value: string) => {
    updateFilters({
      ...filters,
      sort: value as FilterState['sort'],
    });
  };

  const products = data?.data?.items || [];
  const pagination = data?.data?.pagination;
  const facets = data?.data?.facets;

  return (
    <div className="flex min-h-[calc(100vh-3.5rem)]">
      {/* Desktop Sidebar */}
      <aside className="hidden lg:flex w-64 flex-col border-r bg-background flex-shrink-0 sticky top-14 h-[calc(100vh-3.5rem)] overflow-hidden">
        <div className="flex-1 overflow-y-auto px-3 pr-2 scrollbar-thin scrollbar-thumb-muted-foreground/20 scrollbar-track-transparent">
          <div className="py-3">
            <ProductFilters
              filters={filters}
              onFiltersChange={updateFilters}
              facets={facets}
              className="-mx-3 px-3"
            />
          </div>
        </div>
      </aside>

      {/* Main Content */}
      <main className="flex-1 flex flex-col min-w-0">
        <div className="max-w-[1600px] w-full mx-auto px-4 sm:px-6 lg:px-8 py-6 flex-1">
          {/* Mobile header */}
          <div className="flex flex-col gap-2 mb-4 sm:hidden">
            {/* Top row - Filter and Search Bar */}
            <div className="flex items-center gap-2">
              <FilterSheet
                filters={filters}
                onFiltersChange={updateFilters}
                facets={facets}
              />
              <div className="flex-1 relative">
                <Link href={searchQuery ? `/search?q=${encodeURIComponent(searchQuery)}` : '/search'} className="block">
                  <div className="flex items-center gap-2 h-9 px-3 pr-9 rounded-md border bg-muted/50 text-sm">
                    <Search className="h-4 w-4 text-muted-foreground shrink-0" />
                    <span className={searchQuery ? 'text-foreground truncate' : 'text-muted-foreground'}>
                      {searchQuery || 'Search products...'}
                    </span>
                  </div>
                </Link>
                {searchQuery && (
                  <button
                    type="button"
                    onClick={(e) => {
                      e.preventDefault();
                      e.stopPropagation();
                      router.push('/');
                    }}
                    className="absolute right-2 top-1/2 -translate-y-1/2 p-1 text-muted-foreground hover:text-foreground rounded-full hover:bg-muted z-10"
                  >
                    <X className="h-4 w-4" />
                  </button>
                )}
              </div>
              <Button
                variant="outline"
                size="icon"
                className="h-9 w-9 shrink-0"
                onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
              >
                <Sun className="h-4 w-4 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
                <Moon className="absolute h-4 w-4 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
              </Button>
            </div>
            {/* Warning */}
            <div className="bg-muted/30 border rounded-lg py-1 px-2">
              <p className="text-[8px] text-muted-foreground font-medium uppercase tracking-tight text-center">
                Data collected by AI • Check Instagram post for accuracy
              </p>
            </div>
            {/* Products count and Sort */}
            <div className="flex items-center justify-between">
              <div className="flex items-center gap-1.5">
                <LayoutGrid className="h-3.5 w-3.5 text-muted-foreground" />
                <span className="text-xs text-muted-foreground">
                  {pagination ? `${pagination.total} Products` : 'Products'}
                </span>
              </div>
              <Select value={filters.sort || 'newest'} onValueChange={handleSortChange}>
                <SelectTrigger className="w-auto h-7 px-2 gap-1 text-xs border-0 bg-transparent">
                  <ArrowUpDown className="h-3 w-3" />
                  <SelectValue placeholder="Sort" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="newest">Newest</SelectItem>
                  <SelectItem value="oldest">Oldest</SelectItem>
                  <SelectItem value="price_asc">Price ↑</SelectItem>
                  <SelectItem value="price_desc">Price ↓</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          {/* Results info - Desktop */}
          <div className="hidden lg:flex items-center justify-between mb-6">
            <div className="flex items-center gap-2 flex-wrap">
              <p className="text-sm text-muted-foreground">
                {isAdvancedSearch || searchQuery ? (
                  <>
                    {products.length} results
                    {searchQuery && <> for &quot;{searchQuery}&quot;</>}
                  </>
                ) : pagination ? (
                  <>
                    Showing {products.length} of {pagination.total} products
                  </>
                ) : null}
              </p>
              {isAdvancedSearch && (
                <div className="flex items-center gap-1.5">
                  <Search className="h-3.5 w-3.5 text-muted-foreground" />
                  {advancedSearchParams.name && (
                    <Badge variant="secondary" className="text-xs">
                      Name: {advancedSearchParams.name}
                    </Badge>
                  )}
                  {advancedSearchParams.seller && (
                    <Badge variant="secondary" className="text-xs">
                      Seller: {advancedSearchParams.seller}
                    </Badge>
                  )}
                  {advancedSearchParams.attr_value && (
                    <Badge variant="secondary" className="text-xs">
                      Attribute: {advancedSearchParams.attr_value}
                    </Badge>
                  )}
                </div>
              )}
            </div>

            <div className="flex-1 flex justify-center px-4">
              <p className="text-[10px] text-muted-foreground font-medium uppercase tracking-tight opacity-70">
                Data are being collected by AI. Mistakes may be made, please check the Instagram post for better info.
              </p>
            </div>

            <div className="flex items-center gap-2">
              <span className="text-sm text-muted-foreground">Sort by:</span>
              <Select value={filters.sort || 'newest'} onValueChange={handleSortChange}>
                <SelectTrigger className="w-[160px]">
                  <SelectValue placeholder="Sort by" />
                </SelectTrigger>
                <SelectContent>
                  <SelectItem value="newest">Newest First</SelectItem>
                  <SelectItem value="oldest">Oldest First</SelectItem>
                  <SelectItem value="price_asc">Price: Low to High</SelectItem>
                  <SelectItem value="price_desc">Price: High to Low</SelectItem>
                </SelectContent>
              </Select>
            </div>
          </div>

          {/* Product grid */}
          <ProductGrid products={products} isLoading={isLoading} />

          {/* Pagination */}
          {pagination && pagination.total_pages > 1 && (
            <div className="flex items-center justify-center gap-1 mt-8 pb-4">
              <Button
                variant="ghost"
                size="icon"
                className="h-7 w-7 text-muted-foreground"
                onClick={() => setPage(1)}
                disabled={page <= 1}
              >
                <ChevronsLeft className="h-3.5 w-3.5" />
              </Button>
              <Button
                variant="outline"
                size="icon"
                className="h-8 w-8 ml-1"
                onClick={() => setPage((p) => Math.max(1, p - 1))}
                disabled={page <= 1}
              >
                <ChevronLeft className="h-4 w-4" />
              </Button>

              <span className="text-sm text-muted-foreground px-3 min-w-[60px] text-center">
                {page} / {pagination.total_pages}
              </span>

              <Button
                variant="outline"
                size="icon"
                className="h-8 w-8 mr-1"
                onClick={() => setPage((p) => Math.min(pagination.total_pages, p + 1))}
                disabled={page >= pagination.total_pages}
              >
                <ChevronRight className="h-4 w-4" />
              </Button>
              <Button
                variant="ghost"
                size="icon"
                className="h-7 w-7 text-muted-foreground"
                onClick={() => setPage(pagination.total_pages)}
                disabled={page >= pagination.total_pages}
              >
                <ChevronsRight className="h-3.5 w-3.5" />
              </Button>
            </div>
          )}
        </div>
        <Footer />
      </main>
    </div>
  );
}
