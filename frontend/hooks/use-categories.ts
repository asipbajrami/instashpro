'use client';

import { useQuery } from '@tanstack/react-query';
import { getCategories, getCategoryWithProducts } from '@/lib/api';

export function useCategories(group?: 'car' | 'tech') {
  return useQuery({
    queryKey: ['categories', group],
    queryFn: () => getCategories(group),
  });
}

export function useCategoryWithProducts(slug: string, page = 1, perPage = 24) {
  return useQuery({
    queryKey: ['category', slug, page, perPage],
    queryFn: () => getCategoryWithProducts(slug, page, perPage),
    enabled: !!slug,
  });
}
