import type { Metadata } from 'next';
import { notFound } from 'next/navigation';
import { NextIntlClientProvider } from 'next-intl';
import { getMessages, setRequestLocale } from 'next-intl/server';
import { locales, type Locale } from '@/i18n/config';
import { QueryProvider } from '@/components/providers/query-provider';
import { GroupProvider } from '@/components/providers/group-provider';
import { ThemeProvider } from '@/components/providers/theme-provider';
import { PostHogProvider } from '@/components/providers/posthog-provider';
import { AppShell } from '@/components/layout/app-shell';

export function generateStaticParams() {
  return locales.map((locale) => ({ locale }));
}

interface LocaleLayoutProps {
  children: React.ReactNode;
  params: Promise<{ locale: string }>;
}

export async function generateMetadata({
  params,
}: {
  params: Promise<{ locale: string }>;
}): Promise<Metadata> {
  const { locale } = await params;
  const messages = await getMessages({ locale });
  const t = messages.metadata as { title: string; description: string };

  return {
    title: t?.title || 'InstashPro - Product Catalog | Powered by datafynow.ai',
    description: t?.description || 'Browse our product catalog - Powered by datafynow.ai',
  };
}

export default async function LocaleLayout({
  children,
  params,
}: LocaleLayoutProps) {
  const { locale } = await params;

  // Validate locale
  if (!locales.includes(locale as Locale)) {
    notFound();
  }

  // Enable static rendering
  setRequestLocale(locale);

  // Get messages for the locale
  const messages = await getMessages();

  return (
    <NextIntlClientProvider messages={messages}>
      <ThemeProvider
        attribute="class"
        defaultTheme="system"
        enableSystem
        disableTransitionOnChange
      >
        <QueryProvider>
          <GroupProvider>
            <PostHogProvider>
              <AppShell>{children}</AppShell>
            </PostHogProvider>
          </GroupProvider>
        </QueryProvider>
      </ThemeProvider>
    </NextIntlClientProvider>
  );
}
