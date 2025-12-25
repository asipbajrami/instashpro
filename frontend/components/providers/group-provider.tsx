'use client';

import { createContext, useContext, useState, useEffect, ReactNode } from 'react';

export type ProductGroup = 'car' | 'tech' | null;

interface GroupContextType {
  selectedGroup: ProductGroup;
  setSelectedGroup: (group: ProductGroup) => void;
  clearGroup: () => void;
}

const GroupContext = createContext<GroupContextType | undefined>(undefined);

const STORAGE_KEY = 'instashpro_selected_group';

export function GroupProvider({ children }: { children: ReactNode }) {
  const [selectedGroup, setSelectedGroupState] = useState<ProductGroup>(null);
  const [isHydrated, setIsHydrated] = useState(false);

  // Load from localStorage on mount
  useEffect(() => {
    const stored = localStorage.getItem(STORAGE_KEY);
    if (stored === 'car' || stored === 'tech') {
      setSelectedGroupState(stored);
    }
    setIsHydrated(true);
  }, []);

  const setSelectedGroup = (group: ProductGroup) => {
    setSelectedGroupState(group);
    if (group) {
      localStorage.setItem(STORAGE_KEY, group);
    } else {
      localStorage.removeItem(STORAGE_KEY);
    }
  };

  const clearGroup = () => {
    setSelectedGroupState(null);
    localStorage.removeItem(STORAGE_KEY);
  };

  // Don't render children until hydrated to avoid mismatch
  if (!isHydrated) {
    return null;
  }

  return (
    <GroupContext.Provider value={{ selectedGroup, setSelectedGroup, clearGroup }}>
      {children}
    </GroupContext.Provider>
  );
}

export function useGroup() {
  const context = useContext(GroupContext);
  if (context === undefined) {
    throw new Error('useGroup must be used within a GroupProvider');
  }
  return context;
}
