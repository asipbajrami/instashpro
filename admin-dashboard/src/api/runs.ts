import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from './client';
import type {
  InstagramScrapeRun,
  InstagramProcessingRun,
  ProfileRunsResponse,
  PaginatedResponse,
} from '@/types';

export function useScrapeRuns(profileId?: number, page = 1) {
  return useQuery({
    queryKey: ['scrape-runs', profileId, page],
    queryFn: async (): Promise<PaginatedResponse<InstagramScrapeRun>> => {
      const params: Record<string, string | number> = { page };
      if (profileId) {
        params.profile_id = profileId;
      }
      const response = await apiClient.get('/admin/scrape-runs', { params });
      return response.data;
    },
  });
}

export function useProcessingRuns(profileId?: number, page = 1) {
  return useQuery({
    queryKey: ['processing-runs', profileId, page],
    queryFn: async (): Promise<PaginatedResponse<InstagramProcessingRun>> => {
      const params: Record<string, string | number> = { page };
      if (profileId) {
        params.profile_id = profileId;
      }
      const response = await apiClient.get('/admin/processing-runs', { params });
      return response.data;
    },
  });
}

export function useProfileRuns(profileId: number) {
  return useQuery({
    queryKey: ['profile-runs', profileId],
    queryFn: async (): Promise<ProfileRunsResponse> => {
      const response = await apiClient.get(`/admin/profiles/${profileId}/runs`);
      return response.data;
    },
    enabled: !!profileId,
  });
}

export function useTriggerScrape() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (profileId: number) => {
      const response = await apiClient.post(`/admin/profiles/${profileId}/scrape`);
      return response.data;
    },
    onSuccess: (_, profileId) => {
      queryClient.invalidateQueries({ queryKey: ['scrape-runs'] });
      queryClient.invalidateQueries({ queryKey: ['profile-runs', profileId] });
      queryClient.invalidateQueries({ queryKey: ['instagram-profiles'] });
    },
  });
}

export function useTriggerProcessing() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (profileId: number) => {
      const response = await apiClient.post(`/admin/profiles/${profileId}/process`);
      return response.data;
    },
    onSuccess: (_, profileId) => {
      queryClient.invalidateQueries({ queryKey: ['processing-runs'] });
      queryClient.invalidateQueries({ queryKey: ['profile-runs', profileId] });
      queryClient.invalidateQueries({ queryKey: ['instagram-profiles'] });
    },
  });
}

export function useTriggerLabeling() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (profileId: number) => {
      const response = await apiClient.post(`/admin/profiles/${profileId}/label`);
      return response.data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['instagram-profiles'] });
    },
  });
}

export function useTriggerFullPipeline() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async (profileId: number) => {
      const response = await apiClient.post(`/admin/profiles/${profileId}/full-pipeline`);
      return response.data;
    },
    onSuccess: (_, profileId) => {
      queryClient.invalidateQueries({ queryKey: ['scrape-runs'] });
      queryClient.invalidateQueries({ queryKey: ['processing-runs'] });
      queryClient.invalidateQueries({ queryKey: ['profile-runs', profileId] });
      queryClient.invalidateQueries({ queryKey: ['instagram-profiles'] });
    },
  });
}

export interface LabelingStatus {
  profile_id: number;
  unlabeled_count: number;
  total_posts: number;
  is_complete: boolean;
}

export async function fetchLabelingStatus(profileId: number): Promise<LabelingStatus> {
  const response = await apiClient.get(`/admin/profiles/${profileId}/labeling-status`);
  return response.data;
}

export function useUpdateProfileSettings() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: async ({ profileId, ...data }: { profileId: number; posts_per_request?: number }) => {
      const response = await apiClient.patch(`/admin/profiles/${profileId}/settings`, data);
      return response.data;
    },
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['profile-runs', variables.profileId] });
      queryClient.invalidateQueries({ queryKey: ['instagram-profiles'] });
    },
  });
}
