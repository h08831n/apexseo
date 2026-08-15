import React from 'react';
import { 
  Zap, 
  Search, 
  Code2, 
  Bot, 
  BarChart3, 
  CheckCircle, 
  Activity,
  Layers,
  ArrowUpRight,
  ShieldCheck,
  Flame,
  Sparkles
} from 'lucide-react';
import { ModuleStatus, TabType } from '../types';

interface OverviewTabProps {
  setActiveTab: (tab: TabType) => void;
}

export const OverviewTab: React.FC<OverviewTabProps> = ({ setActiveTab }) => {
  const modules: ModuleStatus[] = [
    {
      id: 'seo',
      name: 'SEO & Metadata Subsystem',
      category: 'Core Meta',
      state: 'BOOTED',
      services: ['VariableEngine', 'TitlePresenter', 'DescriptionPresenter', 'CanonicalPresenter', 'BreadcrumbGenerator', 'SitemapGenerator', 'RedirectManager'],
      description: 'Zero-overhead template engine replacing variables and generating OpenGraph, Twitter, and meta tags.',
    },
    {
      id: 'schema',
      name: 'Schema Graph Subsystem',
      category: 'Structured Data',
      state: 'BOOTED',
      services: ['SchemaRegistry', 'SchemaGraphBuilder', 'ArticleSchema', 'ProductSchema', 'FAQPageSchema', 'LocalBusinessSchema'],
      description: 'Single-pass connected JSON-LD graph builder with deep entity nesting compliant with Schema.org standards.',
    },
    {
      id: 'performance',
      name: 'Performance & Smart Cache',
      category: 'Speed',
      state: 'BOOTED',
      services: ['SmartPurge', 'StaticFileWriter', 'DelayJsEngine', 'CssMinifier', 'JsMinifier', 'ResourceHints'],
      description: 'Static HTML caching, asynchronous script deferral, CSS/JS compression, and LiteSpeed tag-based cache purging.',
    },
    {
      id: 'media',
      name: 'Media & LCP Acceleration',
      category: 'Core Web Vitals',
      state: 'BOOTED',
      services: ['ImageLazyLoader', 'PlaceholderGenerator', 'LcpOptimizer', 'ImageOptimizer'],
      description: 'Inline SVG blur placeholders, native lazy loading, automated WebP/AVIF generation, and LCP fetchpriority.',
    },
    {
      id: 'ai',
      name: 'AI & AEO / GEO Intelligence',
      category: 'Generative Engine',
      state: 'BOOTED',
      services: ['LlmsTxtGenerator', 'SearchIntentAnalyzer', 'MetadataAiGenerator'],
      description: 'Generative Engine Optimization (GEO), Answer Engine Optimization (AEO), /llms.txt generator, and intent mapping.',
    },
    {
      id: 'analytics',
      name: 'Analytics & Search Health',
      category: 'Monitoring',
      state: 'BOOTED',
      services: ['FourOhFourMonitor', 'RankTracker', 'MetricsCollector'],
      description: 'Lightweight SQLite/MySQL internal 404 monitoring, keyword position tracking, and zero-third-party telemetry.',
    },
  ];

  return (
    <div className="space-y-6">
      {/* Top Banner */}
      <div className="bg-gradient-to-r from-slate-900 via-slate-800 to-slate-900 border border-slate-800 rounded-xl p-6 shadow-xl relative overflow-hidden">
        <div className="absolute right-0 top-0 w-96 h-96 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none" />
        <div className="relative z-10 flex flex-col md:flex-row md:items-center justify-between gap-6">
          <div className="space-y-2">
            <div className="flex items-center space-x-2">
              <span className="px-2.5 py-0.5 rounded-full bg-emerald-500/20 text-emerald-300 text-xs font-semibold uppercase tracking-wider border border-emerald-500/30">
                Core System Healthy
              </span>
              <span className="text-slate-400 text-xs flex items-center">
                <Activity className="w-3.5 h-3.5 text-emerald-400 mr-1 animate-pulse" />
                Container Lifecycle Active
              </span>
            </div>
            <h1 className="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
              Enterprise WordPress SEO & Speed Platform
            </h1>
            <p className="text-sm text-slate-300 max-w-2xl leading-relaxed">
              Engineered with a clean Dependency-Injected architecture, PSR-11 service container, and 6 core autonomous subsystems designed for sub-millisecond execution and top SERP ranking.
            </p>
          </div>

          <div className="flex items-center gap-3">
            <button 
              onClick={() => setActiveTab('serp_preview')}
              className="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold text-xs rounded-lg transition-colors shadow-md shadow-emerald-500/20 flex items-center space-x-1.5"
            >
              <Search className="w-4 h-4" />
              <span>Launch SERP Studio</span>
            </button>
            <button 
              onClick={() => setActiveTab('ai_geo_studio')}
              className="px-4 py-2.5 bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 font-semibold text-xs rounded-lg transition-colors flex items-center space-x-1.5"
            >
              <Sparkles className="w-4 h-4 text-amber-400" />
              <span>AI & GEO Studio</span>
            </button>
          </div>
        </div>
      </div>

      {/* Metrics Row */}
      <div className="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div className="bg-slate-900 border border-slate-800 rounded-xl p-4.5 hover:border-slate-700 transition-all shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-medium text-slate-400">Total Booted Modules</span>
            <div className="p-2 rounded-lg bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
              <Layers className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3 flex items-baseline space-x-2">
            <span className="text-2xl font-bold text-white">6 / 6</span>
            <span className="text-xs text-emerald-400 font-medium">100% Operational</span>
          </div>
          <p className="mt-1 text-xs text-slate-400">Zero boot failures across all domains</p>
        </div>

        <div className="bg-slate-900 border border-slate-800 rounded-xl p-4.5 hover:border-slate-700 transition-all shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-medium text-slate-400">DI Registered Services</span>
            <div className="p-2 rounded-lg bg-cyan-500/10 text-cyan-400 border border-cyan-500/20">
              <ShieldCheck className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3 flex items-baseline space-x-2">
            <span className="text-2xl font-bold text-white">28</span>
            <span className="text-xs text-cyan-400 font-medium">Singletons</span>
          </div>
          <p className="mt-1 text-xs text-slate-400">Lazy-loaded on first consumption</p>
        </div>

        <div className="bg-slate-900 border border-slate-800 rounded-xl p-4.5 hover:border-slate-700 transition-all shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-medium text-slate-400">Core Web Vitals Impact</span>
            <div className="p-2 rounded-lg bg-amber-500/10 text-amber-400 border border-amber-500/20">
              <Flame className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3 flex items-baseline space-x-2">
            <span className="text-2xl font-bold text-white">100</span>
            <span className="text-xs text-emerald-400 font-medium">PageSpeed Score</span>
          </div>
          <p className="mt-1 text-xs text-slate-400">LCP &lt; 0.8s, INP &lt; 50ms, CLS 0.00</p>
        </div>

        <div className="bg-slate-900 border border-slate-800 rounded-xl p-4.5 hover:border-slate-700 transition-all shadow-sm">
          <div className="flex items-center justify-between">
            <span className="text-xs font-medium text-slate-400">AEO / GEO Preparedness</span>
            <div className="p-2 rounded-lg bg-purple-500/10 text-purple-400 border border-purple-500/20">
              <Bot className="w-4 h-4" />
            </div>
          </div>
          <div className="mt-3 flex items-baseline space-x-2">
            <span className="text-2xl font-bold text-white">Active</span>
            <span className="text-xs text-purple-400 font-medium">/llms.txt Ready</span>
          </div>
          <p className="mt-1 text-xs text-slate-400">Optimized for ChatGPT, Claude, & Perplexity</p>
        </div>
      </div>

      {/* Module Grid */}
      <div className="space-y-3">
        <h2 className="text-lg font-bold text-white flex items-center space-x-2">
          <Layers className="w-5 h-5 text-emerald-400" />
          <span>Active Subsystems & Services</span>
        </h2>

        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
          {modules.map((mod) => (
            <div 
              key={mod.id} 
              className="bg-slate-900/90 border border-slate-800 hover:border-slate-700 rounded-xl p-5 transition-all flex flex-col justify-between"
            >
              <div className="space-y-3">
                <div className="flex items-center justify-between">
                  <span className="text-[11px] font-semibold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">
                    {mod.category}
                  </span>
                  <span className="flex items-center text-[11px] font-medium text-emerald-400">
                    <CheckCircle className="w-3.5 h-3.5 mr-1" />
                    {mod.state}
                  </span>
                </div>

                <div>
                  <h3 className="text-base font-bold text-white tracking-tight">{mod.name}</h3>
                  <p className="text-xs text-slate-400 mt-1 leading-relaxed">{mod.description}</p>
                </div>

                <div className="pt-2 border-t border-slate-800/80">
                  <span className="text-[10px] uppercase font-semibold text-slate-400 block mb-1.5">
                    Registered Services ({mod.services.length})
                  </span>
                  <div className="flex flex-wrap gap-1">
                    {mod.services.map((srv, i) => (
                      <span key={i} className="text-[10px] font-mono px-1.5 py-0.5 rounded bg-slate-800 text-slate-300 border border-slate-700/60">
                        {srv}
                      </span>
                    ))}
                  </div>
                </div>
              </div>

              <div className="mt-4 pt-3 border-t border-slate-800 flex justify-end">
                <button
                  onClick={() => {
                    if (mod.id === 'seo') setActiveTab('serp_preview');
                    if (mod.id === 'schema') setActiveTab('schema_graph');
                    if (mod.id === 'performance' || mod.id === 'media') setActiveTab('performance_cache');
                    if (mod.id === 'ai') setActiveTab('ai_geo_studio');
                    if (mod.id === 'analytics') setActiveTab('analytics');
                  }}
                  className="text-xs text-emerald-400 hover:text-emerald-300 font-medium flex items-center space-x-1"
                >
                  <span>Open Studio</span>
                  <ArrowUpRight className="w-3.5 h-3.5" />
                </button>
              </div>
            </div>
          ))}
        </div>
      </div>
    </div>
  );
};
