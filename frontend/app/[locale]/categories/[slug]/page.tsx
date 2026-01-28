'use client';

import { use, useCallback } from 'react';
import Link from 'next/link';
import { useSearchParams } from 'next/navigation';
import { useRouter } from '@/i18n/navigation';
import { useCategoryWithProducts } from '@/hooks/use-categories';
import { ProductGrid } from '@/components/products/product-grid';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import {
  Breadcrumb,
  BreadcrumbItem,
  BreadcrumbLink,
  BreadcrumbList,
  BreadcrumbPage,
  BreadcrumbSeparator,
} from '@/components/ui/breadcrumb';
import { ChevronLeft, ChevronRight, ChevronsLeft, ChevronsRight } from 'lucide-react';

interface CategoryPageProps {
  params: Promise<{ slug: string }>;
}

export default function CategoryPage({ params }: CategoryPageProps) {
  const { slug } = use(params);
  const router = useRouter();
  const searchParams = useSearchParams();
  const page = Number(searchParams.get('page')) || 1;
  const { data, isLoading } = useCategoryWithProducts(slug, page, 40);

  // Navigate to a specific page
  const navigateToPage = useCallback(
    (newPage: number) => {
      const params = new URLSearchParams();
      if (newPage > 1) params.set('page', String(newPage));
      const queryString = params.toString();
      router.push(`/categories/${slug}${queryString ? `?${queryString}` : ''}`, { scroll: true });
    },
    [router, slug],
  );

  const category = data?.data?.category;
  const products = data?.data?.products?.items || [];
  const pagination = data?.data?.products?.pagination;

  return (
    <div className="container mx-auto px-4 py-6">
      {/* Breadcrumb */}
      <Breadcrumb className="mb-6">
        <BreadcrumbList>
          <BreadcrumbItem>
            <BreadcrumbLink asChild>
              <Link href="/">Home</Link>
            </BreadcrumbLink>
          </BreadcrumbItem>
          <BreadcrumbSeparator />
          <BreadcrumbItem>
            <BreadcrumbLink asChild>
              <Link href="/">Categories</Link>
            </BreadcrumbLink>
          </BreadcrumbItem>
          <BreadcrumbSeparator />
          <BreadcrumbItem>
            <BreadcrumbPage>{category?.name || slug}</BreadcrumbPage>
          </BreadcrumbItem>
        </BreadcrumbList>
      </Breadcrumb>

      {/* Category header */}
      <div className="mb-8">
        <h1 className="text-3xl font-bold mb-2">{category?.name}</h1>
        {category?.description && (
          <p className="text-muted-foreground">{category.description}</p>
        )}
      </div>

      {/* Subcategories */}
      {category?.children && category.children.length > 0 && (
        <div className="mb-6">
          <h2 className="text-sm font-medium text-muted-foreground mb-3">
            Subcategories
          </h2>
          <div className="flex flex-wrap gap-2">
            {category.children.map((child) => (
              <Link key={child.id} href={`/categories/${child.slug}`}>
                <Badge variant="secondary" className="cursor-pointer hover:bg-accent">
                  {child.name}
                </Badge>
              </Link>
            ))}
          </div>
        </div>
      )}

      {/* Results info */}
      {pagination && (
        <p className="text-sm text-muted-foreground mb-4">
          Showing {products.length} of {pagination.total} products
        </p>
      )}

      {/* Product grid */}
      <ProductGrid products={products} isLoading={isLoading} />

      {/* Pagination */}
      {pagination && pagination.total_pages > 1 && (
        <div className="flex items-center justify-center gap-1 mt-8">
          <Button
            variant="ghost"
            size="icon"
            className="h-7 w-7 text-muted-foreground"
            onClick={() => navigateToPage(1)}
            disabled={page <= 1}
          >
            <ChevronsLeft className="h-3.5 w-3.5" />
          </Button>
          <Button
            variant="outline"
            size="icon"
            className="h-8 w-8 ml-1"
            onClick={() => navigateToPage(Math.max(1, page - 1))}
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
            onClick={() => navigateToPage(Math.min(pagination.total_pages, page + 1))}
            disabled={page >= pagination.total_pages}
          >
            <ChevronRight className="h-4 w-4" />
          </Button>
          <Button
            variant="ghost"
            size="icon"
            className="h-7 w-7 text-muted-foreground"
            onClick={() => navigateToPage(pagination.total_pages)}
            disabled={page >= pagination.total_pages}
          >
            <ChevronsRight className="h-3.5 w-3.5" />
          </Button>
        </div>
      )}
    </div>
  );
}
