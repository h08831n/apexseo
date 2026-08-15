import React, { useState } from 'react';
import { 
  BarChart3, 
  AlertTriangle, 
  TrendingUp, 
  TrendingDown, 
  Minus, 
  Check, 
  Plus, 
  Search,
  ExternalLink,
  ShieldCheck
} from 'lucide-react';
import { KeywordRankItem, FourOhFourItem } from '../types';

export const AnalyticsTab: React.FC = () => {
  const [activeSubView, setActiveSubView] = useState<'ranks' | 'fourohfour'>('ranks');

  // Keyword Tracking State
  const [keywords, setKeywords] = useState<KeywordRankItem[]>([
    {
      id: '1',
      keyword: 'fastest wordpress seo plugin',
      url: 'https://example.com/fastest-seo-plugin/',
      currentPosition: 2,
      previousPosition: 5,
      searchVolume: 3200,
      lastUpdated: '2 hours ago',
    },
    {
      id: '2',
      keyword: 'wordpress schema graph generator',
      url: 'https://example.com/schema-graph/',
      currentPosition: 1,
      previousPosition: 1,
      searchVolume: 1800,
      lastUpdated: '4 hours ago',
    },
    {
      id: '3',
      keyword: 'litespeed smart purge seo',
      url: 'https://example.com/litespeed-cache-purge/',
      currentPosition: 4,
      previousPosition: 8,
      searchVolume: 950,
      lastUpdated: 'Yesterday',
    },
    {
      id: '4',
      keyword: 'llms txt generator wordpress',
      url: 'https://example.com/llms-txt-generator/',
      currentPosition: 3,
      previousPosition: 6,
      searchVolume: 1400,
      lastUpdated: '1 day ago',
    },
  ]);

  const [newKw, setNewKw] = useState('');
  const [newUrl, setNewUrl] = useState('');

  // 404 Monitoring State
  const [fourOhFours, setFourOhFours] = useState<FourOhFourItem[]>([
    {
      id: '1',
      url: '/old-category/speed-tips-2022/',
      hits: 42,
      referrer: 'https://external-blog.com/seo-list',
      lastDetected: '10 mins ago',
      resolved: false,
    },
    {
      id: '2',
      url: '/wp-content/uploads/2023/banner.png',
      hits: 19,
      referrer: 'Direct Traffic',
      lastDetected: '35 mins ago',
      resolved: false,
    },
    {
      id: '3',
      url: '/feed/podcast/',
      hits: 8,
      referrer: 'https://google.com/',
      lastDetected: '2 hours ago',
      resolved: true,
    },
  ]);

  const handleAddKeyword = (e: React.FormEvent) => {
    e.preventDefault();
    if (!newKw.trim()) return;

    const item: KeywordRankItem = {
      id: Date.now().toString(),
      keyword: newKw.trim(),
      url: newUrl.trim() || 'https://example.com/',
      currentPosition: Math.floor(Math.random() * 10) + 1,
      previousPosition: Math.floor(Math.random() * 15) + 5,
      searchVolume: 1200,
      lastUpdated: 'Just now',
    };

    setKeywords([item, ...keywords]);
    setNewKw('');
    setNewUrl('');
  };

  const toggleResolve404 = (id: string) => {
    setFourOhFours(
      fourOhFours.map((item) =>
        item.id === id ? { ...item, resolved: !item.resolved } : item
      )
    );
  };

  return (
    <div className="space-y-6">
      {/* Navigation Switch */}
      <div className="flex items-center justify-between bg-slate-900 border border-slate-800 rounded-xl p-4">
        <div className="flex items-center space-x-2">
          <button
            onClick={() => setActiveSubView('ranks')}
            className={`px-4 py-2 rounded-lg text-xs font-semibold transition-all flex items-center space-x-1.5 ${
              activeSubView === 'ranks'
                ? 'bg-emerald-500 text-slate-950 shadow-sm'
                : 'text-slate-400 hover:text-white hover:bg-slate-800'
            }`}
          >
            <TrendingUp className="w-3.5 h-3.5" />
            <span>Keyword SERP Tracker ({keywords.length})</span>
          </button>
          <button
            onClick={() => setActiveSubView('fourohfour')}
            className={`px-4 py-2 rounded-lg text-xs font-semibold transition-all flex items-center space-x-1.5 ${
              activeSubView === 'fourohfour'
                ? 'bg-emerald-500 text-slate-950 shadow-sm'
                : 'text-slate-400 hover:text-white hover:bg-slate-800'
            }`}
          >
            <AlertTriangle className="w-3.5 h-3.5" />
            <span>404 Error Monitor ({fourOhFours.filter(f => !f.resolved).length} active)</span>
          </button>
        </div>

        <div className="hidden sm:flex items-center space-x-2 text-xs text-slate-400">
          <ShieldCheck className="w-4 h-4 text-emerald-400" />
          <span>Local Internal DB — 0 External Tracking Beacons</span>
        </div>
      </div>

      {activeSubView === 'ranks' && (
        <div className="space-y-4">
          {/* Add Keyword Form */}
          <form onSubmit={handleAddKeyword} className="bg-slate-900 border border-slate-800 rounded-xl p-4 flex flex-col sm:flex-row gap-3">
            <input
              type="text"
              placeholder="Target Keyword..."
              value={newKw}
              onChange={(e) => setNewKw(e.target.value)}
              className="flex-1 bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:border-emerald-500"
            />
            <input
              type="text"
              placeholder="Target URL..."
              value={newUrl}
              onChange={(e) => setNewUrl(e.target.value)}
              className="flex-1 bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:border-emerald-500"
            />
            <button
              type="submit"
              className="px-4 py-2 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold text-xs rounded-lg transition-colors flex items-center justify-center space-x-1"
            >
              <Plus className="w-3.5 h-3.5" />
              <span>Track Keyword</span>
            </button>
          </form>

          {/* Keyword Table */}
          <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
            <table className="w-full text-left text-xs">
              <thead className="bg-slate-950 text-slate-400 border-b border-slate-800 font-semibold uppercase text-[10px]">
                <tr>
                  <th className="py-3 px-4">Tracked Keyword</th>
                  <th className="py-3 px-4">Rank Position</th>
                  <th className="py-3 px-4">Movement</th>
                  <th className="py-3 px-4">Est. Volume</th>
                  <th className="py-3 px-4">Last Checked</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-800/60 text-slate-200">
                {keywords.map((kw) => {
                  const diff = kw.previousPosition - kw.currentPosition;
                  return (
                    <tr key={kw.id} className="hover:bg-slate-800/40 transition-colors">
                      <td className="py-3 px-4">
                        <div className="font-semibold text-white">{kw.keyword}</div>
                        <div className="text-[11px] text-slate-400 truncate max-w-xs">{kw.url}</div>
                      </td>
                      <td className="py-3 px-4">
                        <span className="text-sm font-bold text-emerald-400 bg-emerald-500/10 px-2 py-0.5 rounded border border-emerald-500/20">
                          #{kw.currentPosition}
                        </span>
                      </td>
                      <td className="py-3 px-4">
                        {diff > 0 ? (
                          <span className="text-emerald-400 flex items-center font-medium">
                            <TrendingUp className="w-3.5 h-3.5 mr-1" /> +{diff} spots
                          </span>
                        ) : diff < 0 ? (
                          <span className="text-rose-400 flex items-center font-medium">
                            <TrendingDown className="w-3.5 h-3.5 mr-1" /> {diff} spots
                          </span>
                        ) : (
                          <span className="text-slate-400 flex items-center font-medium">
                            <Minus className="w-3.5 h-3.5 mr-1" /> Stable
                          </span>
                        )}
                      </td>
                      <td className="py-3 px-4 text-slate-300 font-mono">
                        {kw.searchVolume.toLocaleString()} / mo
                      </td>
                      <td className="py-3 px-4 text-slate-400">
                        {kw.lastUpdated}
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>
        </div>
      )}

      {activeSubView === 'fourohfour' && (
        <div className="bg-slate-900 border border-slate-800 rounded-xl overflow-hidden shadow-sm">
          <table className="w-full text-left text-xs">
            <thead className="bg-slate-950 text-slate-400 border-b border-slate-800 font-semibold uppercase text-[10px]">
              <tr>
                <th className="py-3 px-4">Broken 404 Request URL</th>
                <th className="py-3 px-4">Hit Count</th>
                <th className="py-3 px-4">Traffic Referrer</th>
                <th className="py-3 px-4">Last Detected</th>
                <th className="py-3 px-4 text-right">Action / 301 Redirect</th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-800/60 text-slate-200">
              {fourOhFours.map((item) => (
                <tr key={item.id} className="hover:bg-slate-800/40 transition-colors">
                  <td className="py-3 px-4 font-mono text-rose-300">
                    {item.url}
                  </td>
                  <td className="py-3 px-4">
                    <span className="font-semibold px-2 py-0.5 rounded bg-rose-500/10 text-rose-400 border border-rose-500/20">
                      {item.hits} Hits
                    </span>
                  </td>
                  <td className="py-3 px-4 text-slate-300">
                    {item.referrer}
                  </td>
                  <td className="py-3 px-4 text-slate-400">
                    {item.lastDetected}
                  </td>
                  <td className="py-3 px-4 text-right">
                    <button
                      onClick={() => toggleResolve404(item.id)}
                      className={`px-3 py-1 rounded text-xs font-medium transition-colors ${
                        item.resolved
                          ? 'bg-emerald-500/10 text-emerald-400 border border-emerald-500/20'
                          : 'bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700'
                      }`}
                    >
                      {item.resolved ? 'Resolved (301 Configured)' : 'Create 301 Redirect'}
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};
