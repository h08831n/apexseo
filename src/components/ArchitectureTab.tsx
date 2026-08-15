import React from 'react';
import { Cpu, ShieldCheck, Box, Layers, Database, ArrowRight, Code } from 'lucide-react';

export const ArchitectureTab: React.FC = () => {
  return (
    <div className="space-y-6">
      {/* Intro Card */}
      <div className="bg-slate-900 border border-slate-800 rounded-xl p-6 shadow-sm space-y-3">
        <div className="flex items-center space-x-2">
          <Cpu className="w-5 h-5 text-emerald-400" />
          <h2 className="text-lg font-bold text-white">Apex SEO Core Architecture & Dependency Flow</h2>
        </div>
        <p className="text-sm text-slate-300 max-w-3xl leading-relaxed">
          Unlike legacy monolith SEO plugins with high memory consumption and tightly coupled global state, Apex SEO is built from the ground up using modern Software Engineering patterns: PSR-11 Dependency Injection, Single Responsibility Services, and isolated, bootable Modules.
        </p>
      </div>

      {/* Architecture Flow Visualizer */}
      <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div className="bg-slate-900 border border-slate-800 rounded-xl p-4.5 space-y-2">
          <div className="flex items-center justify-between">
            <span className="text-[10px] font-mono text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded">Layer 1</span>
            <Box className="w-4 h-4 text-emerald-400" />
          </div>
          <h3 className="text-sm font-bold text-white">Bootstrap & Env Detection</h3>
          <p className="text-xs text-slate-400 leading-relaxed">
            <code className="text-slate-300">EnvironmentDetector</code> identifies Web Server (LiteSpeed, OLS, Nginx, Apache), PHP version, OPcache, and Object Cache availability.
          </p>
        </div>

        <div className="bg-slate-900 border border-slate-800 rounded-xl p-4.5 space-y-2">
          <div className="flex items-center justify-between">
            <span className="text-[10px] font-mono text-cyan-400 bg-cyan-500/10 px-2 py-0.5 rounded">Layer 2</span>
            <ShieldCheck className="w-4 h-4 text-cyan-400" />
          </div>
          <h3 className="text-sm font-bold text-white">PSR-11 Service Container</h3>
          <p className="text-xs text-slate-400 leading-relaxed">
            <code className="text-slate-300">Container</code> manages service lifecycles (singletons, factories, dependency injection) with lazy initialization.
          </p>
        </div>

        <div className="bg-slate-900 border border-slate-800 rounded-xl p-4.5 space-y-2">
          <div className="flex items-center justify-between">
            <span className="text-[10px] font-mono text-purple-400 bg-purple-500/10 px-2 py-0.5 rounded">Layer 3</span>
            <Layers className="w-4 h-4 text-purple-400" />
          </div>
          <h3 className="text-sm font-bold text-white">Module Registry Lifecycle</h3>
          <p className="text-xs text-slate-400 leading-relaxed">
            <code className="text-slate-300">ModuleRegistry</code> orchestrates registration and booting of SEO, Schema, Performance, Media, AI, and Analytics modules.
          </p>
        </div>

        <div className="bg-slate-900 border border-slate-800 rounded-xl p-4.5 space-y-2">
          <div className="flex items-center justify-between">
            <span className="text-[10px] font-mono text-amber-400 bg-amber-500/10 px-2 py-0.5 rounded">Layer 4</span>
            <Database className="w-4 h-4 text-amber-400" />
          </div>
          <h3 className="text-sm font-bold text-white">Database & Migrations</h3>
          <p className="text-xs text-slate-400 leading-relaxed">
            <code className="text-slate-300">MigrationRunner</code> provisions zero-overhead custom tables for index tracking, 404 monitoring, and redirect rules.
          </p>
        </div>
      </div>

      {/* Directory Code Structure */}
      <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm space-y-3">
        <h3 className="text-sm font-bold text-white flex items-center space-x-2">
          <Code className="w-4 h-4 text-emerald-400" />
          <span>Namespace & Class Directory Tree (/wp-content/plugins/apexseo/src/)</span>
        </h3>

        <pre className="bg-slate-950 p-4 rounded-xl text-xs font-mono text-slate-300 border border-slate-800 overflow-x-auto leading-relaxed">
{`ApexSEO\\
├── Core\\
│   ├── Bootstrap\\ (Plugin, Autoloader, LifecycleManager)
│   ├── Container\\ (Container, ContainerInterface)
│   ├── Environment\\ (EnvironmentDetector, CapabilityRegistry, Server Adapters)
│   ├── Configuration\\ (ConfigurationManager)
│   ├── Database\\ (DatabaseManager, MigrationRunner, Migrations)
│   ├── Modules\\ (ModuleRegistry)
│   ├── REST\\ (RestManager)
│   └── CLI\\ (CliManager)
├── SEO\\
│   ├── Variables\\ (VariableEngine)
│   ├── Meta\\ (TitlePresenter, DescriptionPresenter, CanonicalPresenter, RobotsPresenter)
│   ├── Social\\ (OpenGraphPresenter, TwitterCardPresenter)
│   ├── Breadcrumbs\\ (BreadcrumbGenerator)
│   ├── Sitemap\\ (SitemapGenerator)
│   └── Redirects\\ (RedirectManager)
├── Schema\\
│   ├── SchemaRegistry
│   ├── SchemaGraphBuilder
│   └── Types\\ (Article, Product, FAQPage, LocalBusiness, Organization, WebSite)
├── Performance\\
│   ├── Assets\\ (CssMinifier, JsMinifier, HtmlMinifier, DelayJsEngine)
│   ├── Cache\\ (StaticFileWriter, SmartPurge)
│   └── Tweaks\\ (ResourceHints)
├── Media\\
│   ├── LazyLoad\\ (ImageLazyLoader, PlaceholderGenerator)
│   └── Optimizer\\ (LcpOptimizer, ImageOptimizer)
├── AI\\
│   ├── LlmsTxt\\ (LlmsTxtGenerator)
│   ├── SearchIntent\\ (SearchIntentAnalyzer)
│   └── Generators\\ (MetadataAiGenerator)
└── Analytics\\
    ├── Monitoring\\ (FourOhFourMonitor)
    └── Tracking\\ (RankTracker)`}
        </pre>
      </div>
    </div>
  );
};
