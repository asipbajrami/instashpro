'use client';

import Link from 'next/link';
import { ChevronRight } from 'lucide-react';
import { useCategories } from '@/hooks/use-categories';
import { Category } from '@/lib/types';
import { Skeleton } from '@/components/ui/skeleton';
import { cn } from '@/lib/utils';

interface CategoryNavProps {
  onSelect?: () => void;
}

function CategoryItem({ category, level = 0, onSelect }: { category: Category; level?: number; onSelect?: () => void }) {
  const hasChildren = category.children && category.children.length > 0;

  return (
    <div>
      <Link
        href={`/categories/${category.slug}`}
        onClick={onSelect}
        className={cn(
          "flex items-center justify-between py-2 px-3 rounded-md hover:bg-accent transition-colors",
          level > 0 && "ml-4"
        )}
      >
        <span className="text-sm">{category.name}</span>
        <div className="flex items-center gap-2">
          <span className="text-xs text-muted-foreground">{category.product_count}</span>
          {hasChildren && <ChevronRight className="h-4 w-4 text-muted-foreground" />}
        </div>
      </Link>
      {hasChildren && (
        <div className="ml-2 border-l">
          {category.children.map((child) => (
            <CategoryItem key={child.id} category={child} level={level + 1} onSelect={onSelect} />
          ))}
        </div>
      )}
    </div>
  );
}

export function CategoryNav({ onSelect }: CategoryNavProps) {
  const { data, isLoading, error } = useCategories();

  if (isLoading) {
    return (
      <div className="space-y-2">
        {[...Array(5)].map((_, i) => (
          <Skeleton key={i} className="h-10 w-full" />
        ))}
      </div>
    );
  }

  if (error || !data?.data) {
    return <div className="text-sm text-muted-foreground">Failed to load categories</div>;
  }

  return (
    <nav className="space-y-1">
      {data.data.map((category) => (
        <CategoryItem key={category.id} category={category} onSelect={onSelect} />
      ))}
    </nav>
  );
}
