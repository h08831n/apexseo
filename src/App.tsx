import React, { useState } from 'react';
import { Header } from './components/Header';
import { OverviewTab } from './components/OverviewTab';
import { SerpPreviewTab } from './components/SerpPreviewTab';
import { SchemaGraphTab } from './components/SchemaGraphTab';
import { PerformanceTab } from './components/PerformanceTab';
import { AiGeoStudioTab } from './components/AiGeoStudioTab';
import { AnalyticsTab } from './components/AnalyticsTab';
import { ArchitectureTab } from './components/ArchitectureTab';
import { TabType } from './types';

export default function App() {
  const [activeTab, setActiveTab] = useState<TabType>('overview');

  return (
    <div className="min-h-screen bg-slate-950 text-slate-100 flex flex-col font-sans selection:bg-emerald-500/30 selection:text-emerald-200">
      <Header activeTab={activeTab} setActiveTab={setActiveTab} />

      <main className="flex-1 max-w-7xl w-full mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {activeTab === 'overview' && <OverviewTab setActiveTab={setActiveTab} />}
        {activeTab === 'serp_preview' && <SerpPreviewTab />}
        {activeTab === 'schema_graph' && <SchemaGraphTab />}
        {activeTab === 'performance_cache' && <PerformanceTab />}
        {activeTab === 'ai_geo_studio' && <AiGeoStudioTab />}
        {activeTab === 'analytics' && <AnalyticsTab />}
        {activeTab === 'architecture' && <ArchitectureTab />}
      </main>

      <footer className="border-t border-slate-900 bg-slate-950 py-6 text-center text-xs text-slate-500">
        <div className="max-w-7xl mx-auto px-4 flex flex-col sm:flex-row items-center justify-between gap-2">
          <span>Apex SEO Platform v1.0.0 — PSR-11 Modular WordPress Suite</span>
          <span className="text-slate-400">Zero Bloat · Sub-Millisecond Execution · High-Performance SERP</span>
        </div>
      </footer>
    </div>
  );
}
