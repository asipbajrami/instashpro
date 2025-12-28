'use client';

import Link from 'next/link';
import { useState, useRef, useEffect, useCallback } from 'react';
import { Search, ShoppingBag, Car, Smartphone, ChevronDown, ChevronRight, Moon, Sun, SlidersHorizontal, X } from 'lucide-react';
import { useTheme } from 'next-themes';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { useRouter } from 'next/navigation';
import { useGroup, ProductGroup } from '@/components/providers/group-provider';
import { useCategories } from '@/hooks/use-categories';
import { Category } from '@/lib/types';
import { cn } from '@/lib/utils';
import { Badge } from '@/components/ui/badge';

const groupConfig: Record<NonNullable<ProductGroup>, { label: string; icon: React.ReactNode; color: string; bgColor: string }> = {
  car: { label: 'Vehicles', icon: <Car className="h-4 w-4" />, color: 'text-blue-600', bgColor: 'bg-blue-50 dark:bg-blue-950/30' },
  tech: { label: 'Technology', icon: <Smartphone className="h-4 w-4" />, color: 'text-purple-600', bgColor: 'bg-purple-50 dark:bg-purple-950/30' },
};

// Mega menu for categories with nested levels
function CategoryMegaMenu({
  category,
  onSelect,
  onCategoryClick,
}: {
  category: Category;
  onSelect: () => void;
  onCategoryClick: (categoryId: number) => void;
}) {
  const [selectedChild, setSelectedChild] = useState<Category | null>(null);
  const [selectedGrandchild, setSelectedGrandchild] = useState<Category | null>(null);

  const handleCategorySelect = (cat: Category) => {
    onCategoryClick(cat.id);
    onSelect();
  };

  return (
    <div className="flex">
      {/* Level 1 - Direct children */}
      <div className="min-w-[200px] max-h-[350px] overflow-y-auto border-r">
        <div className="p-2">
          <button
            onClick={() => handleCategorySelect(category)}
            className="block w-full text-left px-3 py-2 text-sm font-semibold text-primary hover:bg-accent rounded-md mb-1"
          >
            All {category.name}
          </button>
          {category.children?.map((child) => (
            <button
              key={child.id}
              onMouseEnter={() => { setSelectedChild(child); setSelectedGrandchild(null); }}
              onClick={() => handleCategorySelect(child)}
              className={cn(
                "flex items-center justify-between w-full px-3 py-2 text-sm rounded-md transition-colors text-left",
                selectedChild?.id === child.id ? "bg-accent font-medium" : "hover:bg-accent/50"
              )}
            >
              <span>{child.name}</span>
              <div className="flex items-center gap-1">
                <span className="text-xs text-muted-foreground">{child.product_count}</span>
                {child.children && child.children.length > 0 && (
                  <ChevronRight className="h-3 w-3 text-muted-foreground" />
                )}
              </div>
            </button>
          ))}
        </div>
      </div>

      {/* Level 2 - Grandchildren */}
      {selectedChild && selectedChild.children && selectedChild.children.length > 0 && (
        <div className="min-w-[200px] max-h-[350px] overflow-y-auto border-r animate-slide-in-right">
          <div className="p-2">
            <button
              onClick={() => handleCategorySelect(selectedChild)}
              className="block w-full text-left px-3 py-2 text-sm font-semibold text-primary hover:bg-accent rounded-md mb-1"
            >
              All {selectedChild.name}
            </button>
            {selectedChild.children.map((grandchild) => (
              <button
                key={grandchild.id}
                onMouseEnter={() => setSelectedGrandchild(grandchild)}
                onClick={() => handleCategorySelect(grandchild)}
                className={cn(
                  "flex items-center justify-between w-full px-3 py-2 text-sm rounded-md transition-colors text-left",
                  selectedGrandchild?.id === grandchild.id ? "bg-accent font-medium" : "hover:bg-accent/50"
                )}
              >
                <span>{grandchild.name}</span>
                <div className="flex items-center gap-1">
                  <span className="text-xs text-muted-foreground">{grandchild.product_count}</span>
                  {grandchild.children && grandchild.children.length > 0 && (
                    <ChevronRight className="h-3 w-3 text-muted-foreground" />
                  )}
                </div>
              </button>
            ))}
          </div>
        </div>
      )}

      {/* Level 3 - Great-grandchildren */}
      {selectedGrandchild && selectedGrandchild.children && selectedGrandchild.children.length > 0 && (
        <div className="min-w-[200px] max-h-[350px] overflow-y-auto animate-slide-in-right">
          <div className="p-2">
            <button
              onClick={() => handleCategorySelect(selectedGrandchild)}
              className="block w-full text-left px-3 py-2 text-sm font-semibold text-primary hover:bg-accent rounded-md mb-1"
            >
              All {selectedGrandchild.name}
            </button>
            {selectedGrandchild.children.map((greatGrandchild) => (
              <button
                key={greatGrandchild.id}
                onClick={() => handleCategorySelect(greatGrandchild)}
                className="flex items-center justify-between w-full px-3 py-2 text-sm rounded-md hover:bg-accent transition-colors text-left"
              >
                <span>{greatGrandchild.name}</span>
                <span className="text-xs text-muted-foreground">{greatGrandchild.product_count}</span>
              </button>
            ))}
          </div>
        </div>
      )}
    </div>
  );
}

export function Header() {
  const [searchQuery, setSearchQuery] = useState('');
  const [advancedSearchOpen, setAdvancedSearchOpen] = useState(false);
  const [advancedFields, setAdvancedFields] = useState({
    name: '',
    seller: '',
    attrValue: '',
  });
  const [desktopMenuOpen, setDesktopMenuOpen] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
  const [expandedCategory, setExpandedCategory] = useState<Category | null>(null);
  const router = useRouter();
  const { selectedGroup, setSelectedGroup } = useGroup();
  const { data: categoriesData, isLoading: categoriesLoading } = useCategories(selectedGroup || undefined);
  const { theme, setTheme } = useTheme();
  const desktopMenuRef = useRef<HTMLDivElement>(null);

  const activeAdvancedFilters = Object.values(advancedFields).filter(Boolean).length;

  const handleSearch = (e: React.FormEvent) => {
    e.preventDefault();
    const params = new URLSearchParams();

    if (searchQuery.trim()) {
      params.set('q', searchQuery.trim());
    }
    if (advancedFields.name.trim()) {
      params.set('name', advancedFields.name.trim());
    }
    if (advancedFields.seller.trim()) {
      params.set('seller', advancedFields.seller.trim());
    }
    if (advancedFields.attrValue.trim()) {
      params.set('attr_value', advancedFields.attrValue.trim());
    }

    const queryString = params.toString();
    if (queryString) {
      router.push(`/?${queryString}`);
    }
  };

  const clearAdvancedSearch = () => {
    setAdvancedFields({ name: '', seller: '', attrValue: '' });
    setSearchQuery('');
    router.push('/');
  };

  const closeAdvancedSearch = () => {
    clearAdvancedSearch();
    setAdvancedSearchOpen(false);
  };

  const handleGroupSwitch = (group: ProductGroup) => {
    setSelectedGroup(group);
    setDesktopMenuOpen(false);
    setMobileMenuOpen(false);
    setExpandedCategory(null);
    router.push('/');
  };

  const handleCategoryClick = (categoryId: number) => {
    router.push(`/?category_id=${categoryId}`);
    setDesktopMenuOpen(false);
    setExpandedCategory(null);
  };

  const closeDesktopMenu = useCallback(() => {
    setDesktopMenuOpen(false);
    setExpandedCategory(null);
  }, []);

  const closeMobileMenu = useCallback(() => {
    setMobileMenuOpen(false);
  }, []);

  // Close desktop menu when clicking outside
  useEffect(() => {
    const handleClickOutside = (event: MouseEvent) => {
      if (desktopMenuRef.current && !desktopMenuRef.current.contains(event.target as Node)) {
        closeDesktopMenu();
      }
    };

    if (desktopMenuOpen) {
      document.addEventListener('mousedown', handleClickOutside);
    }

    return () => {
      document.removeEventListener('mousedown', handleClickOutside);
    };
  }, [desktopMenuOpen, closeDesktopMenu]);

  const currentGroup = selectedGroup ? groupConfig[selectedGroup] : null;
  const categories = categoriesData?.data || [];

  return (
    <header className="sticky top-0 z-50 w-full border-b bg-background/95 backdrop-blur supports-[backdrop-filter]:bg-background/60">
      <div className="w-full flex h-14 items-center">
        {/* Logo - Fixed at far left */}
        <Link href="/" className="flex items-center gap-2 shrink-0 pl-4 sm:pl-6 lg:pl-8">
          <ShoppingBag className="h-6 w-6" />
          <div className="hidden sm:block">
            <span className="text-lg font-bold leading-none">InstashPro</span>
            <span className="block text-[10px] text-muted-foreground leading-tight">Powered by datafynow.ai</span>
          </div>
        </Link>

        {/* Rest of header content in centered container */}
        <div className="flex-1 max-w-[1600px] mx-auto flex h-14 items-center gap-3 sm:gap-4 px-4 sm:px-6 lg:px-8">

        {/* Group Switcher with Categories - Desktop */}
        {currentGroup && (
          <div className="relative hidden md:block shrink-0" ref={desktopMenuRef}>
            <Button
              variant="ghost"
              size="sm"
              className={cn('gap-2 h-9', currentGroup.color)}
              onClick={() => setDesktopMenuOpen(!desktopMenuOpen)}
            >
              {currentGroup.icon}
              <span>{currentGroup.label}</span>
              <ChevronDown className={cn("h-3 w-3 transition-transform duration-300 ease-out", desktopMenuOpen && "rotate-180")} />
            </Button>

            {desktopMenuOpen && (
              <div className="absolute top-full left-0 mt-2 z-50 bg-popover border rounded-xl shadow-xl overflow-hidden min-w-[280px] animate-scale-in">
                {/* Group Switcher */}
                <div className="p-2 border-b">
                  <p className="px-3 py-1.5 text-xs font-medium text-muted-foreground uppercase tracking-wider">
                    Switch Section
                  </p>
                  {(Object.entries(groupConfig) as [NonNullable<ProductGroup>, typeof groupConfig.car][]).map(
                    ([key, config]) => (
                      <button
                        key={key}
                        onClick={() => handleGroupSwitch(key)}
                        className={cn(
                          'flex items-center gap-3 w-full px-3 py-2.5 text-sm rounded-lg transition-colors',
                          selectedGroup === key
                            ? cn('font-medium', config.bgColor)
                            : 'hover:bg-accent/50'
                        )}
                      >
                        <span className={config.color}>{config.icon}</span>
                        <span>{config.label}</span>
                        {selectedGroup === key && (
                          <span className="ml-auto text-xs text-muted-foreground">Current</span>
                        )}
                      </button>
                    )
                  )}
                </div>

                {/* Categories */}
                <div className="p-2">
                  <p className="px-3 py-1.5 text-xs font-medium text-muted-foreground uppercase tracking-wider">
                    Categories
                  </p>
                  {categoriesLoading ? (
                    <div className="px-3 py-4">
                      <div className="space-y-2">
                        {[...Array(4)].map((_, i) => (
                          <div key={i} className="h-8 bg-muted animate-pulse rounded-md" />
                        ))}
                      </div>
                    </div>
                  ) : categories.length > 0 ? (
                    <div className="flex">
                      {/* Main categories list */}
                      <div className={cn(
                        "min-w-[240px] max-h-[350px] overflow-y-auto",
                        expandedCategory && "border-r"
                      )}>
                        {categories.map((category) => {
                          const hasChildren = category.children && category.children.length > 0;
                          const isExpanded = expandedCategory?.id === category.id;

                          return (
                            <button
                              key={category.id}
                              onMouseEnter={() => hasChildren && setExpandedCategory(category)}
                              onClick={() => {
                                if (!hasChildren) {
                                  handleCategoryClick(category.id);
                                } else {
                                  setExpandedCategory(isExpanded ? null : category);
                                }
                              }}
                              className={cn(
                                "flex items-center justify-between w-full px-3 py-2.5 text-sm rounded-lg transition-colors text-left",
                                isExpanded ? "bg-accent font-medium" : "hover:bg-accent/50"
                              )}
                            >
                              <span>{category.name}</span>
                              <div className="flex items-center gap-1.5">
                                <span className="text-xs text-muted-foreground">{category.product_count}</span>
                                {hasChildren && (
                                  <ChevronRight className="h-3.5 w-3.5 text-muted-foreground" />
                                )}
                              </div>
                            </button>
                          );
                        })}
                      </div>

                      {/* Expanded category mega menu */}
                      {expandedCategory && expandedCategory.children && expandedCategory.children.length > 0 && (
                        <CategoryMegaMenu
                          category={expandedCategory}
                          onSelect={closeDesktopMenu}
                          onCategoryClick={handleCategoryClick}
                        />
                      )}
                    </div>
                  ) : (
                    <p className="px-3 py-4 text-sm text-muted-foreground">No categories available</p>
                  )}
                </div>
              </div>
            )}
          </div>
        )}

        {/* Group Switcher - Mobile (simple, no categories) */}
        {currentGroup && (
          <div className="relative md:hidden">
            <Button
              variant="ghost"
              size="sm"
              className={cn('gap-1.5', currentGroup.color)}
              onClick={() => setMobileMenuOpen(!mobileMenuOpen)}
            >
              {currentGroup.icon}
              <ChevronDown className="h-3 w-3" />
            </Button>

            {mobileMenuOpen && (
              <>
                <div
                  className="fixed inset-0 z-40"
                  onClick={closeMobileMenu}
                />
                <div className="absolute top-full left-0 mt-1 z-50 bg-popover border rounded-lg shadow-lg p-1 min-w-[160px] animate-scale-in">
                  {(Object.entries(groupConfig) as [NonNullable<ProductGroup>, typeof groupConfig.car][]).map(
                    ([key, config]) => (
                      <button
                        key={key}
                        onClick={() => handleGroupSwitch(key)}
                        className={cn(
                          'flex items-center gap-2 w-full px-3 py-2 text-sm rounded-md transition-colors',
                          selectedGroup === key
                            ? 'bg-accent font-medium'
                            : 'hover:bg-accent/50'
                        )}
                      >
                        <span className={config.color}>{config.icon}</span>
                        {config.label}
                      </button>
                    )
                  )}
                </div>
              </>
            )}
          </div>
        )}

        {/* Search */}
        <form onSubmit={handleSearch} className="flex-1 min-w-0 max-w-2xl">
          <div className="relative flex gap-1.5">
            <div className="relative flex-1 min-w-0">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-muted-foreground pointer-events-none" />
              <Input
                type="search"
                placeholder={`Search ${currentGroup?.label.toLowerCase() || 'products'}...`}
                className="pl-9 pr-3 h-9 w-full bg-muted/80 dark:bg-muted/70 border-0 focus-visible:ring-1"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
              />
            </div>
            <Button
              type="button"
              variant={advancedSearchOpen ? "default" : "ghost"}
              size="icon"
              className="h-9 w-9 shrink-0 relative"
              onClick={() => setAdvancedSearchOpen(!advancedSearchOpen)}
            >
              <SlidersHorizontal className="h-4 w-4" />
              {activeAdvancedFilters > 0 && (
                <Badge className="absolute -top-1.5 -right-1.5 h-4 w-4 p-0 flex items-center justify-center text-[10px]">
                  {activeAdvancedFilters}
                </Badge>
              )}
            </Button>
          </div>
        </form>

        {/* Theme Toggle */}
        <Button
          variant="ghost"
          size="icon"
          className="shrink-0 h-9 w-9"
          onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
        >
          <Sun className="h-5 w-5 rotate-0 scale-100 transition-all dark:-rotate-90 dark:scale-0" />
          <Moon className="absolute h-5 w-5 rotate-90 scale-0 transition-all dark:rotate-0 dark:scale-100" />
          <span className="sr-only">Toggle theme</span>
        </Button>
        </div>
      </div>

      {/* Advanced Search Panel */}
      {advancedSearchOpen && (
        <div className="border-t bg-muted/30 animate-collapse-down">
          <div className="w-full flex">
            <div className="flex-1 max-w-[1600px] mx-auto px-4 sm:px-6 lg:px-8 py-3">
            <div className="flex items-center justify-between mb-2">
              <span className="text-sm font-medium text-muted-foreground">Advanced Search</span>
              <Button
                type="button"
                variant="outline"
                size="sm"
                className="h-7 text-xs"
                onClick={closeAdvancedSearch}
              >
                <X className="h-3 w-3 mr-1" />
                Close
              </Button>
            </div>
            <form onSubmit={handleSearch} className="grid grid-cols-1 sm:grid-cols-3 gap-3">
              <div>
                <label className="text-xs text-muted-foreground mb-1 block">Product Name</label>
                <Input
                  placeholder="Search by name..."
                  className="h-8 text-sm"
                  value={advancedFields.name}
                  onChange={(e) => setAdvancedFields(prev => ({ ...prev, name: e.target.value }))}
                />
              </div>
              <div>
                <label className="text-xs text-muted-foreground mb-1 block">Seller / Shop</label>
                <Input
                  placeholder="Search by seller..."
                  className="h-8 text-sm"
                  value={advancedFields.seller}
                  onChange={(e) => setAdvancedFields(prev => ({ ...prev, seller: e.target.value }))}
                />
              </div>
              <div>
                <label className="text-xs text-muted-foreground mb-1 block">Product Features</label>
                <Input
                  placeholder="e.g. 256GB, Blue, Automatic..."
                  className="h-8 text-sm"
                  value={advancedFields.attrValue}
                  onChange={(e) => setAdvancedFields(prev => ({ ...prev, attrValue: e.target.value }))}
                />
              </div>
              <div className="sm:col-span-3 flex justify-end gap-2">
                <Button 
                  type="button" 
                  variant="outline" 
                  size="sm" 
                  className="h-8"
                  onClick={clearAdvancedSearch}
                >
                  Clear
                </Button>
                <Button type="submit" size="sm" className="h-8">
                  <Search className="h-3.5 w-3.5 mr-1.5" />
                  Search
                </Button>
              </div>
            </form>
            </div>
          </div>
        </div>
      )}
    </header>
  );
}
