import { Link, useLocation } from 'react-router-dom';
import { cn } from '@/lib/utils';
import {
  LayoutDashboard,
  Instagram,
  Settings,
  Layers,
  Tag,
  LogOut,
  Play,
  GitMerge,
} from 'lucide-react';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';

interface SidebarProps {
  onLogout: () => void;
}

interface SidebarContentProps {
  onLogout: () => void;
  onNavigate?: () => void;
}

const navigation = [
  { name: 'Dashboard', href: '/', icon: LayoutDashboard },
  { name: 'Instagram Profiles', href: '/profiles', icon: Instagram },
  { name: 'Scrape Runs', href: '/scrape-runs', icon: Play },
  { name: 'Attributes', href: '/attributes', icon: Tag },
  { name: 'Attributes Overview', href: '/attributes-overview', icon: GitMerge },
  { name: 'Structure Outputs', href: '/structure-outputs', icon: Settings },
  { name: 'Structure Groups', href: '/structure-groups', icon: Layers },
];

export function SidebarContent({ onLogout, onNavigate }: SidebarContentProps) {
  const location = useLocation();

  const handleNavClick = () => {
    onNavigate?.();
  };

  const handleLogout = () => {
    onNavigate?.();
    onLogout();
  };

  return (
    <div className="flex h-full w-64 flex-col bg-card">
      <div className="flex h-16 items-center px-6 border-b">
        <h1 className="text-xl font-bold">Admin Panel</h1>
      </div>

      <nav className="flex-1 space-y-1 px-3 py-4">
        {navigation.map((item) => {
          const isActive = location.pathname === item.href;
          return (
            <Link
              key={item.name}
              to={item.href}
              onClick={handleNavClick}
              className={cn(
                'flex items-center gap-3 rounded-lg px-3 py-3 text-sm font-medium transition-colors min-h-[44px]',
                isActive
                  ? 'bg-primary text-primary-foreground'
                  : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground active:bg-accent/80'
              )}
            >
              <item.icon className="h-5 w-5 flex-shrink-0" />
              {item.name}
            </Link>
          );
        })}
      </nav>

      <div className="border-t p-4">
        <Separator className="mb-4" />
        <Button
          variant="ghost"
          className="w-full justify-start gap-3 text-muted-foreground min-h-[44px]"
          onClick={handleLogout}
        >
          <LogOut className="h-5 w-5" />
          Logout
        </Button>
      </div>
    </div>
  );
}

export function Sidebar({ onLogout }: SidebarProps) {
  return (
    <div className="h-full border-r">
      <SidebarContent onLogout={onLogout} />
    </div>
  );
}
