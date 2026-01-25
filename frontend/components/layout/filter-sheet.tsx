'use client';

import { Button } from '@/components/ui/button';
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';
import { SlidersHorizontal, Search, Moon, Sun, Car, Smartphone } from 'lucide-react';
import { ProductFilters, FilterState } from '@/components/products/product-filters';
import { Facets } from '@/lib/types';
import { useState } from 'react';
import { useTheme } from 'next-themes';
import { useRouter } from 'next/navigation';
import { useGroup, ProductGroup } from '@/components/providers/group-provider';
import { cn } from '@/lib/utils';
import Link from 'next/link';

interface FilterSheetProps {
  filters: FilterState;
  onFiltersChange: (filters: FilterState) => void;
  facets?: Facets;
}

const groupConfig: Record<NonNullable<ProductGroup>, { label: string; icon: React.ReactNode; color: string }> = {
  car: { label: 'Vehicles', icon: <Car className="h-4 w-4" />, color: 'text-blue-600' },
  tech: { label: 'Technology', icon: <Smartphone className="h-4 w-4" />, color: 'text-purple-600' },
};

export function FilterSheet({ filters, onFiltersChange, facets }: FilterSheetProps) {
  const [open, setOpen] = useState(false);
  const { theme, setTheme } = useTheme();
  const router = useRouter();
  const { selectedGroup, setSelectedGroup } = useGroup();

  const activeFilterCount = [
    filters.category,
    filters.min_price !== undefined || filters.max_price !== undefined,
    filters.type,
    filters.attributes && Object.keys(filters.attributes).length > 0,
  ].filter(Boolean).length;

  const handleGroupSwitch = (group: ProductGroup) => {
    setSelectedGroup(group);
    router.push('/');
  };

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>
        <Button variant="outline" size="icon" className="lg:hidden h-9 w-9 relative">
          <SlidersHorizontal className="h-5 w-5" />
          {activeFilterCount > 0 && (
            <span className="absolute -top-1 -right-1 bg-primary text-primary-foreground rounded-full w-4 h-4 text-[10px] flex items-center justify-center">
              {activeFilterCount}
            </span>
          )}
        </Button>
      </SheetTrigger>
      <SheetContent side="left" className="w-[300px] sm:w-[350px] flex flex-col">
        <SheetHeader>
          <SheetTitle>Filters</SheetTitle>
        </SheetHeader>

        {/* Quick Actions - Search & Theme */}
        <div className="flex items-center gap-2 px-4 py-3 border-b">
          <Link href="/search" className="flex-1" onClick={() => setOpen(false)}>
            <Button variant="outline" className="w-full justify-start gap-2">
              <Search className="h-4 w-4" />
              Search products...
            </Button>
          </Link>
          <Button
            variant="outline"
            size="icon"
            onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
          >
            <Sun className="h-4 w-4 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
            <Moon className="absolute h-4 w-4 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
          </Button>
        </div>

        {/* Group Switcher */}
        <div className="px-4 py-3 border-b">
          <p className="text-xs font-medium text-muted-foreground uppercase tracking-wider mb-2">
            Section
          </p>
          <div className="grid grid-cols-2 gap-2">
            {(Object.entries(groupConfig) as [NonNullable<ProductGroup>, typeof groupConfig.car][]).map(
              ([key, config]) => (
                <button
                  key={key}
                  onClick={() => {
                    handleGroupSwitch(key);
                    setOpen(false);
                  }}
                  className={cn(
                    'flex items-center gap-2 px-3 py-2.5 text-sm rounded-lg border transition-colors',
                    selectedGroup === key
                      ? 'bg-primary/10 border-primary/30 font-medium'
                      : 'hover:bg-muted border-transparent'
                  )}
                >
                  <span className={config.color}>{config.icon}</span>
                  <span>{config.label}</span>
                </button>
              )
            )}
          </div>
        </div>

        {/* Filters */}
        <div className="flex-1 overflow-y-auto px-4 pt-2">
          <ProductFilters
            filters={filters}
            onFiltersChange={(newFilters) => {
              onFiltersChange(newFilters);
            }}
            facets={facets}
          />
        </div>
      </SheetContent>
    </Sheet>
  );
}
