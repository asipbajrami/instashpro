import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from './client';
import type { InstagramProfile, PaginatedResponse } from '@/types';

interface ProfileFilters {
  status?: string;
  search?: string;
  page?: number;
  per_page?: number;
}

export function useProfiles(filters: ProfileFilters = {}) {
  return useQuery({
    queryKey: ['profiles', filters],
    queryFn: async (): Promise<PaginatedResponse<InstagramProfile>> => {
      const params = new URLSearchParams();
      if (filters.status) params.append('status', filters.status);
      if (filters.search) params.append('search', filters.search);
      if (filters.page) params.append('page', String(filters.page));
      if (filters.per_page) params.append('per_page', String(filters.per_page));

      const response = await apiClient.get(`/admin/instagram-profiles?${params}`);
      return response.data;
    },
  });
}

export function useProfile(id: number) {
  return useQuery({
    queryKey: ['profiles', id],
    queryFn: async (): Promise<InstagramProfile> => {
      const response = await apiClient.get(`/admin/instagram-profiles/${id}`);
      return response.data;
    },
    enabled: !!id,
  });
}

export function useCreateProfile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (data: { 
      username: string; 
      scrape_interval_hours?: number; 
      scheduled_times?: string[];
      timezone?: string;
      status?: string 
    }) => {
      const response = await apiClient.post('/admin/instagram-profiles', data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['profiles'] });
    },
  });
}

export function useUpdateProfile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ id, ...data }: { 
      id: number; 
      username?: string; 
      scrape_interval_hours?: number; 
      scheduled_times?: string[];
      timezone?: string;
      status?: string 
    }) => {
      const response = await apiClient.put(`/admin/instagram-profiles/${id}`, data);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['profiles'] });
    },
  });
}

export function useDeleteProfile() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (id: number) => {
      await apiClient.delete(`/admin/instagram-profiles/${id}`);
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['profiles'] });
    },
  });
}
