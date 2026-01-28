import { useState } from 'react';
import {
  useBackupInfo,
  useInvalidateBackupInfo,
  useArchiveProgress,
  useStartArchive,
  useClearArchive,
  useClearLocalFiles,
  getDatabaseDownloadUrl,
  getArchiveDownloadUrl,
  type DatabaseDumpType,
} from '@/api/backup';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Button } from '@/components/ui/button';
import { Progress } from '@/components/ui/progress';
import { Database, Download, FileText, Loader2, HardDrive, X, Trash2 } from 'lucide-react';

function formatBytes(bytes: number): string {
  const units = ['B', 'KB', 'MB', 'GB'];
  let i = 0;
  while (bytes >= 1024 && i < units.length - 1) {
    bytes /= 1024;
    i++;
  }
  return `${bytes.toFixed(2)} ${units[i]}`;
}

export function Backups() {
  const { data: info, isLoading, error, refetch } = useBackupInfo();
  const invalidateBackupInfo = useInvalidateBackupInfo();
  const [downloadingDb, setDownloadingDb] = useState<DatabaseDumpType | null>(null);
  const [clearAfterDownload, setClearAfterDownload] = useState(true);

  // Archive hooks
  const { data: archiveProgress } = useArchiveProgress();
  const startArchive = useStartArchive();
  const clearArchive = useClearArchive();
  const clearLocalFiles = useClearLocalFiles();

  const isArchiveRunning = archiveProgress && ['listing', 'archiving'].includes(archiveProgress.status);

  const handleDownloadDatabase = (type: DatabaseDumpType) => {
    setDownloadingDb(type);
    const link = document.createElement('a');
    link.href = getDatabaseDownloadUrl(type);
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    setTimeout(() => setDownloadingDb(null), 3000);
  };

  const handleStartArchive = () => {
    startArchive.mutate();
  };

  const handleClearArchive = () => {
    clearArchive.mutate();
  };

  const handleDownloadArchive = () => {
    const link = document.createElement('a');
    link.href = getArchiveDownloadUrl(clearAfterDownload);
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
    // Refresh info after download
    setTimeout(() => {
      invalidateBackupInfo();
      clearArchive.mutate();
    }, 3000);
  };

  const handleClearLocalFiles = () => {
    if (confirm('Delete all local media files? They will still exist on R2.')) {
      clearLocalFiles.mutate();
    }
  };

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="text-center text-destructive">
        Failed to load backup info
      </div>
    );
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h1 className="text-2xl sm:text-3xl font-bold">Backups</h1>
        <Button variant="outline" onClick={() => refetch()}>
          Refresh
        </Button>
      </div>

      <div className="grid gap-6 md:grid-cols-2">
        {/* Database Backup Card */}
        <Card>
          <CardHeader>
            <div className="flex items-center gap-3">
              <div className="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                <Database className="h-6 w-6 text-blue-600 dark:text-blue-400" />
              </div>
              <div>
                <CardTitle>Database Backup</CardTitle>
                <CardDescription>Download MySQL database dump</CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center gap-2 text-sm text-muted-foreground">
              <FileText className="h-4 w-4" />
              <span>Database: {info?.database || 'instashpro'}</span>
            </div>
            <div className="grid gap-2">
              <Button
                className="w-full"
                onClick={() => handleDownloadDatabase('full')}
                disabled={downloadingDb !== null}
              >
                {downloadingDb === 'full' ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Preparing...
                  </>
                ) : (
                  <>
                    <Download className="mr-2 h-4 w-4" />
                    Full Backup (Structure + Data)
                  </>
                )}
              </Button>
              <Button
                className="w-full"
                variant="secondary"
                onClick={() => handleDownloadDatabase('structure')}
                disabled={downloadingDb !== null}
              >
                {downloadingDb === 'structure' ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Preparing...
                  </>
                ) : (
                  <>
                    <Download className="mr-2 h-4 w-4" />
                    Structure Only
                  </>
                )}
              </Button>
              <Button
                className="w-full"
                variant="secondary"
                onClick={() => handleDownloadDatabase('data')}
                disabled={downloadingDb !== null}
              >
                {downloadingDb === 'data' ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Preparing...
                  </>
                ) : (
                  <>
                    <Download className="mr-2 h-4 w-4" />
                    Data Only
                  </>
                )}
              </Button>
            </div>
          </CardContent>
        </Card>

        {/* Local Files Info Card */}
        <Card>
          <CardHeader>
            <div className="flex items-center gap-3">
              <div className="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                <HardDrive className="h-6 w-6 text-green-600 dark:text-green-400" />
              </div>
              <div>
                <CardTitle>Local Media Files</CardTitle>
                <CardDescription>Files in storage/app/public/posts</CardDescription>
              </div>
            </div>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="space-y-2">
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">Files:</span>
                <span className="font-medium">{info?.local_files || 0}</span>
              </div>
              <div className="flex items-center justify-between text-sm">
                <span className="text-muted-foreground">Size:</span>
                <span className="font-medium">{formatBytes(info?.local_size || 0)}</span>
              </div>
            </div>
            <p className="text-sm text-muted-foreground">
              Media is saved locally when uploaded. Download as ZIP then clear to free space.
            </p>
            {(info?.local_files || 0) > 0 && (
              <Button
                className="w-full"
                variant="destructive"
                onClick={handleClearLocalFiles}
                disabled={clearLocalFiles.isPending || isArchiveRunning}
              >
                {clearLocalFiles.isPending ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Clearing...
                  </>
                ) : (
                  <>
                    <Trash2 className="mr-2 h-4 w-4" />
                    Clear Local Files
                  </>
                )}
              </Button>
            )}
          </CardContent>
        </Card>
      </div>

      {/* Download Archive Card */}
      <Card>
        <CardHeader>
          <div className="flex items-center gap-3">
            <div className="p-2 bg-purple-100 dark:bg-purple-900 rounded-lg">
              <HardDrive className="h-6 w-6 text-purple-600 dark:text-purple-400" />
            </div>
            <div>
              <CardTitle>Download Media Archive</CardTitle>
              <CardDescription>ZIP and download local media files</CardDescription>
            </div>
          </div>
        </CardHeader>
        <CardContent className="space-y-4">
          {/* Progress section */}
          {archiveProgress && archiveProgress.status !== 'idle' && (
            <div className="space-y-3 p-4 rounded-lg bg-muted/50">
              <div className="flex items-center justify-between">
                <span className="text-sm font-medium">{archiveProgress.message}</span>
                {!isArchiveRunning && (
                  <Button
                    variant="ghost"
                    size="sm"
                    onClick={handleClearArchive}
                    disabled={clearArchive.isPending}
                  >
                    <X className="h-4 w-4" />
                  </Button>
                )}
              </div>

              <Progress value={archiveProgress.progress} className="h-2" />

              <div className="flex items-center justify-between text-xs text-muted-foreground">
                <span>
                  {archiveProgress.processed_files !== undefined && archiveProgress.total_files !== undefined
                    ? `${archiveProgress.processed_files} / ${archiveProgress.total_files} files`
                    : `${archiveProgress.progress}%`}
                </span>
                {archiveProgress.failed_files !== undefined && archiveProgress.failed_files > 0 && (
                  <span className="text-destructive">{archiveProgress.failed_files} failed</span>
                )}
              </div>

              {/* Download when completed */}
              {archiveProgress.status === 'completed' && archiveProgress.filename && (
                <div className="pt-2 space-y-3">
                  <div className="flex items-center gap-2 text-sm">
                    <FileText className="h-4 w-4" />
                    <span>{archiveProgress.filename}</span>
                    {archiveProgress.file_size && (
                      <span className="text-muted-foreground">({formatBytes(archiveProgress.file_size)})</span>
                    )}
                  </div>

                  <label className="flex items-center gap-2 text-sm">
                    <input
                      type="checkbox"
                      checked={clearAfterDownload}
                      onChange={(e) => setClearAfterDownload(e.target.checked)}
                      className="rounded"
                    />
                    <span>Clear local files after download</span>
                  </label>

                  <Button className="w-full" onClick={handleDownloadArchive}>
                    <Download className="mr-2 h-4 w-4" />
                    Download Archive
                  </Button>
                </div>
              )}

              {/* Error state */}
              {archiveProgress.status === 'failed' && (
                <p className="text-sm text-destructive">{archiveProgress.message}</p>
              )}
            </div>
          )}

          {/* Start button */}
          {(!archiveProgress || archiveProgress.status === 'idle') && (
            <>
              <p className="text-sm text-muted-foreground">
                Creates a ZIP of all local media files.
                {(info?.local_files || 0) === 0 && ' No files to archive.'}
              </p>
              <Button
                className="w-full"
                onClick={handleStartArchive}
                disabled={startArchive.isPending || isArchiveRunning || (info?.local_files || 0) === 0}
              >
                {startArchive.isPending ? (
                  <>
                    <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                    Starting...
                  </>
                ) : (
                  <>
                    <HardDrive className="mr-2 h-4 w-4" />
                    Create Archive ({info?.local_files || 0} files)
                  </>
                )}
              </Button>
            </>
          )}

          {/* Create new archive button when completed/failed */}
          {archiveProgress && ['completed', 'failed'].includes(archiveProgress.status) && (
            <Button
              className="w-full"
              variant="outline"
              onClick={handleStartArchive}
              disabled={startArchive.isPending || (info?.local_files || 0) === 0}
            >
              {startArchive.isPending ? (
                <>
                  <Loader2 className="mr-2 h-4 w-4 animate-spin" />
                  Starting...
                </>
              ) : (
                <>
                  <HardDrive className="mr-2 h-4 w-4" />
                  Create New Archive
                </>
              )}
            </Button>
          )}
        </CardContent>
      </Card>
    </div>
  );
}
