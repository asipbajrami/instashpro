'use client';

import { ReactNode } from 'react';
import { useGroup } from '@/components/providers/group-provider';
import { GroupSelector } from '@/components/group-selector';
import { Header } from '@/components/layout/header';

interface AppShellProps {
  children: ReactNode;
}

export function AppShell({ children }: AppShellProps) {
  const { selectedGroup } = useGroup();

  // Show group selector if no group is selected
  if (!selectedGroup) {
    return <GroupSelector />;
  }

  // Show main app with header - sidebar and content handled by page
  return (
    <>
      <Header />
      {children}
    </>
  );
}
