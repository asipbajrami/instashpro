import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query';
import { apiClient } from './client';

// Types
export interface BackupInfo {
  local_files: number;
  local_size: number;
  database: string;
}

export interface ArchiveProgress {
  status: 'idle' | 'listing' | 'archiving' | 'completed' | 'failed';
  message: string;
  progress: number;
  total_files?: number;
  processed_files?: number;
  failed_files?: number;
  file_size?: number;
  filename?: string;
  zip_path?: string;
  completed_at?: string;
  updated_at?: string;
}

export type DatabaseDumpType = 'full' | 'structure' | 'data';

// API base URL for direct downloads
const BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:8000';

// Hooks
export function useBackupInfo() {
  return useQuery({
    queryKey: ['backup', 'info'],
    queryFn: async (): Promise<BackupInfo> => {
      const { data } = await apiClient.get('/admin/backup/info');
      return data;
    },
  });
}

export function useInvalidateBackupInfo() {
  const queryClient = useQueryClient();
  return () => queryClient.invalidateQueries({ queryKey: ['backup', 'info'] });
}

export function useArchiveProgress() {
  return useQuery({
    queryKey: ['backup', 'archive'],
    queryFn: async (): Promise<ArchiveProgress> => {
      const { data } = await apiClient.get('/admin/backup/archive/progress');
      return data;
    },
    refetchInterval: (query) => {
      const status = query.state.data?.status;
      // Poll while in progress
      return status && ['listing', 'archiving'].includes(status) ? 2000 : false;
    },
  });
}

export function useStartArchive() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async () => {
      const { data } = await apiClient.post('/admin/backup/archive');
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['backup', 'archive'] });
    },
  });
}

export function useClearArchive() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async () => {
      const { data } = await apiClient.delete('/admin/backup/archive');
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['backup', 'archive'] });
    },
  });
}

export function useClearLocalFiles() {
  const queryClient = useQueryClient();
  return useMutation({
    mutationFn: async () => {
      const { data } = await apiClient.delete('/admin/backup/local');
      return data;
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['backup', 'info'] });
    },
  });
}

// URL generators for direct downloads
export function getDatabaseDownloadUrl(type: DatabaseDumpType = 'full'): string {
  return `${BASE_URL}/api/admin/backup/database?type=${type}`;
}

export function getArchiveDownloadUrl(clearLocal = false): string {
  const base = `${BASE_URL}/api/admin/backup/archive/download`;
  return clearLocal ? `${base}?clear_local=1` : base;
}
