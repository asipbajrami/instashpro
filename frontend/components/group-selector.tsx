'use client';

import { useGroup, ProductGroup } from '@/components/providers/group-provider';
import { Car, Smartphone, ArrowRight } from 'lucide-react';
import { cn } from '@/lib/utils';

interface GroupOption {
  id: ProductGroup;
  title: string;
  subtitle: string;
  icon: React.ReactNode;
  gradient: string;
  hoverGradient: string;
}

const groups: GroupOption[] = [
  {
    id: 'car',
    title: 'Vehicles',
    subtitle: 'Cars, Motorcycles & Parts',
    icon: <Car className="h-16 w-16 md:h-24 md:w-24" />,
    gradient: 'from-blue-600 to-indigo-700',
    hoverGradient: 'hover:from-blue-500 hover:to-indigo-600',
  },
  {
    id: 'tech',
    title: 'Technology',
    subtitle: 'Phones, Laptops & Electronics',
    icon: <Smartphone className="h-16 w-16 md:h-24 md:w-24" />,
    gradient: 'from-purple-600 to-pink-600',
    hoverGradient: 'hover:from-purple-500 hover:to-pink-500',
  },
];

export function GroupSelector() {
  const { setSelectedGroup } = useGroup();

  const handleSelect = (group: ProductGroup) => {
    setSelectedGroup(group);
  };

  return (
    <div className="fixed inset-0 z-50 bg-background">
      <div className="min-h-screen flex flex-col items-center justify-center p-6">
        {/* Header */}
        <div className="text-center mb-12 md:mb-16">
          <h1 className="text-4xl md:text-6xl font-bold tracking-tight mb-4">
            Welcome to <span className="text-primary">InstashPro</span>
          </h1>
          <p className="text-lg md:text-xl text-muted-foreground max-w-md mx-auto">
            What are you looking for today?
          </p>
        </div>

        {/* Group Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6 md:gap-8 w-full max-w-4xl">
          {groups.map((group) => (
            <button
              key={group.id}
              onClick={() => handleSelect(group.id)}
              className={cn(
                'group relative overflow-hidden rounded-3xl p-8 md:p-12',
                'bg-gradient-to-br text-white',
                'transform transition-all duration-300',
                'hover:scale-[1.02] hover:shadow-2xl',
                'focus:outline-none focus:ring-4 focus:ring-primary/50',
                group.gradient,
                group.hoverGradient
              )}
            >
              {/* Background pattern */}
              <div className="absolute inset-0 opacity-10">
                <div className="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/20" />
                <div className="absolute -left-8 -bottom-8 h-32 w-32 rounded-full bg-white/20" />
              </div>

              {/* Content */}
              <div className="relative flex flex-col items-center text-center">
                <div className="mb-6 p-4 rounded-full bg-white/10 backdrop-blur-sm">
                  {group.icon}
                </div>
                <h2 className="text-2xl md:text-3xl font-bold mb-2">
                  {group.title}
                </h2>
                <p className="text-white/80 text-sm md:text-base mb-6">
                  {group.subtitle}
                </p>
                <div className="flex items-center gap-2 text-sm font-medium opacity-0 group-hover:opacity-100 transition-opacity">
                  <span>Browse {group.title}</span>
                  <ArrowRight className="h-4 w-4 group-hover:translate-x-1 transition-transform" />
                </div>
              </div>
            </button>
          ))}
        </div>

        {/* Footer */}
        <p className="mt-12 text-sm text-muted-foreground">
          You can change this anytime from the header
        </p>
      </div>
    </div>
  );
}
