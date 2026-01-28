'use client';

import { useQuery } from '@tanstack/react-query';
import { useLocale } from 'next-intl';
import { getCategories, getCategoryWithProducts } from '@/lib/api';

export function useCategories(group?: 'car' | 'tech') {
  const locale = useLocale();
  return useQuery({
    queryKey: ['categories', group, locale],
    queryFn: () => getCategories(group, locale),
  });
}

export function useCategoryWithProducts(slug: string, page = 1, perPage = 40) {
  const locale = useLocale();
  return useQuery({
    queryKey: ['category', slug, page, perPage, locale],
    queryFn: () => getCategoryWithProducts(slug, page, perPage, locale),
    enabled: !!slug,
  });
}
