import React, { useState } from 'react';
import { 
  Search, 
  Smartphone, 
  Monitor, 
  Share2, 
  Twitter, 
  Globe, 
  Sparkles, 
  Check, 
  AlertCircle,
  Copy
} from 'lucide-react';
import { SeoPreviewState } from '../types';

export const SerpPreviewTab: React.FC = () => {
  const [device, setDevice] = useState<'desktop' | 'mobile'>('desktop');
  const [previewChannel, setPreviewChannel] = useState<'google' | 'facebook' | 'twitter'>('google');
  const [copied, setCopied] = useState(false);

  const [state, setState] = useState<SeoPreviewState>({
    titleTemplate: '%%title%% %%sep%% %%sitename%%',
    descriptionTemplate: 'Discover high performance WordPress SEO with %%sitename%%. Optimized by %%author_name%% in %%category%%.',
    canonicalUrl: 'https://example.com/high-speed-seo-guide/',
    ogImage: 'https://images.unsplash.com/photo-1460925895917-afdab827c52f?w=1200&h=630&fit=crop',
    robotsNoIndex: false,
    robotsNoFollow: false,
    pageTitle: 'High-Speed SEO Guide 2026',
    siteName: 'Apex Engine',
    separator: '|',
    authorName: 'Alex Thorne',
    category: 'Core Web Vitals',
  });

  // Calculate live dynamic values
  const resolveTemplate = (tpl: string) => {
    return tpl
      .replace(/%%title%%/g, state.pageTitle)
      .replace(/%%sitename%%/g, state.siteName)
      .replace(/%%sep%%/g, state.separator)
      .replace(/%%author_name%%/g, state.authorName)
      .replace(/%%category%%/g, state.category);
  };

  const computedTitle = resolveTemplate(state.titleTemplate);
  const computedDesc = resolveTemplate(state.descriptionTemplate);

  const titleLength = computedTitle.length;
  const descLength = computedDesc.length;

  const getTitleStatus = () => {
    if (titleLength === 0) return { color: 'text-rose-400', label: 'Empty title' };
    if (titleLength < 30) return { color: 'text-amber-400', label: 'Too short (optimal: 50-60)' };
    if (titleLength <= 60) return { color: 'text-emerald-400', label: 'Optimal length' };
    return { color: 'text-rose-400', label: 'Likely truncated on SERP (> 60)' };
  };

  const getDescStatus = () => {
    if (descLength === 0) return { color: 'text-rose-400', label: 'Empty description' };
    if (descLength < 70) return { color: 'text-amber-400', label: 'Too short (optimal: 120-160)' };
    if (descLength <= 160) return { color: 'text-emerald-400', label: 'Optimal length' };
    return { color: 'text-rose-400', label: 'Likely truncated (> 160)' };
  };

  const copyHtmlSnippet = () => {
    const snippet = `<!-- Apex SEO Automated Meta Tags -->
<title>${computedTitle}</title>
<meta name="description" content="${computedDesc}" />
<link rel="canonical" href="${state.canonicalUrl}" />
<meta name="robots" content="${state.robotsNoIndex ? 'noindex' : 'index'}, ${state.robotsNoFollow ? 'nofollow' : 'follow'}" />
<meta property="og:title" content="${computedTitle}" />
<meta property="og:description" content="${computedDesc}" />
<meta property="og:url" content="${state.canonicalUrl}" />
<meta property="og:image" content="${state.ogImage}" />
<meta name="twitter:card" content="summary_large_image" />`;

    navigator.clipboard.writeText(snippet);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
      {/* Editor Column */}
      <div className="lg:col-span-6 space-y-5">
        <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm space-y-4">
          <div className="flex items-center justify-between pb-3 border-b border-slate-800">
            <h2 className="text-base font-bold text-white flex items-center space-x-2">
              <Search className="w-4 h-4 text-emerald-400" />
              <span>Variable Engine & Meta Formatter</span>
            </h2>
            <button
              onClick={copyHtmlSnippet}
              className="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-2.5 py-1 rounded border border-slate-700 flex items-center space-x-1 transition-colors"
            >
              {copied ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
              <span>{copied ? 'Copied HTML' : 'Copy Meta HTML'}</span>
            </button>
          </div>

          {/* Quick Variable Injector */}
          <div className="bg-slate-950/60 p-3 rounded-lg border border-slate-800">
            <span className="text-[11px] font-semibold text-slate-400 block mb-1.5">
              Available Dynamic Template Variables
            </span>
            <div className="flex flex-wrap gap-1.5">
              {['%%title%%', '%%sitename%%', '%%sep%%', '%%author_name%%', '%%category%%', '%%excerpt%%'].map((v) => (
                <button
                  key={v}
                  onClick={() => {
                    setState(prev => ({ ...prev, titleTemplate: prev.titleTemplate + ' ' + v }));
                  }}
                  className="text-[11px] font-mono bg-slate-800 hover:bg-slate-700 text-emerald-400 px-2 py-0.5 rounded border border-slate-700/60 transition-colors"
                >
                  {v}
                </button>
              ))}
            </div>
          </div>

          {/* Title Template */}
          <div className="space-y-1.5">
            <div className="flex justify-between items-center text-xs">
              <label className="font-semibold text-slate-200">SEO Title Template</label>
              <span className={`text-[11px] font-medium ${getTitleStatus().color}`}>
                {titleLength}/60 chars — {getTitleStatus().label}
              </span>
            </div>
            <input
              type="text"
              value={state.titleTemplate}
              onChange={(e) => setState({ ...state, titleTemplate: e.target.value })}
              className="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-lg px-3 py-2 text-sm text-white font-mono"
            />
          </div>

          {/* Description Template */}
          <div className="space-y-1.5">
            <div className="flex justify-between items-center text-xs">
              <label className="font-semibold text-slate-200">Meta Description Template</label>
              <span className={`text-[11px] font-medium ${getDescStatus().color}`}>
                {descLength}/160 chars — {getDescStatus().label}
              </span>
            </div>
            <textarea
              rows={3}
              value={state.descriptionTemplate}
              onChange={(e) => setState({ ...state, descriptionTemplate: e.target.value })}
              className="w-full bg-slate-950 border border-slate-700 focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500 rounded-lg px-3 py-2 text-sm text-white"
            />
          </div>

          {/* Context Variables for Preview */}
          <div className="grid grid-cols-2 gap-3 pt-2 border-t border-slate-800/80">
            <div>
              <label className="text-[11px] text-slate-400 block mb-1">Post / Page Title (%%title%%)</label>
              <input
                type="text"
                value={state.pageTitle}
                onChange={(e) => setState({ ...state, pageTitle: e.target.value })}
                className="w-full bg-slate-950 border border-slate-700 rounded px-2.5 py-1.5 text-xs text-white"
              />
            </div>
            <div>
              <label className="text-[11px] text-slate-400 block mb-1">Site Name (%%sitename%%)</label>
              <input
                type="text"
                value={state.siteName}
                onChange={(e) => setState({ ...state, siteName: e.target.value })}
                className="w-full bg-slate-950 border border-slate-700 rounded px-2.5 py-1.5 text-xs text-white"
              />
            </div>
            <div>
              <label className="text-[11px] text-slate-400 block mb-1">Author (%%author_name%%)</label>
              <input
                type="text"
                value={state.authorName}
                onChange={(e) => setState({ ...state, authorName: e.target.value })}
                className="w-full bg-slate-950 border border-slate-700 rounded px-2.5 py-1.5 text-xs text-white"
              />
            </div>
            <div>
              <label className="text-[11px] text-slate-400 block mb-1">Category (%%category%%)</label>
              <input
                type="text"
                value={state.category}
                onChange={(e) => setState({ ...state, category: e.target.value })}
                className="w-full bg-slate-950 border border-slate-700 rounded px-2.5 py-1.5 text-xs text-white"
              />
            </div>
          </div>

          {/* Canonical & Robots Options */}
          <div className="pt-2 border-t border-slate-800/80 space-y-2">
            <div>
              <label className="text-[11px] text-slate-400 block mb-1">Canonical URL</label>
              <input
                type="text"
                value={state.canonicalUrl}
                onChange={(e) => setState({ ...state, canonicalUrl: e.target.value })}
                className="w-full bg-slate-950 border border-slate-700 rounded px-2.5 py-1.5 text-xs text-white font-mono"
              />
            </div>

            <div className="flex items-center gap-6 pt-1">
              <label className="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                <input
                  type="checkbox"
                  checked={state.robotsNoIndex}
                  onChange={(e) => setState({ ...state, robotsNoIndex: e.target.checked })}
                  className="rounded border-slate-700 bg-slate-950 text-emerald-500 focus:ring-emerald-500"
                />
                <span>Robots: <strong className="text-amber-400 font-mono">noindex</strong></span>
              </label>

              <label className="flex items-center space-x-2 text-xs text-slate-300 cursor-pointer">
                <input
                  type="checkbox"
                  checked={state.robotsNoFollow}
                  onChange={(e) => setState({ ...state, robotsNoFollow: e.target.checked })}
                  className="rounded border-slate-700 bg-slate-950 text-emerald-500 focus:ring-emerald-500"
                />
                <span>Robots: <strong className="text-amber-400 font-mono">nofollow</strong></span>
              </label>
            </div>
          </div>
        </div>
      </div>

      {/* Live SERP & Social Preview Column */}
      <div className="lg:col-span-6 space-y-5">
        <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm space-y-4">
          <div className="flex items-center justify-between pb-3 border-b border-slate-800">
            <h2 className="text-base font-bold text-white flex items-center space-x-2">
              <Globe className="w-4 h-4 text-emerald-400" />
              <span>Live SERP & Social Simulator</span>
            </h2>

            {/* Platform Selector */}
            <div className="flex items-center space-x-1 bg-slate-950 p-1 rounded-lg border border-slate-800">
              <button
                onClick={() => setPreviewChannel('google')}
                className={`px-2.5 py-1 rounded text-xs font-medium transition-all ${
                  previewChannel === 'google' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-400 hover:text-white'
                }`}
              >
                Google SERP
              </button>
              <button
                onClick={() => setPreviewChannel('facebook')}
                className={`px-2.5 py-1 rounded text-xs font-medium transition-all ${
                  previewChannel === 'facebook' ? 'bg-slate-800 text-blue-400 shadow-sm' : 'text-slate-400 hover:text-white'
                }`}
              >
                OpenGraph
              </button>
              <button
                onClick={() => setPreviewChannel('twitter')}
                className={`px-2.5 py-1 rounded text-xs font-medium transition-all ${
                  previewChannel === 'twitter' ? 'bg-slate-800 text-sky-400 shadow-sm' : 'text-slate-400 hover:text-white'
                }`}
              >
                Twitter Card
              </button>
            </div>
          </div>

          {/* Google SERP Preview */}
          {previewChannel === 'google' && (
            <div className="space-y-3">
              <div className="flex justify-between items-center">
                <span className="text-xs text-slate-400">Target Display Viewport:</span>
                <div className="flex items-center space-x-2">
                  <button
                    onClick={() => setDevice('desktop')}
                    className={`flex items-center space-x-1 px-2 py-1 rounded text-xs ${
                      device === 'desktop' ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white'
                    }`}
                  >
                    <Monitor className="w-3.5 h-3.5" />
                    <span>Desktop</span>
                  </button>
                  <button
                    onClick={() => setDevice('mobile')}
                    className={`flex items-center space-x-1 px-2 py-1 rounded text-xs ${
                      device === 'mobile' ? 'bg-slate-800 text-white' : 'text-slate-400 hover:text-white'
                    }`}
                  >
                    <Smartphone className="w-3.5 h-3.5" />
                    <span>Mobile</span>
                  </button>
                </div>
              </div>

              {/* Realistic Google Search Card */}
              <div className={`bg-white rounded-xl p-4 shadow-md text-slate-800 ${device === 'mobile' ? 'max-w-sm mx-auto' : ''}`}>
                {/* SERP Header & Breadcrumb */}
                <div className="flex items-center space-x-2 mb-1">
                  <div className="w-6 h-6 rounded-full bg-slate-100 border border-slate-200 flex items-center justify-center text-xs font-bold text-slate-700">
                    A
                  </div>
                  <div className="text-xs text-slate-600 leading-tight">
                    <div className="font-medium text-slate-900">{state.siteName}</div>
                    <div className="text-[11px] text-slate-500 truncate max-w-xs">{state.canonicalUrl}</div>
                  </div>
                </div>

                {/* SERP Title */}
                <h3 className="text-lg text-[#1a0dab] hover:underline font-medium cursor-pointer leading-snug break-words">
                  {computedTitle || 'Untitled Document'}
                </h3>

                {/* SERP Description */}
                <p className="text-xs text-[#4d5156] mt-1 leading-relaxed break-words">
                  {computedDesc || 'No meta description provided. Search engines will generate a fallback snippet.'}
                </p>

                {state.robotsNoIndex && (
                  <div className="mt-2.5 p-1.5 rounded bg-rose-50 border border-rose-200 text-rose-700 text-[11px] flex items-center space-x-1">
                    <AlertCircle className="w-3.5 h-3.5 shrink-0" />
                    <span>Warning: <strong>noindex</strong> header active. This page will be excluded from search indices.</span>
                  </div>
                )}
              </div>
            </div>
          )}

          {/* OpenGraph Preview */}
          {previewChannel === 'facebook' && (
            <div className="space-y-3">
              <span className="text-xs text-slate-400">Facebook / LinkedIn Feed Card:</span>
              <div className="bg-[#242526] rounded-xl overflow-hidden border border-slate-700 max-w-md mx-auto shadow-lg text-white">
                <img 
                  src={state.ogImage} 
                  alt="OG Preview" 
                  className="w-full h-48 object-cover bg-slate-800"
                />
                <div className="p-3 bg-[#3a3b3c]/50">
                  <span className="text-[11px] uppercase tracking-wider text-slate-400">
                    {new URL(state.canonicalUrl).hostname || 'EXAMPLE.COM'}
                  </span>
                  <h4 className="font-semibold text-sm text-white line-clamp-2 mt-0.5">
                    {computedTitle}
                  </h4>
                  <p className="text-xs text-slate-300 line-clamp-2 mt-1">
                    {computedDesc}
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Twitter Card Preview */}
          {previewChannel === 'twitter' && (
            <div className="space-y-3">
              <span className="text-xs text-slate-400">Twitter (X) Summary Large Image Card:</span>
              <div className="bg-black rounded-2xl overflow-hidden border border-slate-800 max-w-md mx-auto shadow-lg text-white">
                <img 
                  src={state.ogImage} 
                  alt="Twitter Card" 
                  className="w-full h-48 object-cover bg-slate-900"
                />
                <div className="p-3">
                  <span className="text-xs text-slate-500">
                    {new URL(state.canonicalUrl).hostname || 'example.com'}
                  </span>
                  <h4 className="font-semibold text-sm text-white line-clamp-1 mt-0.5">
                    {computedTitle}
                  </h4>
                  <p className="text-xs text-slate-400 line-clamp-2 mt-1">
                    {computedDesc}
                  </p>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};
