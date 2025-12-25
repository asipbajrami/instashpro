import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from './client';
import type { ProductAttribute, ProductAttributeValue, StructureOutput } from '@/types';

export interface AttributeWithOutputs extends ProductAttribute {
  structure_outputs: StructureOutput[];
  structure_outputs_count: number;
}

export function useAttributes() {
  return useQuery({
    queryKey: ['attributes'],
    queryFn: async (): Promise<ProductAttribute[]> => {
      const response = await apiClient.get('/admin/attributes');
      return response.data;
    },
  });
}

export function useAttributesWithOutputs() {
  return useQuery({
    queryKey: ['attributes-with-outputs'],
    queryFn: async (): Promise<AttributeWithOutputs[]> => {
      const response = await apiClient.get('/admin/attributes/with-outputs');
      return response.data;
    },
  });
}

export function useAttribute(id: number) {
  return useQuery({
    queryKey: ['attributes', id],
    queryFn: async (): Promise<ProductAttribute> => {
      const response = await apiClient.get(`/admin/attributes/${id}`);
      return response.data;
    },
    enabled: !!id,
  });
}

export function useCreateAttribute() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: { name: string; slug?: string }) => {
      const response = await apiClient.post('/admin/attributes', data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attributes'] });
      queryClient.invalidateQueries({ queryKey: ['product-attributes'] });
    },
  });
}

export function useUpdateAttribute() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, ...data }: { id: number; name?: string; slug?: string }) => {
      const response = await apiClient.put(`/admin/attributes/${id}`, data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attributes'] });
      queryClient.invalidateQueries({ queryKey: ['product-attributes'] });
    },
  });
}

export function useDeleteAttribute() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: number) => {
      await apiClient.delete(`/admin/attributes/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['attributes'] });
      queryClient.invalidateQueries({ queryKey: ['product-attributes'] });
    },
  });
}

// Attribute Values hooks
export function useAttributeValues(attributeId: number) {
  return useQuery({
    queryKey: ['attribute-values', attributeId],
    queryFn: async (): Promise<ProductAttributeValue[]> => {
      const response = await apiClient.get(`/admin/attributes/${attributeId}/values`);
      return response.data;
    },
    enabled: !!attributeId,
  });
}

export function useCreateAttributeValue() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ attributeId, ...data }: {
      attributeId: number;
      value: string;
      ai_value?: string;
      type_value?: string;
      is_temp?: boolean;
      score?: number;
    }) => {
      const response = await apiClient.post(`/admin/attributes/${attributeId}/values`, data);
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['attribute-values', variables.attributeId] });
      queryClient.invalidateQueries({ queryKey: ['attributes'] });
    },
  });
}

export function useUpdateAttributeValue() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ attributeId, valueId, ...data }: {
      attributeId: number;
      valueId: number;
      value?: string;
      ai_value?: string;
      type_value?: string;
      is_temp?: boolean;
      score?: number;
    }) => {
      const response = await apiClient.put(`/admin/attributes/${attributeId}/values/${valueId}`, data);
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['attribute-values', variables.attributeId] });
      queryClient.invalidateQueries({ queryKey: ['attributes'] });
    },
  });
}

export function useDeleteAttributeValue() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ attributeId, valueId }: { attributeId: number; valueId: number }) => {
      await apiClient.delete(`/admin/attributes/${attributeId}/values/${valueId}`);
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['attribute-values', variables.attributeId] });
      queryClient.invalidateQueries({ queryKey: ['attributes'] });
    },
  });
}
