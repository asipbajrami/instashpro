'use client';

import { Button } from '@/components/ui/button';
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';
import { SlidersHorizontal } from 'lucide-react';
import { ProductFilters, FilterState } from '@/components/products/product-filters';
import { Facets } from '@/lib/types';
import { useState } from 'react';

interface FilterSheetProps {
  filters: FilterState;
  onFiltersChange: (filters: FilterState) => void;
  facets?: Facets;
}

export function FilterSheet({ filters, onFiltersChange, facets }: FilterSheetProps) {
  const [open, setOpen] = useState(false);

  const activeFilterCount = [
    filters.category,
    filters.min_price !== undefined || filters.max_price !== undefined,
    filters.type,
    filters.attributes && Object.keys(filters.attributes).length > 0,
  ].filter(Boolean).length;

  return (
    <Sheet open={open} onOpenChange={setOpen}>
      <SheetTrigger asChild>
        <Button variant="outline" size="sm" className="lg:hidden">
          <SlidersHorizontal className="h-4 w-4 mr-2" />
          Filters
          {activeFilterCount > 0 && (
            <span className="ml-2 bg-primary text-primary-foreground rounded-full w-5 h-5 text-xs flex items-center justify-center">
              {activeFilterCount}
            </span>
          )}
        </Button>
      </SheetTrigger>
      <SheetContent side="left" className="w-[300px] sm:w-[350px]">
        <SheetHeader>
          <SheetTitle>Filters</SheetTitle>
        </SheetHeader>
        <div className="mt-1 overflow-y-auto h-[calc(100vh-100px)] px-4">
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
