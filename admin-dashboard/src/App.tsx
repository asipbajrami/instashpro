import { BrowserRouter, Routes, Route, Navigate } from 'react-router-dom';
import { QueryClient, QueryClientProvider } from '@tanstack/react-query';
import { AuthProvider } from '@/hooks/useAuth';
import { Layout } from '@/components/layout/Layout';
import { Login } from '@/pages/Login';
import { Dashboard } from '@/pages/Dashboard';
import { Profiles } from '@/pages/Profiles';
import { Attributes } from '@/pages/Attributes';
import { AttributeValues } from '@/pages/AttributeValues';
import { AttributeOutputsOverview } from '@/pages/AttributeOutputsOverview';
import { StructureOutputs } from '@/pages/StructureOutputs';
import { StructureGroups } from '@/pages/StructureGroups';
import { ScrapeRuns } from '@/pages/ScrapeRuns';

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 1000 * 60 * 5, // 5 minutes
      retry: 1,
    },
  },
});

function App() {
  return (
    <QueryClientProvider client={queryClient}>
      <AuthProvider>
        <BrowserRouter>
          <Routes>
            <Route path="/login" element={<Login />} />
            <Route element={<Layout />}>
              <Route path="/" element={<Dashboard />} />
              <Route path="/profiles" element={<Profiles />} />
              <Route path="/scrape-runs" element={<ScrapeRuns />} />
              <Route path="/attributes" element={<Attributes />} />
              <Route path="/attributes/:id/values" element={<AttributeValues />} />
              <Route path="/attributes-overview" element={<AttributeOutputsOverview />} />
              <Route path="/structure-outputs" element={<StructureOutputs />} />
              <Route path="/structure-groups" element={<StructureGroups />} />
            </Route>
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </BrowserRouter>
      </AuthProvider>
    </QueryClientProvider>
  );
}

export default App;
