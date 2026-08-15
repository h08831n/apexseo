import React, { useState } from 'react';
import { 
  Zap, 
  Trash2, 
  CheckCircle2, 
  FileCode, 
  ShieldAlert, 
  Sparkles,
  RefreshCw,
  Gauge
} from 'lucide-react';

export const PerformanceTab: React.FC = () => {
  const [purgeStatus, setPurgeStatus] = useState<string | null>(null);
  const [isPurging, setIsPurging] = useState(false);

  // Minifier Demo State
  const [inputCss, setInputCss] = useState(`/* Apex Theme Base Style */
body {
    background-color: #0f172a;
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
    color: #f8fafc;
    margin: 0;
    padding: 0;
}

.hero-banner {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 3rem 2rem;
}`);

  const [minifiedCss, setMinifiedCss] = useState('');

  const handleMinify = () => {
    // Mimic ApexSEO\Performance\Assets\CssMinifier
    let css = inputCss;
    css = css.replace(/\/\*[\s\S]*?\*\//g, '');
    css = css.replace(/\s+/g, ' ');
    css = css.replace(/\s*([\{\}:;,>])\s*/g, '$1');
    css = css.replace(/;}/g, '}');
    setMinifiedCss(css.trim());
  };

  const handlePurgeCache = () => {
    setIsPurging(true);
    setTimeout(() => {
      setIsPurging(false);
      setPurgeStatus('Purged all cache tags across LiteSpeed, Nginx Microcache, & Static File Cache');
      setTimeout(() => setPurgeStatus(null), 4000);
    }, 800);
  };

  return (
    <div className="space-y-6">
      {/* Top Header Card */}
      <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm flex flex-col md:flex-row md:items-center justify-between gap-4">
        <div className="space-y-1">
          <div className="flex items-center space-x-2">
            <span className="text-xs px-2.5 py-0.5 rounded-full bg-emerald-500/10 text-emerald-400 font-semibold border border-emerald-500/20">
              Smart Purge Engine Active
            </span>
            <span className="text-xs text-slate-400">Server Adapter: LiteSpeed / OpenLiteSpeed / Nginx</span>
          </div>
          <h2 className="text-lg font-bold text-white tracking-tight">
            High-Performance Cache & Asset Optimization
          </h2>
        </div>

        <div className="flex items-center space-x-3">
          <button
            onClick={handlePurgeCache}
            disabled={isPurging}
            className="px-4 py-2 bg-rose-500/10 hover:bg-rose-500/20 text-rose-400 border border-rose-500/30 font-semibold text-xs rounded-lg transition-colors flex items-center space-x-1.5"
          >
            {isPurging ? (
              <RefreshCw className="w-3.5 h-3.5 animate-spin" />
            ) : (
              <Trash2 className="w-3.5 h-3.5" />
            )}
            <span>{isPurging ? 'Purging Server Cache...' : 'Smart Purge All Cache'}</span>
          </button>
        </div>
      </div>

      {purgeStatus && (
        <div className="p-3 bg-emerald-950/60 border border-emerald-500/30 rounded-lg text-emerald-300 text-xs flex items-center space-x-2">
          <CheckCircle2 className="w-4 h-4 text-emerald-400 shrink-0" />
          <span>{purgeStatus}</span>
        </div>
      )}

      {/* Feature Grid */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div className="bg-slate-900 border border-slate-800 rounded-xl p-4.5 space-y-2">
          <div className="flex items-center justify-between">
            <h3 className="text-sm font-bold text-white">Delay JS Execution</h3>
            <span className="text-[10px] uppercase font-semibold text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded">
              Enabled
            </span>
          </div>
          <p className="text-xs text-slate-400 leading-relaxed">
            Delays non-critical JavaScript until user interaction (scroll, touch, click, keydown), maximizing initial Lighthouse INP and Total Blocking Time (TBT).
          </p>
        </div>

        <div className="bg-slate-900 border border-slate-800 rounded-xl p-4.5 space-y-2">
          <div className="flex items-center justify-between">
            <h3 className="text-sm font-bold text-white">Critical LCP Acceleration</h3>
            <span className="text-[10px] uppercase font-semibold text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded">
              Enabled
            </span>
          </div>
          <p className="text-xs text-slate-400 leading-relaxed">
            Excludes above-the-fold hero images from lazy loading and injects <code className="text-emerald-300 font-mono">fetchpriority="high"</code> and <code className="text-emerald-300 font-mono">loading="eager"</code>.
          </p>
        </div>

        <div className="bg-slate-900 border border-slate-800 rounded-xl p-4.5 space-y-2">
          <div className="flex items-center justify-between">
            <h3 className="text-sm font-bold text-white">Resource Hints & Preconnect</h3>
            <span className="text-[10px] uppercase font-semibold text-emerald-400 bg-emerald-500/10 px-1.5 py-0.5 rounded">
              Enabled
            </span>
          </div>
          <p className="text-xs text-slate-400 leading-relaxed">
            Automatically injects DNS prefetch and preconnect tags for external fonts, CDNs, and Google Analytics without blocking rendering.
          </p>
        </div>
      </div>

      {/* Minification Interactive Lab */}
      <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm space-y-4">
        <div className="flex items-center justify-between pb-3 border-b border-slate-800">
          <div className="flex items-center space-x-2">
            <FileCode className="w-4 h-4 text-emerald-400" />
            <h3 className="text-sm font-bold text-white">Zero-Regex CSS/JS Minifier Engine Lab</h3>
          </div>
          <button
            onClick={handleMinify}
            className="px-3 py-1.5 bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-semibold text-xs rounded-lg transition-colors flex items-center space-x-1"
          >
            <Sparkles className="w-3.5 h-3.5" />
            <span>Minify Test CSS</span>
          </button>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="text-xs text-slate-400 block mb-1.5 font-medium">Input Raw CSS ({inputCss.length} bytes)</label>
            <textarea
              rows={8}
              value={inputCss}
              onChange={(e) => setInputCss(e.target.value)}
              className="w-full bg-slate-950 border border-slate-800 rounded-lg p-3 text-xs font-mono text-slate-300 focus:border-emerald-500"
            />
          </div>

          <div>
            <label className="text-xs text-slate-400 block mb-1.5 font-medium">
              Minified Output {minifiedCss ? `(${minifiedCss.length} bytes - saved ${Math.round((1 - minifiedCss.length / inputCss.length) * 100)}%)` : ''}
            </label>
            <textarea
              rows={8}
              readOnly
              value={minifiedCss || 'Click "Minify Test CSS" to test compression...'}
              className="w-full bg-slate-950 border border-slate-800 rounded-lg p-3 text-xs font-mono text-emerald-300"
            />
          </div>
        </div>
      </div>
    </div>
  );
};
