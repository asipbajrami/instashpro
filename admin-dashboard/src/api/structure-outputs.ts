import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from './client';
import type { StructureOutput, ProductAttribute, PaginatedResponse } from '@/types';

interface StructureOutputFilters {
  parent_key?: string;
  used_for?: string;
  search?: string;
  page?: number;
  per_page?: number;
}

export function useStructureOutputs(filters: StructureOutputFilters = {}) {
  return useQuery({
    queryKey: ['structure-outputs', filters],
    queryFn: async (): Promise<PaginatedResponse<StructureOutput>> => {
      const params = new URLSearchParams();
      if (filters.parent_key) params.append('parent_key', filters.parent_key);
      if (filters.used_for) params.append('used_for', filters.used_for);
      if (filters.search) params.append('search', filters.search);
      if (filters.page) params.append('page', String(filters.page));
      if (filters.per_page) params.append('per_page', String(filters.per_page));

      const response = await apiClient.get(`/admin/structure-outputs?${params}`);
      return response.data;
    },
  });
}

export function useStructureOutput(id: number) {
  return useQuery({
    queryKey: ['structure-outputs', id],
    queryFn: async (): Promise<StructureOutput> => {
      const response = await apiClient.get(`/admin/structure-outputs/${id}`);
      return response.data;
    },
    enabled: !!id,
  });
}

export function useProductAttributes() {
  return useQuery({
    queryKey: ['product-attributes'],
    queryFn: async (): Promise<ProductAttribute[]> => {
      const response = await apiClient.get('/admin/product-attributes');
      return response.data;
    },
  });
}

export function useCreateStructureOutput() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: Omit<StructureOutput, 'id' | 'created_at' | 'updated_at' | 'product_attribute'>) => {
      const response = await apiClient.post('/admin/structure-outputs', data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['structure-outputs'] });
    },
  });
}

export function useUpdateStructureOutput() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, ...data }: Partial<StructureOutput> & { id: number }) => {
      const response = await apiClient.put(`/admin/structure-outputs/${id}`, data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['structure-outputs'] });
    },
  });
}

export function useDeleteStructureOutput() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: number) => {
      await apiClient.delete(`/admin/structure-outputs/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['structure-outputs'] });
    },
  });
}
