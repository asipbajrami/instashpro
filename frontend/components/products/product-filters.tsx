'use client';

import { useState, useEffect } from 'react';
import { motion, AnimatePresence } from 'framer-motion';
import { useCategories } from '@/hooks/use-categories';
import { getAttributeValues } from '@/lib/api';
import { Slider } from '@/components/ui/slider';
import { Checkbox } from '@/components/ui/checkbox';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Skeleton } from '@/components/ui/skeleton';
import { Input } from '@/components/ui/input';
import { Collapsible, CollapsibleContent, CollapsibleTrigger } from '@/components/ui/collapsible';
import { ChevronDown, ChevronRight, ChevronUp, RotateCcw, Search, Loader2, DollarSign, ChevronsDownUp, ChevronsUpDown } from 'lucide-react';
import { Switch } from '@/components/ui/switch';
import { Category, Facets } from '@/lib/types';
import { cn } from '@/lib/utils';
import { useGroup } from '@/components/providers/group-provider';
import { formatPrice } from '@/lib/currency';

export interface FilterState {
  category?: string;
  category_id?: number;
  min_price?: number;
  max_price?: number;
  attributes?: Record<string, string[]>;
  type?: string;
  currency?: string;
  profile?: string;
  sort?: 'newest' | 'oldest' | 'price_asc' | 'price_desc';
  has_price?: boolean;
}

interface ProductFiltersProps {
  filters: FilterState;
  onFiltersChange: (filters: FilterState) => void;
  facets?: Facets;
  className?: string;
}

export function ProductFilters({
  filters,
  onFiltersChange,
  facets,
  className,
}: ProductFiltersProps) {
  const { selectedGroup } = useGroup();
  const { data: categoriesData, isLoading: categoriesLoading } = useCategories(selectedGroup || undefined);
  const [priceRange, setPriceRange] = useState<[number, number]>([0, 1000]);
  const [expandedCategories, setExpandedCategories] = useState<Set<number>>(new Set());
  const [initialized, setInitialized] = useState(false);

  // State for expanded attribute values (show more)
  const [expandedAttributes, setExpandedAttributes] = useState<Record<number, {
    values: { id: number; value: string; is_temp?: boolean }[];
    loading: boolean;
    search: string;
  }>>({});

  // State for collapsed filter sections (all collapsed by default)
  const [expandedSections, setExpandedSections] = useState<Set<string>>(new Set());

  const toggleSection = (sectionId: string) => {
    setExpandedSections(prev => {
      const newSet = new Set(prev);
      if (newSet.has(sectionId)) {
        newSet.delete(sectionId);
      } else {
        newSet.add(sectionId);
      }
      return newSet;
    });
  };

  // Get all available section IDs
  const getAllSectionIds = (): string[] => {
    const sections: string[] = [];
    
    // Always available sections
    sections.push('categories');
    
    // Conditional sections
    if (facets?.price_range) sections.push('price');
    if (facets?.types && facets.types.length > 0) sections.push('types');
    if (facets?.currencies && facets.currencies.length > 1) sections.push('currency');
    if (facets?.profiles && facets.profiles.length > 0) sections.push('profiles');
    
    // Attribute sections
    if (facets?.attributes) {
      facets.attributes.forEach(attr => {
        sections.push(`attr-${attr.id}`);
      });
    }
    
    return sections;
  };

  // Check if all sections are expanded
  const areAllExpanded = () => {
    const allSections = getAllSectionIds();
    return allSections.length > 0 && allSections.every(id => expandedSections.has(id));
  };

  // Check if any section is expanded
  const hasAnyExpanded = () => {
    return expandedSections.size > 0;
  };

  // Expand all sections
  const expandAll = () => {
    const allSections = getAllSectionIds();
    setExpandedSections(new Set(allSections));
  };

  // Collapse all sections
  const collapseAll = () => {
    setExpandedSections(new Set());
  };

  // Toggle all sections
  const toggleAll = () => {
    if (hasAnyExpanded()) {
      collapseAll();
    } else {
      expandAll();
    }
  };

  // Categories start collapsed - user can expand as needed
  useEffect(() => {
    if (categoriesData?.data && !initialized) {
      setInitialized(true);
    }
  }, [categoriesData?.data, initialized]);

  useEffect(() => {
    if (facets?.price_range) {
      setPriceRange([
        filters.min_price ?? facets.price_range.min,
        filters.max_price ?? facets.price_range.max,
      ]);
    }
  }, [facets?.price_range, filters.min_price, filters.max_price]);

  const handleCategoryChange = (slug: string, id: number) => {
    // Clear expanded attributes when category changes (values will be different)
    setExpandedAttributes({});
    onFiltersChange({
      ...filters,
      category: filters.category === slug ? undefined : slug,
      category_id: filters.category === slug ? undefined : id,
    });
  };

  const handlePriceChange = (value: number[]) => {
    setPriceRange([value[0], value[1]]);
  };

  const handlePriceCommit = (value: number[]) => {
    onFiltersChange({
      ...filters,
      min_price: value[0],
      max_price: value[1],
    });
  };

  const handleMinPriceInput = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = parseFloat(e.target.value) || 0;
    const newRange: [number, number] = [
      Math.max(facets?.price_range?.min || 0, Math.min(value, priceRange[1])),
      priceRange[1]
    ];
    setPriceRange(newRange);
    onFiltersChange({
      ...filters,
      min_price: newRange[0],
      max_price: newRange[1],
    });
  };

  const handleMaxPriceInput = (e: React.ChangeEvent<HTMLInputElement>) => {
    const value = parseFloat(e.target.value) || facets?.price_range?.max || 1000;
    const newRange: [number, number] = [
      priceRange[0],
      Math.min(facets?.price_range?.max || 1000, Math.max(value, priceRange[0]))
    ];
    setPriceRange(newRange);
    onFiltersChange({
      ...filters,
      min_price: newRange[0],
      max_price: newRange[1],
    });
  };

  const handleAttributeChange = (attributeName: string, value: string, checked: boolean) => {
    const currentAttributes = filters.attributes || {};
    const currentValues = currentAttributes[attributeName] || [];

    let newValues: string[];
    if (checked) {
      newValues = [...currentValues, value];
    } else {
      newValues = currentValues.filter((v) => v !== value);
    }

    const newAttributes = { ...currentAttributes };
    if (newValues.length > 0) {
      newAttributes[attributeName] = newValues;
    } else {
      delete newAttributes[attributeName];
    }

    onFiltersChange({
      ...filters,
      attributes: Object.keys(newAttributes).length > 0 ? newAttributes : undefined,
    });
  };

  const handleTypeChange = (type: string, checked: boolean) => {
    onFiltersChange({
      ...filters,
      type: checked ? type : undefined,
    });
  };

  const handleProfileChange = (username: string, checked: boolean) => {
    onFiltersChange({
      ...filters,
      profile: checked ? username : undefined,
    });
  };

  const handleCurrencyChange = (currency: string, checked: boolean) => {
    onFiltersChange({
      ...filters,
      currency: checked ? currency : undefined,
    });
  };

  const handleHasPriceChange = (checked: boolean) => {
    onFiltersChange({
      ...filters,
      has_price: checked || undefined,
    });
  };

  const clearFilters = () => {
    onFiltersChange({});
    if (facets?.price_range) {
      setPriceRange([facets.price_range.min, facets.price_range.max]);
    }
  };

  const activeFilterCount = [
    filters.category,
    filters.min_price !== undefined || filters.max_price !== undefined,
    filters.type,
    filters.currency,
    filters.profile,
    filters.has_price,
    filters.attributes && Object.keys(filters.attributes).length > 0,
  ].filter(Boolean).length;

  const toggleCategory = (categoryId: number) => {
    const newExpanded = new Set(expandedCategories);
    if (newExpanded.has(categoryId)) {
      newExpanded.delete(categoryId);
    } else {
      newExpanded.add(categoryId);
    }
    setExpandedCategories(newExpanded);
  };

  // Fetch all values for an attribute (show more)
  const handleShowMore = async (attributeId: number) => {
    // If already expanded, collapse it
    if (expandedAttributes[attributeId]) {
      setExpandedAttributes((prev) => {
        const newState = { ...prev };
        delete newState[attributeId];
        return newState;
      });
      return;
    }

    // Set loading state
    setExpandedAttributes((prev) => ({
      ...prev,
      [attributeId]: { values: [], loading: true, search: '' },
    }));

    try {
      // Pass category to filter values by products in that category
      const response = await getAttributeValues(attributeId, undefined, filters.category);
      setExpandedAttributes((prev) => ({
        ...prev,
        [attributeId]: {
          values: response.data.values,
          loading: false,
          search: '',
        },
      }));
    } catch (error) {
      console.error('Failed to fetch attribute values:', error);
      setExpandedAttributes((prev) => {
        const newState = { ...prev };
        delete newState[attributeId];
        return newState;
      });
    }
  };

  // Search within expanded attribute values
  const handleAttributeSearch = async (attributeId: number, search: string) => {
    setExpandedAttributes((prev) => ({
      ...prev,
      [attributeId]: { ...prev[attributeId], search, loading: true },
    }));

    try {
      // Pass category to filter values by products in that category
      const response = await getAttributeValues(attributeId, search || undefined, filters.category);
      setExpandedAttributes((prev) => ({
        ...prev,
        [attributeId]: {
          ...prev[attributeId],
          values: response.data.values,
          loading: false,
        },
      }));
    } catch (error) {
      console.error('Failed to search attribute values:', error);
      setExpandedAttributes((prev) => ({
        ...prev,
        [attributeId]: { ...prev[attributeId], loading: false },
      }));
    }
  };

  const renderCategoryTree = (categories: Category[], level = 0) => {
    return categories.map((category) => {
      const hasChildren = category.children && category.children.length > 0;
      const isExpanded = expandedCategories.has(category.id);
      const isSelected = filters.category === category.slug;

      return (
        <div key={category.id}>
          <div
            className={cn(
              'flex items-center gap-1.5 py-1.5 px-2 rounded-md cursor-pointer transition-all group',
              isSelected
                ? 'bg-primary text-primary-foreground'
                : 'hover:bg-muted',
            )}
            style={{ marginLeft: `${level * 12}px` }}
            onClick={() => handleCategoryChange(category.slug, category.id)}
          >
            {hasChildren && (
              <button
                onClick={(e) => {
                  e.stopPropagation();
                  toggleCategory(category.id);
                }}
                className={cn(
                  'p-0.5 rounded transition-colors',
                  isSelected ? 'hover:bg-primary-foreground/20' : 'hover:bg-muted-foreground/20'
                )}
              >
                {isExpanded ? (
                  <ChevronDown className={cn('h-3.5 w-3.5', isSelected ? 'text-primary-foreground' : 'text-muted-foreground')} />
                ) : (
                  <ChevronRight className={cn('h-3.5 w-3.5', isSelected ? 'text-primary-foreground' : 'text-muted-foreground')} />
                )}
              </button>
            )}
            <span className={cn(
              'text-sm flex-1',
              isSelected && 'font-medium',
              !hasChildren && 'ml-5'
            )}>
              {category.name}
            </span>
            {category.product_count > 0 && (
              <span className={cn(
                'text-xs tabular-nums',
                isSelected ? 'text-primary-foreground/70' : 'text-muted-foreground'
              )}>
                {category.product_count}
              </span>
            )}
          </div>
          {hasChildren && isExpanded && (
            <div className="mt-0.5 border-l border-border/50 ml-3 animate-slide-in-right">
              {renderCategoryTree(category.children!, level + 1)}
            </div>
          )}
        </div>
      );
    });
  };

  const allSections = getAllSectionIds();
  const isAllExpanded = areAllExpanded();
  const hasExpanded = hasAnyExpanded();

  return (
    <div className={cn('space-y-2', className)}>
      {/* Filters header with expand/collapse */}
      {allSections.length > 0 && (
        <div className="flex items-center justify-between -mx-3 px-4 sm:px-6 lg:px-8">
          <h3 className="text-xs font-medium text-muted-foreground uppercase tracking-wider">
            Filters
          </h3>
          <Button
            variant="ghost"
            size="sm"
            onClick={toggleAll}
            className="h-7 px-2 text-xs text-muted-foreground hover:text-foreground"
            title={hasExpanded ? "Collapse All" : "Expand All"}
          >
            {hasExpanded ? (
              <>
                <ChevronsDownUp className="h-3 w-3 mr-1.5" />
                Collapse
              </>
            ) : (
              <>
                <ChevronsUpDown className="h-3 w-3 mr-1.5" />
                Expand
              </>
            )}
          </Button>
        </div>
      )}

      {/* Categories */}
      <Collapsible
        open={expandedSections.has('categories')}
        onOpenChange={() => toggleSection('categories')}
      >
        <CollapsibleTrigger asChild>
          <button className="flex items-center justify-between w-full text-left py-1.5 -mx-3 px-4 sm:px-6 lg:px-8 rounded-lg hover:bg-muted/50 transition-colors">
            <h3 className="text-sm font-medium text-foreground">
              Categories
            </h3>
            <ChevronDown className={cn(
              "h-4 w-4 text-muted-foreground transition-transform duration-300 ease-out",
              expandedSections.has('categories') && "rotate-180"
            )} />
          </button>
        </CollapsibleTrigger>
        <CollapsibleContent>
          {categoriesLoading ? (
            <div className="space-y-2 pt-2 -mx-3 px-4 sm:px-6 lg:px-8">
              {Array.from({ length: 4 }).map((_, i) => (
                <Skeleton key={i} className="h-9 w-full rounded-lg" />
              ))}
            </div>
          ) : (
            <div className="space-y-0.5 max-h-[300px] overflow-y-auto pr-0 scrollbar-hide pt-2 -mx-3 px-4 sm:px-6 lg:px-8">
              {categoriesData?.data?.[0]?.children && renderCategoryTree(categoriesData.data[0].children)}
            </div>
          )}
        </CollapsibleContent>
      </Collapsible>

      {/* Price Range */}
      {facets?.price_range && (
        <Collapsible
          open={expandedSections.has('price')}
          onOpenChange={() => toggleSection('price')}
        >
          <CollapsibleTrigger asChild>
            <button className="flex items-center justify-between w-full text-left py-1.5 -mx-3 px-4 sm:px-6 lg:px-8 rounded-lg hover:bg-muted/50 transition-colors">
              <h3 className="text-sm font-medium text-foreground">
                Price Range
              </h3>
              <ChevronDown className={cn(
                "h-4 w-4 text-muted-foreground transition-transform duration-300 ease-out",
                expandedSections.has('price') && "rotate-180"
              )} />
            </button>
          </CollapsibleTrigger>
          <CollapsibleContent>
            <div className="bg-muted/30 rounded-xl p-4 space-y-4 mt-2 -mx-3 px-4 sm:px-6 lg:px-8">
              {/* Has Price Toggle */}
              <label
                className={cn(
                  "flex items-center justify-between gap-2 px-3 py-2 rounded-lg cursor-pointer transition-all border",
                  filters.has_price
                    ? "bg-emerald-50 border-emerald-300 dark:bg-emerald-950/30 dark:border-emerald-600"
                    : "bg-background border-transparent hover:bg-muted/50"
                )}
              >
                <div className="flex items-center gap-2">
                  <DollarSign className={cn("h-4 w-4", filters.has_price ? "text-emerald-600" : "text-muted-foreground")} />
                  <span className="text-sm">With price only</span>
                </div>
                <Switch
                  checked={filters.has_price ?? false}
                  onCheckedChange={handleHasPriceChange}
                />
              </label>
              <Slider
                value={priceRange}
                min={facets.price_range.min}
                max={facets.price_range.max}
                step={1}
                onValueChange={handlePriceChange}
                onValueCommit={handlePriceCommit}
                className="mb-4"
              />
              <div className="flex items-center justify-between gap-2">
                <Input
                  type="number"
                  value={priceRange[0]}
                  onChange={handleMinPriceInput}
                  min={facets?.price_range?.min || 0}
                  max={priceRange[1]}
                  step="1"
                  className="bg-background border rounded-lg px-3 py-2 text-xs font-medium flex-1 text-center h-auto [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                />
                <span className="text-muted-foreground text-xs">to</span>
                <Input
                  type="number"
                  value={priceRange[1]}
                  onChange={handleMaxPriceInput}
                  min={priceRange[0]}
                  max={facets?.price_range?.max || 1000}
                  step="1"
                  className="bg-background border rounded-lg px-3 py-2 text-xs font-medium flex-1 text-center h-auto [&::-webkit-inner-spin-button]:appearance-none [&::-webkit-outer-spin-button]:appearance-none"
                />
              </div>
            </div>
          </CollapsibleContent>
        </Collapsible>
      )}

      {/* Product Types */}
      {facets?.types && facets.types.length > 0 && (
        <Collapsible
          open={expandedSections.has('types')}
          onOpenChange={() => toggleSection('types')}
        >
          <CollapsibleTrigger asChild>
            <button className="flex items-center justify-between w-full text-left py-1.5 -mx-3 px-4 sm:px-6 lg:px-8 rounded-lg hover:bg-muted/50 transition-colors">
              <h3 className="text-sm font-medium text-foreground">
                Product Type
              </h3>
              <ChevronDown className={cn(
                "h-4 w-4 text-muted-foreground transition-transform duration-300 ease-out",
                expandedSections.has('types') && "rotate-180"
              )} />
            </button>
          </CollapsibleTrigger>
          <CollapsibleContent>
            <div className="space-y-0.5 pt-2 -mx-3 px-4 sm:px-6 lg:px-8">
              {facets.types.map((type) => (
                <label
                  key={type.value}
                  className={cn(
                    'flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer transition-all border',
                    filters.type === type.value
                      ? 'bg-primary/10 border-primary/30'
                      : 'bg-transparent border-transparent hover:bg-muted/50'
                  )}
                >
                  <Checkbox
                    checked={filters.type === type.value}
                    onCheckedChange={(checked) =>
                      handleTypeChange(type.value, checked === true)
                    }
                  />
                  <span className="text-sm flex-1 capitalize">{type.value}</span>
                  <span className="text-xs text-muted-foreground tabular-nums">
                    {type.count}
                  </span>
                </label>
              ))}
            </div>
          </CollapsibleContent>
        </Collapsible>
      )}

      {/* Currency */}
      {facets?.currencies && facets.currencies.length > 1 && (
        <Collapsible
          open={expandedSections.has('currency')}
          onOpenChange={() => toggleSection('currency')}
        >
          <CollapsibleTrigger asChild>
            <button className="flex items-center justify-between w-full text-left py-1.5 -mx-3 px-4 sm:px-6 lg:px-8 rounded-lg hover:bg-muted/50 transition-colors">
              <h3 className="text-sm font-medium text-foreground">
                Currency
              </h3>
              <ChevronDown className={cn(
                "h-4 w-4 text-muted-foreground transition-transform duration-300 ease-out",
                expandedSections.has('currency') && "rotate-180"
              )} />
            </button>
          </CollapsibleTrigger>
          <CollapsibleContent>
            <div className="space-y-0.5 pt-2 -mx-3 px-4 sm:px-6 lg:px-8">
              {facets.currencies.map((currency) => (
                <label
                  key={currency.value}
                  className={cn(
                    'flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer transition-all border',
                    filters.currency === currency.value
                      ? 'bg-primary/10 border-primary/30'
                      : 'bg-transparent border-transparent hover:bg-muted/50'
                  )}
                >
                  <Checkbox
                    checked={filters.currency === currency.value}
                    onCheckedChange={(checked) =>
                      handleCurrencyChange(currency.value, checked === true)
                    }
                  />
                  <span className="text-sm flex-1">{currency.label}</span>
                  <span className="text-xs text-muted-foreground tabular-nums">
                    {currency.count}
                  </span>
                </label>
              ))}
            </div>
          </CollapsibleContent>
        </Collapsible>
      )}

      {/* Instagram Profiles */}
      {facets?.profiles && facets.profiles.length > 0 && (
        <Collapsible
          open={expandedSections.has('profiles')}
          onOpenChange={() => toggleSection('profiles')}
        >
          <CollapsibleTrigger asChild>
            <button className="flex items-center justify-between w-full text-left py-1.5 -mx-3 px-4 sm:px-6 lg:px-8 rounded-lg hover:bg-muted/50 transition-colors">
              <h3 className="text-sm font-medium text-foreground">
                Shop / Seller
              </h3>
              <ChevronDown className={cn(
                "h-4 w-4 text-muted-foreground transition-transform duration-300 ease-out",
                expandedSections.has('profiles') && "rotate-180"
              )} />
            </button>
          </CollapsibleTrigger>
          <CollapsibleContent>
            <div className="space-y-0.5 max-h-[250px] overflow-y-auto pr-0 scrollbar-hide pt-2 -mx-3 px-4 sm:px-6 lg:px-8">
              {facets.profiles.map((profile) => (
                <label
                  key={profile.id}
                  className={cn(
                    'flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer transition-all border',
                    filters.profile === profile.username
                      ? 'bg-primary/10 border-primary/30'
                      : 'bg-transparent border-transparent hover:bg-muted/50'
                  )}
                >
                  <Checkbox
                    checked={filters.profile === profile.username}
                    onCheckedChange={(checked) =>
                      handleProfileChange(profile.username, checked === true)
                    }
                  />
                  <div className="flex items-center gap-2 flex-1 min-w-0">
                    {profile.profile_pic_url ? (
                      <img
                        src={profile.profile_pic_url}
                        alt={profile.username}
                        className="w-6 h-6 rounded-full object-cover flex-shrink-0"
                      />
                    ) : (
                      <div className="w-6 h-6 rounded-full bg-muted flex-shrink-0" />
                    )}
                    <span className="text-sm truncate">
                      @{profile.username}
                      {profile.is_verified && (
                        <span className="ml-1 text-blue-500">✓</span>
                      )}
                    </span>
                  </div>
                  <span className="text-xs text-muted-foreground tabular-nums flex-shrink-0">
                    {profile.product_count}
                  </span>
                </label>
              ))}
            </div>
          </CollapsibleContent>
        </Collapsible>
      )}

      {/* Attributes - filtered by group on the API side */}
      {facets?.attributes && facets.attributes.map((attribute) => {
        const sectionKey = `attr-${attribute.id}`;
        const isSectionExpanded = expandedSections.has(sectionKey);
        const isValuesExpanded = !!expandedAttributes[attribute.id];
        const expandedData = expandedAttributes[attribute.id];
        // Show only 4 values initially, all when expanded
        const INITIAL_VALUES_COUNT = 4;
        const displayValues = isValuesExpanded
          ? expandedData.values
          : attribute.values.slice(0, INITIAL_VALUES_COUNT);

        return (
          <Collapsible
            key={attribute.id}
            open={isSectionExpanded}
            onOpenChange={() => toggleSection(sectionKey)}
          >
            <CollapsibleTrigger asChild>
              <button className="flex items-center justify-between w-full text-left py-1.5 -mx-3 px-4 sm:px-6 lg:px-8 rounded-lg hover:bg-muted/50 transition-colors">
                <h3 className="text-sm font-medium text-foreground">
                  {attribute.name}
                </h3>
                <ChevronDown className={cn(
                  "h-4 w-4 text-muted-foreground transition-transform duration-300 ease-out",
                  isSectionExpanded && "rotate-180"
                )} />
              </button>
            </CollapsibleTrigger>

            <CollapsibleContent>
              <div className="pt-2 -mx-3 px-4 sm:px-6 lg:px-8">
                {/* Search input when values expanded */}
                <AnimatePresence>
                  {isValuesExpanded && (
                    <motion.div
                      initial={{ height: 0, opacity: 0 }}
                      animate={{ height: "auto", opacity: 1 }}
                      exit={{ height: 0, opacity: 0 }}
                      transition={{
                        height: { duration: 0.25, ease: [0.16, 1, 0.3, 1] },
                        opacity: { duration: 0.2, ease: "easeOut" }
                      }}
                      className="relative mb-2 overflow-hidden"
                    >
                      <div className="relative">
                        <Search className="absolute left-2.5 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                        <Input
                          placeholder={`Search ${attribute.name.toLowerCase()}...`}
                          value={expandedData.search}
                          onChange={(e) => handleAttributeSearch(attribute.id, e.target.value)}
                          className="pl-8 h-9 text-sm rounded-lg"
                        />
                      </div>
                    </motion.div>
                  )}
                </AnimatePresence>

                <div className="space-y-0.5 max-h-[200px] overflow-y-auto pr-0 scrollbar-hide">
                  {expandedData?.loading ? (
                    <div className="flex items-center justify-center py-4">
                      <Loader2 className="h-5 w-5 animate-spin text-muted-foreground" />
                    </div>
                  ) : (
                    displayValues.map((attrValue) => {
                      const isChecked =
                        filters.attributes?.[attribute.name]?.includes(attrValue.value) ?? false;
                      const isTemp = 'is_temp' in attrValue && attrValue.is_temp;
                      return (
                        <label
                          key={attrValue.id}
                          className={cn(
                            'flex items-center gap-3 px-3 py-2 rounded-lg cursor-pointer transition-all border',
                            isChecked
                              ? 'bg-primary/10 border-primary/30'
                              : 'bg-transparent border-transparent hover:bg-muted/50 hover:border-muted',
                            isTemp && 'opacity-60'
                          )}
                        >
                          <Checkbox
                            checked={isChecked}
                            onCheckedChange={(checked) =>
                              handleAttributeChange(attribute.name, attrValue.value, checked === true)
                            }
                          />
                          <span className={cn('text-sm flex-1', isTemp && 'italic')}>
                            {attrValue.value}
                          </span>
                          {'count' in attrValue && (
                            <span className="text-xs text-muted-foreground tabular-nums">
                              {(attrValue as { count: number }).count}
                            </span>
                          )}
                        </label>
                      );
                    })
                  )}
                </div>

                {/* Show more / Show less button */}
                <Button
                  variant="ghost"
                  size="sm"
                  onClick={() => handleShowMore(attribute.id)}
                  className="w-full text-xs text-muted-foreground hover:text-foreground h-8 mt-1"
                  disabled={expandedData?.loading}
                >
                  {expandedData?.loading ? (
                    <>
                      <Loader2 className="h-3 w-3 mr-1.5 animate-spin" />
                      Loading...
                    </>
                  ) : isValuesExpanded ? (
                    <>
                      <ChevronUp className="h-3 w-3 mr-1.5" />
                      Show less
                    </>
                  ) : (
                    <>
                      <ChevronDown className="h-3 w-3 mr-1.5" />
                      Show all
                    </>
                  )}
                </Button>
              </div>
            </CollapsibleContent>
          </Collapsible>
        );
      })}
    </div>
  );
}
