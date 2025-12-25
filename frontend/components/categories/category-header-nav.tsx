'use client';

import { useState, useRef, useEffect, useCallback } from 'react';
import { useRouter } from 'next/navigation';
import { ChevronRight, ChevronDown } from 'lucide-react';
import { useCategories } from '@/hooks/use-categories';
import { useGroup } from '@/components/providers/group-provider';
import { Category } from '@/lib/types';
import { cn } from '@/lib/utils';
import { createPortal } from 'react-dom';

interface CategoryHeaderNavProps {
  onSelect?: () => void;
}

function CategoryMegaMenu({
  category,
  onSelect,
  onClose,
  triggerRect,
  onCategoryClick,
  onMouseEnter,
  onMouseLeave
}: {
  category: Category;
  onSelect?: () => void;
  onClose: () => void;
  triggerRect: DOMRect | null;
  onCategoryClick: (categoryId: number) => void;
  onMouseEnter: () => void;
  onMouseLeave: () => void;
}) {
  const [selectedChild, setSelectedChild] = useState<Category | null>(null);
  const [selectedGrandchild, setSelectedGrandchild] = useState<Category | null>(null);

  const hasChildren = category.children && category.children.length > 0;

  if (!hasChildren || !triggerRect) return null;

  const handleCategorySelect = (cat: Category) => {
    onCategoryClick(cat.id);
    onSelect?.();
    onClose();
  };

  const menuContent = (
    <div
      className="fixed z-[100]"
      style={{
        top: triggerRect.bottom,
        left: triggerRect.left,
      }}
      onMouseEnter={onMouseEnter}
      onMouseLeave={onMouseLeave}
    >
      {/* Invisible bridge to prevent gap between button and menu */}
      <div className="h-2" />
      <div className="bg-popover border rounded-lg shadow-xl overflow-hidden">
        <div className="flex">
          {/* Level 1 - Direct children */}
          <div className="min-w-[220px] max-h-[400px] overflow-y-auto border-r bg-muted/30">
            <div className="p-2">
              <button
                onClick={() => handleCategorySelect(category)}
                className="block w-full text-left px-3 py-2 text-sm font-semibold text-primary hover:bg-accent rounded-md mb-1"
              >
                All {category.name}
              </button>
              {category.children.map((child) => (
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
            <div className="min-w-[220px] max-h-[400px] overflow-y-auto border-r">
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
            <div className="min-w-[220px] max-h-[400px] overflow-y-auto">
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
      </div>
    </div>
  );

  if (typeof document !== 'undefined') {
    return createPortal(menuContent, document.body);
  }

  return null;
}

function CategoryDropdown({
  category,
  onSelect,
  onCategoryClick
}: {
  category: Category;
  onSelect?: () => void;
  onCategoryClick: (categoryId: number) => void;
}) {
  const [isOpen, setIsOpen] = useState(false);
  const [triggerRect, setTriggerRect] = useState<DOMRect | null>(null);
  const buttonRef = useRef<HTMLButtonElement>(null);
  const isOverButton = useRef(false);
  const isOverMenu = useRef(false);
  const closeTimeoutRef = useRef<NodeJS.Timeout | null>(null);
  const hasChildren = category.children && category.children.length > 0;

  const updateRect = useCallback(() => {
    if (buttonRef.current) {
      setTriggerRect(buttonRef.current.getBoundingClientRect());
    }
  }, []);

  useEffect(() => {
    if (isOpen) {
      updateRect();
    }
  }, [isOpen, updateRect]);

  const handleOpen = useCallback(() => {
    if (closeTimeoutRef.current) {
      clearTimeout(closeTimeoutRef.current);
      closeTimeoutRef.current = null;
    }
    setIsOpen(true);
  }, []);

  const handleClose = useCallback(() => {
    closeTimeoutRef.current = setTimeout(() => {
      if (!isOverButton.current && !isOverMenu.current) {
        setIsOpen(false);
      }
    }, 100);
  }, []);

  const handleButtonEnter = useCallback(() => {
    isOverButton.current = true;
    handleOpen();
  }, [handleOpen]);

  const handleButtonLeave = useCallback(() => {
    isOverButton.current = false;
    handleClose();
  }, [handleClose]);

  const handleMenuEnter = useCallback(() => {
    isOverMenu.current = true;
    handleOpen();
  }, [handleOpen]);

  const handleMenuLeave = useCallback(() => {
    isOverMenu.current = false;
    handleClose();
  }, [handleClose]);

  useEffect(() => {
    return () => {
      if (closeTimeoutRef.current) {
        clearTimeout(closeTimeoutRef.current);
      }
    };
  }, []);

  return (
    <div className="relative">
      <button
        ref={buttonRef}
        onMouseEnter={handleButtonEnter}
        onMouseLeave={handleButtonLeave}
        onClick={() => {
          // Always select the category when clicked
          onCategoryClick(category.id);
          onSelect?.();
          setIsOpen(false);
        }}
        className={cn(
          "flex items-center gap-1 px-3 py-2 text-sm font-medium rounded-md hover:bg-accent transition-colors whitespace-nowrap",
          isOpen && "bg-accent"
        )}
      >
        {category.name}
        {hasChildren && <ChevronDown className={cn("h-3 w-3 transition-transform", isOpen && "rotate-180")} />}
      </button>

      {hasChildren && isOpen && (
        <CategoryMegaMenu
          category={category}
          onSelect={onSelect}
          onClose={() => setIsOpen(false)}
          triggerRect={triggerRect}
          onCategoryClick={onCategoryClick}
          onMouseEnter={handleMenuEnter}
          onMouseLeave={handleMenuLeave}
        />
      )}
    </div>
  );
}

export function CategoryHeaderNav({ onSelect }: CategoryHeaderNavProps) {
  const router = useRouter();
  const { selectedGroup } = useGroup();
  const { data, isLoading, error } = useCategories(selectedGroup || undefined);

  const handleCategoryClick = (categoryId: number) => {
    router.push(`/?category_id=${categoryId}`);
  };

  if (isLoading) {
    return (
      <div className="flex items-center gap-2">
        {[...Array(5)].map((_, i) => (
          <div key={i} className="h-8 w-20 bg-muted animate-pulse rounded-md" />
        ))}
      </div>
    );
  }

  if (error) {
    return <div className="text-sm text-muted-foreground">Failed to load categories</div>;
  }

  if (!data?.data || data.data.length === 0) {
    return <div className="text-sm text-muted-foreground">No categories available</div>;
  }

  return (
    <nav className="flex items-center gap-1">
      {data.data.map((category) => (
        <CategoryDropdown
          key={category.id}
          category={category}
          onSelect={onSelect}
          onCategoryClick={handleCategoryClick}
        />
      ))}
    </nav>
  );
}
