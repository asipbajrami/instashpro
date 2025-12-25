import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from './client';
import type { StructureOutputGroup } from '@/types';

export function useStructureOutputGroups() {
  return useQuery({
    queryKey: ['structure-output-groups'],
    queryFn: async (): Promise<StructureOutputGroup[]> => {
      const response = await apiClient.get('/admin/structure-output-groups');
      return response.data;
    },
  });
}

export function useStructureOutputGroup(id: number) {
  return useQuery({
    queryKey: ['structure-output-groups', id],
    queryFn: async (): Promise<StructureOutputGroup> => {
      const response = await apiClient.get(`/admin/structure-output-groups/${id}`);
      return response.data;
    },
    enabled: !!id,
  });
}

export function useCreateStructureOutputGroup() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: { used_for: string; description: string }) => {
      const response = await apiClient.post('/admin/structure-output-groups', data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['structure-output-groups'] });
    },
  });
}

export function useUpdateStructureOutputGroup() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, ...data }: { id: number; used_for?: string; description?: string }) => {
      const response = await apiClient.put(`/admin/structure-output-groups/${id}`, data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['structure-output-groups'] });
    },
  });
}

export function useDeleteStructureOutputGroup() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: number) => {
      await apiClient.delete(`/admin/structure-output-groups/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['structure-output-groups'] });
    },
  });
}
