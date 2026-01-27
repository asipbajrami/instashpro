'use client';

import { Button } from '@/components/ui/button';
import {
  Sheet,
  SheetContent,
  SheetHeader,
  SheetTitle,
  SheetTrigger,
} from '@/components/ui/sheet';
import { SlidersHorizontal, Car, Smartphone } from 'lucide-react';
import { ProductFilters, FilterState } from '@/components/products/product-filters';
import { Facets } from '@/lib/types';
import { useState } from 'react';
import { useRouter } from '@/i18n/navigation';
import { useGroup, ProductGroup } from '@/components/providers/group-provider';
import { cn } from '@/lib/utils';
import { useTranslations } from 'next-intl';

interface FilterSheetProps {
  filters: FilterState;
  onFiltersChange: (filters: FilterState) => void;
  facets?: Facets;
}

export function FilterSheet({ filters, onFiltersChange, facets }: FilterSheetProps) {
  const [open, setOpen] = useState(false);
  const router = useRouter();
  const { selectedGroup, setSelectedGroup } = useGroup();
  const t = useTranslations('filters');
  const tGroups = useTranslations('groups');

  const groupConfig: Record<NonNullable<ProductGroup>, { label: string; icon: React.ReactNode; smallIcon: React.ReactNode; color: string; bgColor: string }> = {
    car: { label: tGroups('car.label'), icon: <Car className="h-4 w-4" />, smallIcon: <Car className="h-2.5 w-2.5" />, color: 'text-blue-600', bgColor: 'bg-blue-100 dark:bg-blue-900/50' },
    tech: { label: tGroups('tech.label'), icon: <Smartphone className="h-4 w-4" />, smallIcon: <Smartphone className="h-2.5 w-2.5" />, color: 'text-purple-600', bgColor: 'bg-purple-100 dark:bg-purple-900/50' },
  };

  const currentGroup = selectedGroup ? groupConfig[selectedGroup] : null;

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
        <Button variant="outline" size="sm" className="lg:hidden h-10 px-3 gap-1.5 bg-muted border-foreground/30 hover:bg-accent shadow-sm dark:bg-white/10 dark:border-white/30 dark:hover:bg-white/20">
          <SlidersHorizontal className="h-4 w-4 text-foreground/80" />
          <span className="text-sm font-medium">{t('title')}</span>
          {activeFilterCount > 0 && (
            <span className="bg-primary text-primary-foreground rounded-full min-w-[18px] h-[18px] px-1 text-[10px] flex items-center justify-center">
              {activeFilterCount}
            </span>
          )}
          {currentGroup && (
            <span className={cn(
              "rounded-full p-0.5",
              currentGroup.bgColor,
              currentGroup.color
            )}>
              {currentGroup.smallIcon}
            </span>
          )}
        </Button>
      </SheetTrigger>
      <SheetContent side="left" className="w-[300px] sm:w-[350px] flex flex-col gap-0">
        <SheetHeader className="pb-2">
          <SheetTitle>{t('title')}</SheetTitle>
        </SheetHeader>

        {/* Group Switcher */}
        <div className="px-4 pb-3 border-b">
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
