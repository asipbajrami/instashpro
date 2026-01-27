'use client';

import { useState, useEffect, useCallback } from 'react';

const STORAGE_KEY = 'instashpro_search_history';
const MAX_SEARCHES = 6;

export interface SearchHistoryItem {
  query: string;
  timestamp: number;
}

export function useSearchHistory() {
  const [history, setHistory] = useState<SearchHistoryItem[]>([]);
  const [isLoaded, setIsLoaded] = useState(false);

  // Load history from localStorage on mount
  useEffect(() => {
    try {
      const stored = localStorage.getItem(STORAGE_KEY);
      if (stored) {
        const parsed = JSON.parse(stored) as SearchHistoryItem[];
        setHistory(parsed);
      }
    } catch {
      // Ignore localStorage errors
    }
    setIsLoaded(true);
  }, []);

  // Save to localStorage whenever history changes
  useEffect(() => {
    if (isLoaded) {
      try {
        localStorage.setItem(STORAGE_KEY, JSON.stringify(history));
      } catch {
        // Ignore localStorage errors
      }
    }
  }, [history, isLoaded]);

  const addSearch = useCallback((query: string) => {
    if (!query.trim()) return;

    const trimmedQuery = query.trim();

    setHistory((prev) => {
      // Remove existing entry with same query (case insensitive)
      const filtered = prev.filter(
        (item) => item.query.toLowerCase() !== trimmedQuery.toLowerCase()
      );

      // Add new entry at the beginning
      const newHistory = [
        { query: trimmedQuery, timestamp: Date.now() },
        ...filtered,
      ].slice(0, MAX_SEARCHES);

      return newHistory;
    });
  }, []);

  const removeSearch = useCallback((query: string) => {
    setHistory((prev) =>
      prev.filter((item) => item.query !== query)
    );
  }, []);

  const clearHistory = useCallback(() => {
    setHistory([]);
  }, []);

  return {
    history,
    isLoaded,
    addSearch,
    removeSearch,
    clearHistory,
  };
}
