import React from 'react';
import { 
  Zap, 
  Search, 
  Code2, 
  Cpu, 
  Bot, 
  BarChart3, 
  Layers,
  CheckCircle2,
  Server
} from 'lucide-react';
import { TabType } from '../types';

interface HeaderProps {
  activeTab: TabType;
  setActiveTab: (tab: TabType) => void;
}

export const Header: React.FC<HeaderProps> = ({ activeTab, setActiveTab }) => {
  const navItems: Array<{ id: TabType; label: string; icon: React.ReactNode }> = [
    { id: 'overview', label: 'Platform Hub', icon: <Layers className="w-4 h-4" /> },
    { id: 'serp_preview', label: 'SERP & Meta Studio', icon: <Search className="w-4 h-4" /> },
    { id: 'schema_graph', label: 'Schema Graph', icon: <Code2 className="w-4 h-4" /> },
    { id: 'performance_cache', label: 'Speed & Cache', icon: <Zap className="w-4 h-4" /> },
    { id: 'ai_geo_studio', label: 'AI & AEO / GEO', icon: <Bot className="w-4 h-4" /> },
    { id: 'analytics', label: 'Rank & 404 Logs', icon: <BarChart3 className="w-4 h-4" /> },
    { id: 'architecture', label: 'Architecture', icon: <Cpu className="w-4 h-4" /> },
  ];

  return (
    <header className="bg-slate-900 border-b border-slate-800 text-white sticky top-0 z-50">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex items-center justify-between h-16">
          <div className="flex items-center space-x-3">
            <div className="w-9 h-9 rounded-lg bg-gradient-to-tr from-emerald-500 to-teal-400 flex items-center justify-center shadow-lg shadow-emerald-500/20">
              <Zap className="w-5 h-5 text-slate-950 stroke-[2.5]" />
            </div>
            <div>
              <div className="flex items-center space-x-2">
                <span className="font-bold text-lg tracking-tight bg-clip-text text-transparent bg-gradient-to-r from-white via-slate-100 to-slate-300">
                  Apex SEO
                </span>
                <span className="text-[10px] font-semibold uppercase px-2 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
                  v1.0.0 Enterprise
                </span>
              </div>
              <p className="text-xs text-slate-400">High-Performance Modular WordPress SEO Suite</p>
            </div>
          </div>

          <div className="hidden lg:flex items-center space-x-4">
            <div className="flex items-center space-x-2 text-xs bg-slate-800/80 px-3 py-1.5 rounded-md border border-slate-700/60">
              <Server className="w-3.5 h-3.5 text-emerald-400" />
              <span className="text-slate-300">Engine:</span>
              <span className="font-medium text-emerald-400">LiteSpeed + Nginx Direct</span>
            </div>
            <div className="flex items-center space-x-1.5 text-xs text-emerald-400">
              <CheckCircle2 className="w-3.5 h-3.5" />
              <span>6 Modules Booted</span>
            </div>
          </div>
        </div>

        {/* Tab Navigation */}
        <nav className="flex space-x-1 overflow-x-auto py-2 scrollbar-none border-t border-slate-800/60">
          {navItems.map((item) => {
            const isActive = activeTab === item.id;
            return (
              <button
                key={item.id}
                onClick={() => setActiveTab(item.id)}
                className={`flex items-center space-x-2 px-3.5 py-2 rounded-lg text-xs font-medium transition-all whitespace-nowrap ${
                  isActive
                    ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 shadow-sm'
                    : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/50'
                }`}
              >
                {item.icon}
                <span>{item.label}</span>
              </button>
            );
          })}
        </nav>
      </div>
    </header>
  );
};
