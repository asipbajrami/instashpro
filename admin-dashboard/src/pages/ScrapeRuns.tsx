import { useState, useEffect, useRef, useCallback } from 'react';
import { useScrapeRuns, useProcessingRuns, useTriggerScrape, useTriggerProcessing, useTriggerLabeling, useTriggerFullPipeline, useUpdateProfileSettings, useTriggerReprocessSkipped, useCleanupStaleRuns, useCancelProcessingRun, useCancelScrapeRun, fetchLabelingStatus, fetchSkippedPostsStatus } from '@/api/runs';
import { useProfiles } from '@/api/profiles';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import { Loader2, ChevronDown } from 'lucide-react';
import { cn } from '@/lib/utils';
import {
  Table,
  TableBody,
  TableCell,
  TableHead,
  TableHeader,
  TableRow,
} from '@/components/ui/table';
import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select';
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from '@/components/ui/tabs';
import { Play, RefreshCw, Cpu, Settings2, Check, Tag, Zap, RotateCcw, X, Trash2 } from 'lucide-react';
import type { InstagramScrapeRun, InstagramProcessingRun, InstagramProfile } from '@/types';

function formatDate(dateString: string | null) {
  if (!dateString) return '-';
  return new Date(dateString).toLocaleString();
}

function StatusBadge({ status }: { status: string }) {
  const variants: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
    completed: 'default',
    running: 'secondary',
    pending: 'outline',
    failed: 'destructive',
  };
  return <Badge variant={variants[status] || 'outline'}>{status}</Badge>;
}

function TypeBadge({ type }: { type: string }) {
  const variants: Record<string, 'default' | 'secondary' | 'outline'> = {
    full_pipeline: 'default',
    continuation: 'secondary',
    posts: 'outline',
  };
  const labels: Record<string, string> = {
    full_pipeline: 'Full Pipeline',
    continuation: 'continuation',
    posts: 'posts',
  };
  return (
    <Badge variant={variants[type] || 'outline'}>
      {labels[type] || type}
    </Badge>
  );
}

interface ActiveRun {
  type: 'scrape' | 'process' | 'label' | 'full_pipeline' | 'reprocess_skipped';
  status: 'running' | 'completed' | 'failed';
  progress?: string;
  initialCount?: number; // For labeling: initial count of posts to label
}

function ProfileRow({
  profile,
  onScrape,
  onProcess,
  onLabel,
  onFullPipeline,
  onReprocessSkipped,
  onUpdateSettings,
  isScraping,
  isProcessing,
  isLabeling,
  isFullPipelining,
  isReprocessingSkipped,
  isUpdating,
  activeRun,
  skippedCount,
}: {
  profile: InstagramProfile;
  onScrape: () => void;
  onProcess: () => void;
  onLabel: () => void;
  onFullPipeline: () => void;
  onReprocessSkipped: () => void;
  onUpdateSettings: (postsPerRequest: number) => void;
  isScraping: boolean;
  isProcessing: boolean;
  isLabeling: boolean;
  isFullPipelining: boolean;
  isReprocessingSkipped: boolean;
  isUpdating: boolean;
  activeRun?: ActiveRun;
  skippedCount?: number;
}) {
  const [editingPosts, setEditingPosts] = useState(false);
  const [postsValue, setPostsValue] = useState(profile.posts_per_request.toString());

  const handleSavePostsPerRequest = () => {
    const value = Math.min(12, Math.max(1, parseInt(postsValue) || 12));
    onUpdateSettings(value);
    setEditingPosts(false);
  };

  const isScrapeRunning = activeRun?.type === 'scrape' && activeRun.status === 'running';
  const isProcessRunning = activeRun?.type === 'process' && activeRun.status === 'running';
  const isLabelRunning = activeRun?.type === 'label' && activeRun.status === 'running';
  const isFullPipelineRunning = activeRun?.type === 'full_pipeline' && activeRun.status === 'running';
  const isReprocessSkippedRunning = activeRun?.type === 'reprocess_skipped' && activeRun.status === 'running';
  const isAnyRunning = isScrapeRunning || isProcessRunning || isLabelRunning || isFullPipelineRunning || isReprocessSkippedRunning;

  return (
    <TableRow>
      <TableCell>
        <div className="flex items-center gap-3">
          {profile.profile_pic_url && (
            <img
              src={profile.profile_pic_url}
              alt={profile.username}
              className="w-8 h-8 rounded-full"
            />
          )}
          <div>
            <div className="font-medium">{profile.username}</div>
            <div className="text-xs text-muted-foreground">{profile.full_name}</div>
          </div>
        </div>
      </TableCell>
      <TableCell>
        {profile.initial_scrape_done ? (
          <Badge variant="default">Done</Badge>
        ) : (
          <Badge variant="outline" className="text-orange-600 border-orange-300">Pending</Badge>
        )}
      </TableCell>
      <TableCell>
        <div className="text-sm">
          <span className="font-medium">{profile.local_post_count}</span>
          <span className="text-muted-foreground"> / {profile.media_count}</span>
        </div>
        {profile.coverage_percentage !== undefined && (
          <div className="text-xs text-muted-foreground">
            {profile.coverage_percentage.toFixed(1)}% coverage
          </div>
        )}
      </TableCell>
      <TableCell>
        {editingPosts ? (
          <div className="flex items-center gap-2">
            <Input
              type="number"
              min={1}
              max={12}
              value={postsValue}
              onChange={(e) => setPostsValue(e.target.value)}
              className="w-16 h-8"
            />
            <Button
              size="icon"
              variant="ghost"
              className="h-8 w-8"
              onClick={handleSavePostsPerRequest}
              disabled={isUpdating}
            >
              <Check className="h-4 w-4" />
            </Button>
          </div>
        ) : (
          <Button
            variant="ghost"
            size="sm"
            className="h-8 px-2"
            onClick={() => setEditingPosts(true)}
          >
            <Settings2 className="h-3 w-3 mr-1" />
            {profile.posts_per_request}
          </Button>
        )}
      </TableCell>
      <TableCell className="text-sm text-muted-foreground">
        {formatDate(profile.last_scraped_at)}
      </TableCell>
      <TableCell>
        <div className="flex items-center gap-2">
          <Button
            size="sm"
            variant={isScrapeRunning ? 'secondary' : 'outline'}
            onClick={onScrape}
            disabled={isScraping || isScrapeRunning}
            className="h-8"
          >
            {isScrapeRunning ? (
              <Loader2 className="h-3 w-3 mr-1 animate-spin" />
            ) : (
              <Play className="h-3 w-3 mr-1" />
            )}
            {isScrapeRunning ? 'Scraping...' : 'Scrape'}
          </Button>
          <Button
            size="sm"
            variant={isLabelRunning ? 'secondary' : 'outline'}
            onClick={onLabel}
            disabled={isLabeling || isLabelRunning || !profile.initial_scrape_done}
            className="h-8"
            title={!profile.initial_scrape_done ? 'Complete initial scrape first' : ''}
          >
            {isLabelRunning ? (
              <>
                <Loader2 className="h-3 w-3 mr-1 animate-spin" />
                {activeRun?.progress || 'Labeling...'}
              </>
            ) : (
              <>
                <Tag className="h-3 w-3 mr-1" />
                Label
              </>
            )}
          </Button>
          <Button
            size="sm"
            variant={isProcessRunning ? 'secondary' : 'outline'}
            onClick={onProcess}
            disabled={isProcessing || isAnyRunning || !profile.initial_scrape_done}
            className="h-8"
            title={!profile.initial_scrape_done ? 'Complete initial scrape first' : ''}
          >
            {isProcessRunning ? (
              <>
                <Loader2 className="h-3 w-3 mr-1 animate-spin" />
                {activeRun?.progress || 'Processing...'}
              </>
            ) : (
              <>
                <Cpu className="h-3 w-3 mr-1" />
                Process
              </>
            )}
          </Button>
          <Button
            size="sm"
            variant={isFullPipelineRunning ? 'default' : 'secondary'}
            onClick={onFullPipeline}
            disabled={isFullPipelining || isAnyRunning}
            className="h-8"
            title="Run full pipeline: Scrape → Label → Process"
          >
            {isFullPipelineRunning ? (
              <>
                <Loader2 className="h-3 w-3 mr-1 animate-spin" />
                {activeRun?.progress || 'Starting...'}
              </>
            ) : (
              <>
                <Zap className="h-3 w-3 mr-1" />
                Full
              </>
            )}
          </Button>
          {(skippedCount !== undefined && skippedCount > 0) && (
            <Button
              size="sm"
              variant={isReprocessSkippedRunning ? 'secondary' : 'outline'}
              onClick={onReprocessSkipped}
              disabled={isReprocessingSkipped || isAnyRunning}
              className="h-8"
              title={`Reprocess ${skippedCount} skipped posts (processed but no products found)`}
            >
              {isReprocessSkippedRunning ? (
                <>
                  <Loader2 className="h-3 w-3 mr-1 animate-spin" />
                  {activeRun?.progress || 'Reprocessing...'}
                </>
              ) : (
                <>
                  <RotateCcw className="h-3 w-3 mr-1" />
                  Retry ({skippedCount})
                </>
              )}
            </Button>
          )}
        </div>
      </TableCell>
    </TableRow>
  );
}

// Mobile-optimized profile card with collapsible actions
function MobileProfileCard({
  profile,
  onScrape,
  onProcess,
  onLabel,
  onFullPipeline,
  onReprocessSkipped,
  isScraping,
  isProcessing,
  isLabeling,
  isFullPipelining,
  isReprocessingSkipped,
  activeRun,
  skippedCount,
}: {
  profile: InstagramProfile;
  onScrape: () => void;
  onProcess: () => void;
  onLabel: () => void;
  onFullPipeline: () => void;
  onReprocessSkipped: () => void;
  isScraping: boolean;
  isProcessing: boolean;
  isLabeling: boolean;
  isFullPipelining: boolean;
  isReprocessingSkipped: boolean;
  activeRun?: ActiveRun;
  skippedCount?: number;
}) {
  const [showAllActions, setShowAllActions] = useState(false);

  const isScrapeRunning = activeRun?.type === 'scrape' && activeRun.status === 'running';
  const isProcessRunning = activeRun?.type === 'process' && activeRun.status === 'running';
  const isLabelRunning = activeRun?.type === 'label' && activeRun.status === 'running';
  const isFullPipelineRunning = activeRun?.type === 'full_pipeline' && activeRun.status === 'running';
  const isReprocessSkippedRunning = activeRun?.type === 'reprocess_skipped' && activeRun.status === 'running';
  const isAnyRunning = isScrapeRunning || isProcessRunning || isLabelRunning || isFullPipelineRunning || isReprocessSkippedRunning;

  return (
    <div className="rounded-lg border bg-card p-4 shadow-sm">
      {/* Header with profile info */}
      <div className="flex items-center gap-3 mb-3">
        {profile.profile_pic_url && (
          <img
            src={profile.profile_pic_url}
            alt={profile.username}
            className="w-10 h-10 rounded-full"
          />
        )}
        <div className="flex-1 min-w-0">
          <div className="font-medium truncate">{profile.username}</div>
          <div className="text-xs text-muted-foreground truncate">{profile.full_name}</div>
        </div>
        {profile.initial_scrape_done ? (
          <Badge variant="default">Done</Badge>
        ) : (
          <Badge variant="outline" className="text-orange-600 border-orange-300">Pending</Badge>
        )}
      </div>

      {/* Stats row */}
      <div className="grid grid-cols-2 gap-2 text-sm mb-3 py-2 border-y">
        <div>
          <span className="text-muted-foreground">Posts:</span>
          <span className="ml-1 font-medium">{profile.local_post_count}/{profile.media_count}</span>
        </div>
        <div>
          <span className="text-muted-foreground">Coverage:</span>
          <span className="ml-1 font-medium">{profile.coverage_percentage?.toFixed(1) || 0}%</span>
        </div>
      </div>

      {/* Primary actions - always visible */}
      <div className="grid grid-cols-2 gap-2 mb-2">
        <Button
          size="sm"
          variant={isScrapeRunning ? 'secondary' : 'outline'}
          onClick={onScrape}
          disabled={isScraping || isScrapeRunning}
          className="h-10"
        >
          {isScrapeRunning ? (
            <Loader2 className="h-4 w-4 mr-1 animate-spin" />
          ) : (
            <Play className="h-4 w-4 mr-1" />
          )}
          {isScrapeRunning ? 'Scraping' : 'Scrape'}
        </Button>
        <Button
          size="sm"
          variant={isFullPipelineRunning ? 'default' : 'secondary'}
          onClick={onFullPipeline}
          disabled={isFullPipelining || isAnyRunning}
          className="h-10"
        >
          {isFullPipelineRunning ? (
            <>
              <Loader2 className="h-4 w-4 mr-1 animate-spin" />
              {activeRun?.progress || 'Running'}
            </>
          ) : (
            <>
              <Zap className="h-4 w-4 mr-1" />
              Full Pipeline
            </>
          )}
        </Button>
      </div>

      {/* Secondary actions - collapsible */}
      <button
        onClick={() => setShowAllActions(!showAllActions)}
        className="w-full text-sm text-muted-foreground py-2 flex items-center justify-center gap-1 hover:text-foreground transition-colors"
      >
        {showAllActions ? 'Hide actions' : 'More actions'}
        <ChevronDown className={cn("h-4 w-4 transition-transform", showAllActions && "rotate-180")} />
      </button>

      {showAllActions && (
        <div className="grid grid-cols-2 gap-2 pt-2">
          <Button
            size="sm"
            variant={isLabelRunning ? 'secondary' : 'outline'}
            onClick={onLabel}
            disabled={isLabeling || isLabelRunning || !profile.initial_scrape_done}
            className="h-10"
          >
            {isLabelRunning ? (
              <>
                <Loader2 className="h-4 w-4 mr-1 animate-spin" />
                {activeRun?.progress || 'Labeling'}
              </>
            ) : (
              <>
                <Tag className="h-4 w-4 mr-1" />
                Label
              </>
            )}
          </Button>
          <Button
            size="sm"
            variant={isProcessRunning ? 'secondary' : 'outline'}
            onClick={onProcess}
            disabled={isProcessing || isAnyRunning || !profile.initial_scrape_done}
            className="h-10"
          >
            {isProcessRunning ? (
              <>
                <Loader2 className="h-4 w-4 mr-1 animate-spin" />
                {activeRun?.progress || 'Processing'}
              </>
            ) : (
              <>
                <Cpu className="h-4 w-4 mr-1" />
                Process
              </>
            )}
          </Button>
          {(skippedCount !== undefined && skippedCount > 0) && (
            <Button
              size="sm"
              variant={isReprocessSkippedRunning ? 'secondary' : 'outline'}
              onClick={onReprocessSkipped}
              disabled={isReprocessingSkipped || isAnyRunning}
              className="h-10 col-span-2"
            >
              {isReprocessSkippedRunning ? (
                <>
                  <Loader2 className="h-4 w-4 mr-1 animate-spin" />
                  {activeRun?.progress || 'Reprocessing'}
                </>
              ) : (
                <>
                  <RotateCcw className="h-4 w-4 mr-1" />
                  Retry Skipped ({skippedCount})
                </>
              )}
            </Button>
          )}
        </div>
      )}
    </div>
  );
}

export function ScrapeRuns() {
  const [selectedProfileId, setSelectedProfileId] = useState<number | undefined>();
  const [scrapeRunsPage, setScrapeRunsPage] = useState(1);
  const [processingRunsPage, setProcessingRunsPage] = useState(1);
  const [activeRuns, setActiveRuns] = useState<Record<number, ActiveRun>>({});
  const [skippedCounts, setSkippedCounts] = useState<Record<number, number>>({});
  const pollingRef = useRef<ReturnType<typeof setTimeout> | null>(null);
  const activeRunsRef = useRef<Record<number, ActiveRun>>({});

  // Keep ref in sync with state
  useEffect(() => {
    activeRunsRef.current = activeRuns;
  }, [activeRuns]);

  const { data: profiles, isLoading: profilesLoading, refetch: refetchProfiles } = useProfiles();
  const { data: scrapeRunsData, isLoading: scrapeLoading, refetch: refetchScrape } = useScrapeRuns(selectedProfileId, scrapeRunsPage);
  const { data: processingRunsData, isLoading: processingLoading, refetch: refetchProcessing } = useProcessingRuns(selectedProfileId, processingRunsPage);
  const triggerScrapeMutation = useTriggerScrape();
  const triggerProcessingMutation = useTriggerProcessing();
  const triggerLabelingMutation = useTriggerLabeling();
  const triggerFullPipelineMutation = useTriggerFullPipeline();
  const triggerReprocessSkippedMutation = useTriggerReprocessSkipped();
  const cleanupStaleRunsMutation = useCleanupStaleRuns();
  const cancelProcessingRunMutation = useCancelProcessingRun();
  const cancelScrapeRunMutation = useCancelScrapeRun();
  const updateSettingsMutation = useUpdateProfileSettings();

  // Check for running jobs and update status
  const checkRunningJobs = useCallback(async () => {
    const scrapeRuns = scrapeRunsData?.data || [];
    const processingRuns = processingRunsData?.data || [];
    const currentActiveRuns = activeRunsRef.current;

    const newActiveRuns: Record<number, ActiveRun> = {};

    // Check scrape runs (including full_pipeline type)
    scrapeRuns.forEach((run: InstagramScrapeRun) => {
      if (run.status === 'running' && run.profile?.id) {
        const runType = run.type === 'full_pipeline' ? 'full_pipeline' : 'scrape';
        newActiveRuns[run.profile.id] = {
          type: runType,
          status: 'running',
          // For full_pipeline, error_message contains the current step (e.g., "Step 2/3: Labeling...")
          progress: run.type === 'full_pipeline' && run.error_message ? run.error_message : undefined
        };
      }
    });

    // Check processing runs (processing takes priority in display)
    processingRuns.forEach((run: InstagramProcessingRun) => {
      if (run.profile?.id) {
        const processed = run.posts_processed || 0;
        const skipped = run.posts_skipped || 0;
        const failed = run.posts_failed || 0;
        const total = run.posts_to_process || 0;
        const allDone = (processed + skipped + failed) >= total && total > 0;

        // Only show as running if status is running AND not all posts are done
        if (run.status === 'running' && !allDone) {
          newActiveRuns[run.profile.id] = {
            type: 'process',
            status: 'running',
            progress: total > 0 ? `${processed + skipped + failed}/${total}` : undefined
          };
        }
      }
    });

    // Check labeling runs (preserve existing labeling runs and update progress)
    const labelingProfileIds = Object.entries(currentActiveRuns)
      .filter(([, run]) => run.type === 'label' && run.status === 'running')
      .map(([id]) => Number(id));

    for (const profileId of labelingProfileIds) {
      try {
        const status = await fetchLabelingStatus(profileId);
        const existingRun = currentActiveRuns[profileId];
        const initialCount = existingRun?.initialCount || status.total_posts;
        const labeled = initialCount - status.unlabeled_count;

        if (status.is_complete) {
          // Labeling is complete, don't add to newActiveRuns
          refetchProfiles();
        } else {
          newActiveRuns[profileId] = {
            type: 'label',
            status: 'running',
            initialCount,
            progress: `${labeled}/${initialCount}`
          };
        }
      } catch (error) {
        console.error('Failed to fetch labeling status:', error);
        // Keep existing run state on error
        if (currentActiveRuns[profileId]) {
          newActiveRuns[profileId] = currentActiveRuns[profileId];
        }
      }
    }

    setActiveRuns(newActiveRuns);

    // Return true if there are still running jobs
    return Object.keys(newActiveRuns).length > 0;
  }, [scrapeRunsData, processingRunsData, refetchProfiles]);

  // Track number of active runs for dependency
  const activeRunsCount = Object.keys(activeRuns).length;

  // Poll for updates when there are running jobs
  useEffect(() => {
    let isMounted = true;

    const runCheck = async () => {
      if (isMounted) {
        await checkRunningJobs();
      }
    };

    runCheck();

    // Check if there are any active runs (including labeling)
    if (activeRunsCount > 0) {
      // Poll every 3 seconds while jobs are running
      pollingRef.current = setInterval(() => {
        refetchScrape();
        refetchProcessing();
        runCheck();
      }, 3000);
    }

    return () => {
      isMounted = false;
      if (pollingRef.current) {
        clearInterval(pollingRef.current);
        pollingRef.current = null;
      }
    };
  }, [scrapeRunsData, processingRunsData, activeRunsCount, checkRunningJobs, refetchScrape, refetchProcessing]);

  const handleTriggerScrape = async (profileId: number) => {
    try {
      await triggerScrapeMutation.mutateAsync(profileId);
      setActiveRuns(prev => ({ ...prev, [profileId]: { type: 'scrape', status: 'running' } }));
      refetchScrape();
    } catch (error) {
      console.error('Scrape failed:', error);
    }
  };

  const handleTriggerProcessing = async (profileId: number) => {
    try {
      const result = await triggerProcessingMutation.mutateAsync(profileId);
      const total = result.posts_queued || 0;
      setActiveRuns(prev => ({
        ...prev,
        [profileId]: { type: 'process', status: 'running', progress: `0/${total}` }
      }));
      refetchProcessing();
    } catch (error) {
      console.error('Processing failed:', error);
    }
  };

  const handleTriggerLabeling = async (profileId: number) => {
    try {
      const result = await triggerLabelingMutation.mutateAsync(profileId);
      const initialCount = result.data?.posts_to_label || 0;
      setActiveRuns(prev => ({
        ...prev,
        [profileId]: {
          type: 'label',
          status: 'running',
          initialCount,
          progress: `0/${initialCount}`
        }
      }));
    } catch (error) {
      console.error('Labeling failed:', error);
    }
  };

  const handleTriggerFullPipeline = async (profileId: number) => {
    try {
      await triggerFullPipelineMutation.mutateAsync(profileId);
      setActiveRuns(prev => ({
        ...prev,
        [profileId]: { type: 'full_pipeline', status: 'running' }
      }));
      refetchScrape();
      refetchProcessing();
    } catch (error) {
      console.error('Full pipeline failed:', error);
    }
  };

  const handleUpdateSettings = async (profileId: number, postsPerRequest: number) => {
    try {
      await updateSettingsMutation.mutateAsync({ profileId, posts_per_request: postsPerRequest });
      refetchProfiles();
    } catch (error) {
      console.error('Update settings failed:', error);
    }
  };

  const handleTriggerReprocessSkipped = async (profileId: number) => {
    try {
      const result = await triggerReprocessSkippedMutation.mutateAsync(profileId);
      const total = result.data?.posts_queued || 0;
      setActiveRuns(prev => ({
        ...prev,
        [profileId]: { type: 'reprocess_skipped', status: 'running', progress: `0/${total}` }
      }));
      // Clear the skipped count since we're reprocessing
      setSkippedCounts(prev => ({ ...prev, [profileId]: 0 }));
      refetchProcessing();
    } catch (error) {
      console.error('Reprocess skipped failed:', error);
    }
  };

  // Fetch skipped counts for all profiles
  useEffect(() => {
    const fetchSkippedCounts = async () => {
      if (!profiles?.data) return;

      const counts: Record<number, number> = {};
      for (const profile of profiles.data) {
        try {
          const status = await fetchSkippedPostsStatus(profile.id);
          counts[profile.id] = status.skipped_count;
        } catch (error) {
          console.error(`Failed to fetch skipped status for profile ${profile.id}:`, error);
        }
      }
      setSkippedCounts(counts);
    };

    fetchSkippedCounts();
  }, [profiles]);

  const handleCleanupStaleRuns = async () => {
    try {
      await cleanupStaleRunsMutation.mutateAsync();
      refetchScrape();
      refetchProcessing();
      // Clear active runs since cleanup may have changed statuses
      setActiveRuns({});
    } catch (error) {
      console.error('Cleanup failed:', error);
    }
  };

  const handleCancelScrapeRun = async (runId: number) => {
    try {
      await cancelScrapeRunMutation.mutateAsync(runId);
      refetchScrape();
      // Clear active run for this profile
      setActiveRuns({});
    } catch (error) {
      console.error('Cancel scrape run failed:', error);
    }
  };

  const handleCancelProcessingRun = async (runId: number) => {
    try {
      await cancelProcessingRunMutation.mutateAsync(runId);
      refetchProcessing();
      // Clear active run for this profile
      setActiveRuns({});
    } catch (error) {
      console.error('Cancel processing run failed:', error);
    }
  };

  return (
    <div className="space-y-6">
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
        <div>
          <h1 className="text-2xl sm:text-3xl font-bold">Scrape & Processing Runs</h1>
          <p className="text-muted-foreground mt-1 text-sm">
            Manage Instagram scraping and product processing
          </p>
        </div>
        <div className="flex items-center gap-2">
          <Button
            variant="outline"
            size="sm"
            onClick={handleCleanupStaleRuns}
            disabled={cleanupStaleRunsMutation.isPending}
            title="Fix stuck runs that show as running but are actually complete or timed out"
            className="flex-1 sm:flex-none"
          >
            {cleanupStaleRunsMutation.isPending ? (
              <Loader2 className="h-4 w-4 mr-1 animate-spin" />
            ) : (
              <Trash2 className="h-4 w-4 mr-1" />
            )}
            <span className="hidden sm:inline">Cleanup Stuck</span>
            <span className="sm:hidden">Cleanup</span>
          </Button>
          <Button
            variant="outline"
            size="icon"
            onClick={() => {
              refetchProfiles();
              refetchScrape();
              refetchProcessing();
            }}
          >
            <RefreshCw className="h-4 w-4" />
          </Button>
        </div>
      </div>

      <Tabs defaultValue="profiles" className="w-full">
        <TabsList className="w-full sm:w-auto overflow-x-auto flex-nowrap">
          <TabsTrigger value="profiles" className="flex-shrink-0">Profiles</TabsTrigger>
          <TabsTrigger value="scrape" className="flex-shrink-0">Scrape History</TabsTrigger>
          <TabsTrigger value="processing" className="flex-shrink-0">Processing History</TabsTrigger>
        </TabsList>

        <TabsContent value="profiles" className="mt-4">
          {profilesLoading ? (
            <div className="flex items-center justify-center h-64">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>
          ) : (
            <>
              {/* Mobile Card View */}
              <div className="lg:hidden space-y-3">
                {profiles?.data?.map((profile) => (
                  <MobileProfileCard
                    key={profile.id}
                    profile={profile}
                    onScrape={() => handleTriggerScrape(profile.id)}
                    onProcess={() => handleTriggerProcessing(profile.id)}
                    onLabel={() => handleTriggerLabeling(profile.id)}
                    onFullPipeline={() => handleTriggerFullPipeline(profile.id)}
                    onReprocessSkipped={() => handleTriggerReprocessSkipped(profile.id)}
                    isScraping={triggerScrapeMutation.isPending}
                    isProcessing={triggerProcessingMutation.isPending}
                    isLabeling={triggerLabelingMutation.isPending}
                    isFullPipelining={triggerFullPipelineMutation.isPending}
                    isReprocessingSkipped={triggerReprocessSkippedMutation.isPending}
                    activeRun={activeRuns[profile.id]}
                    skippedCount={skippedCounts[profile.id]}
                  />
                ))}
                {profiles?.data?.length === 0 && (
                  <div className="text-center text-muted-foreground py-8">
                    No profiles found. Add profiles from the Instagram Profiles page.
                  </div>
                )}
              </div>

              {/* Desktop Table View */}
              <div className="hidden lg:block">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Profile</TableHead>
                      <TableHead>Initial Scrape</TableHead>
                      <TableHead>Posts</TableHead>
                      <TableHead>Per Request</TableHead>
                      <TableHead>Last Scraped</TableHead>
                      <TableHead>Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {profiles?.data?.map((profile) => (
                      <ProfileRow
                        key={profile.id}
                        profile={profile}
                        onScrape={() => handleTriggerScrape(profile.id)}
                        onProcess={() => handleTriggerProcessing(profile.id)}
                        onLabel={() => handleTriggerLabeling(profile.id)}
                        onFullPipeline={() => handleTriggerFullPipeline(profile.id)}
                        onReprocessSkipped={() => handleTriggerReprocessSkipped(profile.id)}
                        onUpdateSettings={(value) => handleUpdateSettings(profile.id, value)}
                        isScraping={triggerScrapeMutation.isPending}
                        isProcessing={triggerProcessingMutation.isPending}
                        isLabeling={triggerLabelingMutation.isPending}
                        isFullPipelining={triggerFullPipelineMutation.isPending}
                        isReprocessingSkipped={triggerReprocessSkippedMutation.isPending}
                        isUpdating={updateSettingsMutation.isPending}
                        activeRun={activeRuns[profile.id]}
                        skippedCount={skippedCounts[profile.id]}
                      />
                    ))}
                    {profiles?.data?.length === 0 && (
                      <TableRow>
                        <TableCell colSpan={6} className="text-center text-muted-foreground py-8">
                          No profiles found. Add profiles from the Instagram Profiles page.
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </div>
            </>
          )}
        </TabsContent>

        <TabsContent value="scrape" className="mt-4">
          <div className="mb-4">
            <Select
              value={selectedProfileId?.toString() || 'all'}
              onValueChange={(val) => {
                setSelectedProfileId(val === 'all' ? undefined : Number(val));
                setScrapeRunsPage(1);
              }}
            >
              <SelectTrigger className="w-full sm:w-[200px]">
                <SelectValue placeholder="Filter by profile" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Profiles</SelectItem>
                {profiles?.data?.map((profile) => (
                  <SelectItem key={profile.id} value={profile.id.toString()}>
                    {profile.username}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {scrapeLoading ? (
            <div className="flex items-center justify-center h-64">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>
          ) : (
            <>
              {/* Mobile Card View */}
              <div className="md:hidden space-y-3">
                {scrapeRunsData?.data?.map((run: InstagramScrapeRun) => (
                  <div key={run.id} className="rounded-lg border bg-card p-4 shadow-sm">
                    <div className="flex items-center justify-between mb-2">
                      <span className="font-medium">{run.profile?.username || '-'}</span>
                      <StatusBadge status={run.status} />
                    </div>
                    <div className="flex items-center gap-2 mb-2">
                      <TypeBadge type={run.type} />
                      {run.type === 'full_pipeline' && run.status === 'running' && run.error_message && (
                        <span className="text-xs text-muted-foreground">{run.error_message}</span>
                      )}
                    </div>
                    <div className="grid grid-cols-3 gap-2 text-sm mb-2">
                      <div>
                        <span className="text-muted-foreground block text-xs">Fetched</span>
                        <span className="font-medium">{run.posts_fetched}</span>
                      </div>
                      <div>
                        <span className="text-muted-foreground block text-xs">New</span>
                        <span className="font-medium text-green-600">{run.posts_new}</span>
                      </div>
                      <div>
                        <span className="text-muted-foreground block text-xs">Skipped</span>
                        <span className="font-medium text-gray-500">{run.posts_skipped}</span>
                      </div>
                    </div>
                    <div className="text-xs text-muted-foreground mb-2">
                      Started: {formatDate(run.started_at)}
                    </div>
                    {run.status === 'running' && (
                      <Button
                        size="sm"
                        variant="ghost"
                        className="w-full h-8 text-red-600 hover:text-red-700 hover:bg-red-50"
                        onClick={() => handleCancelScrapeRun(run.id)}
                        disabled={cancelScrapeRunMutation.isPending}
                      >
                        <X className="h-3 w-3 mr-1" />
                        Cancel
                      </Button>
                    )}
                  </div>
                ))}
                {scrapeRunsData?.data?.length === 0 && (
                  <div className="text-center text-muted-foreground py-8">
                    No scrape runs found.
                  </div>
                )}
              </div>

              {/* Desktop Table View */}
              <div className="hidden md:block">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Profile</TableHead>
                      <TableHead>Type</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>Fetched</TableHead>
                      <TableHead>New</TableHead>
                      <TableHead>Skipped</TableHead>
                      <TableHead>Has More</TableHead>
                      <TableHead>Started</TableHead>
                      <TableHead>Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {scrapeRunsData?.data?.map((run: InstagramScrapeRun) => (
                      <TableRow key={run.id}>
                        <TableCell className="font-medium">
                          {run.profile?.username || '-'}
                        </TableCell>
                        <TableCell>
                          <TypeBadge type={run.type} />
                        </TableCell>
                        <TableCell>
                          <div className="flex flex-col gap-1">
                            <StatusBadge status={run.status} />
                            {run.type === 'full_pipeline' && run.status === 'running' && run.error_message && (
                              <span className="text-xs text-muted-foreground">{run.error_message}</span>
                            )}
                          </div>
                        </TableCell>
                        <TableCell>{run.posts_fetched}</TableCell>
                        <TableCell className="text-green-600">{run.posts_new}</TableCell>
                        <TableCell className="text-gray-500">{run.posts_skipped}</TableCell>
                        <TableCell>
                          {run.has_more_pages ? (
                            <Badge variant="secondary">Yes</Badge>
                          ) : (
                            <span className="text-gray-400">No</span>
                          )}
                        </TableCell>
                        <TableCell className="text-sm text-muted-foreground">
                          {formatDate(run.started_at)}
                        </TableCell>
                        <TableCell>
                          {run.status === 'running' && (
                            <Button
                              size="sm"
                              variant="ghost"
                              className="h-7 text-red-600 hover:text-red-700 hover:bg-red-50"
                              onClick={() => handleCancelScrapeRun(run.id)}
                              disabled={cancelScrapeRunMutation.isPending}
                              title="Cancel this run"
                            >
                              <X className="h-3 w-3 mr-1" />
                              Cancel
                            </Button>
                          )}
                        </TableCell>
                      </TableRow>
                    ))}
                    {scrapeRunsData?.data?.length === 0 && (
                      <TableRow>
                        <TableCell colSpan={9} className="text-center text-muted-foreground py-8">
                          No scrape runs found.
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </div>

              {scrapeRunsData && scrapeRunsData.last_page > 1 && (
                <div className="flex justify-center gap-2 mt-4">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setScrapeRunsPage(p => Math.max(1, p - 1))}
                    disabled={scrapeRunsPage === 1}
                  >
                    Previous
                  </Button>
                  <span className="py-2 px-3 text-sm">
                    Page {scrapeRunsPage} of {scrapeRunsData.last_page}
                  </span>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setScrapeRunsPage(p => Math.min(scrapeRunsData.last_page, p + 1))}
                    disabled={scrapeRunsPage === scrapeRunsData.last_page}
                  >
                    Next
                  </Button>
                </div>
              )}
            </>
          )}
        </TabsContent>

        <TabsContent value="processing" className="mt-4">
          <div className="mb-4">
            <Select
              value={selectedProfileId?.toString() || 'all'}
              onValueChange={(val) => {
                setSelectedProfileId(val === 'all' ? undefined : Number(val));
                setProcessingRunsPage(1);
              }}
            >
              <SelectTrigger className="w-full sm:w-[200px]">
                <SelectValue placeholder="Filter by profile" />
              </SelectTrigger>
              <SelectContent>
                <SelectItem value="all">All Profiles</SelectItem>
                {profiles?.data?.map((profile) => (
                  <SelectItem key={profile.id} value={profile.id.toString()}>
                    {profile.username}
                  </SelectItem>
                ))}
              </SelectContent>
            </Select>
          </div>

          {processingLoading ? (
            <div className="flex items-center justify-center h-64">
              <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary"></div>
            </div>
          ) : (
            <>
              {/* Mobile Card View */}
              <div className="md:hidden space-y-3">
                {processingRunsData?.data?.map((run: InstagramProcessingRun) => (
                  <div key={run.id} className="rounded-lg border bg-card p-4 shadow-sm">
                    <div className="flex items-center justify-between mb-2">
                      <span className="font-medium">{run.profile?.username || '-'}</span>
                      <StatusBadge status={run.status} />
                    </div>
                    <div className="grid grid-cols-2 gap-2 text-sm mb-2">
                      <div>
                        <span className="text-muted-foreground block text-xs">To Process</span>
                        <span className="font-medium">{run.posts_to_process}</span>
                      </div>
                      <div>
                        <span className="text-muted-foreground block text-xs">Processed</span>
                        <span className="font-medium text-green-600">{run.posts_processed}</span>
                      </div>
                      <div>
                        <span className="text-muted-foreground block text-xs">Failed</span>
                        <span className="font-medium text-red-600">{run.posts_failed}</span>
                      </div>
                      <div>
                        <span className="text-muted-foreground block text-xs">Skipped</span>
                        <span className="font-medium text-gray-500">{run.posts_skipped}</span>
                      </div>
                    </div>
                    <div className="text-xs text-muted-foreground mb-2">
                      Started: {formatDate(run.started_at)}
                    </div>
                    {run.status === 'running' && (
                      <Button
                        size="sm"
                        variant="ghost"
                        className="w-full h-8 text-red-600 hover:text-red-700 hover:bg-red-50"
                        onClick={() => handleCancelProcessingRun(run.id)}
                        disabled={cancelProcessingRunMutation.isPending}
                      >
                        <X className="h-3 w-3 mr-1" />
                        Cancel
                      </Button>
                    )}
                  </div>
                ))}
                {processingRunsData?.data?.length === 0 && (
                  <div className="text-center text-muted-foreground py-8">
                    No processing runs found.
                  </div>
                )}
              </div>

              {/* Desktop Table View */}
              <div className="hidden md:block">
                <Table>
                  <TableHeader>
                    <TableRow>
                      <TableHead>Profile</TableHead>
                      <TableHead>Status</TableHead>
                      <TableHead>To Process</TableHead>
                      <TableHead>Processed</TableHead>
                      <TableHead>Failed</TableHead>
                      <TableHead>Skipped</TableHead>
                      <TableHead>Started</TableHead>
                      <TableHead>Actions</TableHead>
                    </TableRow>
                  </TableHeader>
                  <TableBody>
                    {processingRunsData?.data?.map((run: InstagramProcessingRun) => (
                      <TableRow key={run.id}>
                        <TableCell className="font-medium">
                          {run.profile?.username || '-'}
                        </TableCell>
                        <TableCell>
                          <StatusBadge status={run.status} />
                        </TableCell>
                        <TableCell>{run.posts_to_process}</TableCell>
                        <TableCell className="text-green-600">{run.posts_processed}</TableCell>
                        <TableCell className="text-red-600">{run.posts_failed}</TableCell>
                        <TableCell className="text-gray-500">{run.posts_skipped}</TableCell>
                        <TableCell className="text-sm text-muted-foreground">
                          {formatDate(run.started_at)}
                        </TableCell>
                        <TableCell>
                          {run.status === 'running' && (
                            <Button
                              size="sm"
                              variant="ghost"
                              className="h-7 text-red-600 hover:text-red-700 hover:bg-red-50"
                              onClick={() => handleCancelProcessingRun(run.id)}
                              disabled={cancelProcessingRunMutation.isPending}
                              title="Cancel this run"
                            >
                              <X className="h-3 w-3 mr-1" />
                              Cancel
                            </Button>
                          )}
                        </TableCell>
                      </TableRow>
                    ))}
                    {processingRunsData?.data?.length === 0 && (
                      <TableRow>
                        <TableCell colSpan={8} className="text-center text-muted-foreground py-8">
                          No processing runs found.
                        </TableCell>
                      </TableRow>
                    )}
                  </TableBody>
                </Table>
              </div>

              {processingRunsData && processingRunsData.last_page > 1 && (
                <div className="flex justify-center gap-2 mt-4">
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setProcessingRunsPage(p => Math.max(1, p - 1))}
                    disabled={processingRunsPage === 1}
                  >
                    Previous
                  </Button>
                  <span className="py-2 px-3 text-sm">
                    Page {processingRunsPage} of {processingRunsData.last_page}
                  </span>
                  <Button
                    variant="outline"
                    size="sm"
                    onClick={() => setProcessingRunsPage(p => Math.min(processingRunsData.last_page, p + 1))}
                    disabled={processingRunsPage === processingRunsData.last_page}
                  >
                    Next
                  </Button>
                </div>
              )}
            </>
          )}
        </TabsContent>
      </Tabs>
    </div>
  );
}
