'use client';

import { useQuery, keepPreviousData } from '@tanstack/react-query';
import { getProducts, getProduct, searchProducts, advancedSearchProducts, AdvancedSearchParams } from '@/lib/api';
import { ProductFilters } from '@/lib/types';

export function useProducts(filters: ProductFilters = {}) {
  return useQuery({
    queryKey: ['products', filters],
    queryFn: () => getProducts(filters),
    placeholderData: keepPreviousData,
  });
}

export function useProduct(id: number) {
  return useQuery({
    queryKey: ['product', id],
    queryFn: () => getProduct(id),
    enabled: !!id,
  });
}

export function useSearchProducts(query: string, perPage = 24, group?: 'car' | 'tech') {
  return useQuery({
    queryKey: ['products', 'search', query, perPage, group],
    queryFn: () => searchProducts(query, perPage, group),
    enabled: query.length > 0,
  });
}

export function useAdvancedSearch(params: AdvancedSearchParams, enabled = true) {
  // Check if any search parameter is provided
  const hasSearchParams = Boolean(
    params.q ||
    params.name ||
    params.description ||
    params.seller ||
    params.attr_value ||
    params.type ||
    params.category_id ||
    (params.attr && Object.keys(params.attr).length > 0)
  );

  return useQuery({
    queryKey: ['products', 'advanced-search', params],
    queryFn: () => advancedSearchProducts(params),
    enabled: enabled && hasSearchParams,
    placeholderData: keepPreviousData,
  });
}
