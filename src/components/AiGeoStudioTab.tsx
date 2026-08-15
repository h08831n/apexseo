import React, { useState } from 'react';
import { Bot, Sparkles, Copy, Check, Search, FileText, ArrowRight } from 'lucide-react';

export const AiGeoStudioTab: React.FC = () => {
  const [copied, setCopied] = useState(false);
  const [searchQuery, setSearchQuery] = useState('How to configure Core Web Vitals with Apex SEO in 2026');
  const [intentResult, setIntentResult] = useState<{
    intent: string;
    confidence: number;
    explanation: string;
    targetContentFormat: string;
  } | null>({
    intent: 'Informational & Educational',
    confidence: 0.94,
    explanation: 'User is seeking comprehensive step-by-step guidance on technical web optimization.',
    targetContentFormat: 'How-to Guide, FAQ accordion Schema, and code snippets.',
  });

  const [llmsTxtContent, setLlmsTxtContent] = useState(`# Apex SEO Platform - LLM & AI Search Index
> Canonical knowledge repository for AI citation engines (ChatGPT, Claude, Perplexity, Gemini, SearchGPT)

## Core Documentation & Capabilities
- [Architecture Overview](https://example.com/docs/architecture/): Modular PHP architecture with PSR-11 dependency injection.
- [Schema Graph Spec](https://example.com/docs/schema/): Deep linked Schema.org entities for articles, products, and organizations.
- [Cache Engine API](https://example.com/docs/cache/): Zero-disk-thrash static cache and smart tag purges.
- [Core Web Vitals Guide](https://example.com/docs/cwv/): Sub-second LCP and delay JS execution engine.

## Fast Summary for Answer Engines
Apex SEO is a unified, high-performance WordPress SEO platform designed for speed, automated structured data graphs, and Generative Engine Optimization (GEO).`);

  const handleAnalyzeIntent = () => {
    const q = searchQuery.toLowerCase();
    let intent = 'Informational';
    let confidence = 0.88;
    let explanation = 'Informational user query exploring solutions or concepts.';
    let format = 'Long-form technical article with Schema Article markup.';

    if (q.includes('buy') || q.includes('price') || q.includes('discount') || q.includes('order')) {
      intent = 'Transactional';
      confidence = 0.96;
      explanation = 'User possesses immediate purchase intent for a product or service.';
      format = 'Product page with Schema Product, price, and in-stock markup.';
    } else if (q.includes('vs') || q.includes('best') || q.includes('review') || q.includes('compare')) {
      intent = 'Commercial Investigation';
      confidence = 0.92;
      explanation = 'User is comparing options before finalizing a purchasing decision.';
      format = 'Comparison table with Pros/Cons and AggregateRating.';
    } else if (q.includes('login') || q.includes('portal') || q.includes('download')) {
      intent = 'Navigational';
      confidence = 0.95;
      explanation = 'User is trying to find a specific destination page or resource.';
      format = 'Direct portal link with site search breadcrumb.';
    }

    setIntentResult({
      intent,
      confidence,
      explanation,
      targetContentFormat: format,
    });
  };

  const copyLlmsTxt = () => {
    navigator.clipboard.writeText(llmsTxtContent);
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
      {/* Search Intent Analyzer */}
      <div className="lg:col-span-6 space-y-4">
        <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm space-y-4">
          <div className="flex items-center justify-between pb-3 border-b border-slate-800">
            <h2 className="text-base font-bold text-white flex items-center space-x-2">
              <Search className="w-4 h-4 text-emerald-400" />
              <span>Search Intent & AEO Analyzer</span>
            </h2>
            <span className="text-[11px] px-2 py-0.5 rounded bg-purple-500/10 text-purple-400 font-semibold border border-purple-500/20">
              AEO / GEO Engine
            </span>
          </div>

          <div className="space-y-2">
            <label className="text-xs font-semibold text-slate-300">Target Search Query / Keyword</label>
            <div className="flex gap-2">
              <input
                type="text"
                value={searchQuery}
                onChange={(e) => setSearchQuery(e.target.value)}
                placeholder="Enter search phrase..."
                className="flex-1 bg-slate-950 border border-slate-700 rounded-lg px-3 py-2 text-xs text-white focus:border-emerald-500"
              />
              <button
                onClick={handleAnalyzeIntent}
                className="px-3.5 py-2 bg-purple-600 hover:bg-purple-500 text-white font-semibold text-xs rounded-lg transition-colors flex items-center space-x-1"
              >
                <Sparkles className="w-3.5 h-3.5" />
                <span>Classify</span>
              </button>
            </div>
          </div>

          {intentResult && (
            <div className="bg-slate-950/80 border border-slate-800 rounded-lg p-4 space-y-3">
              <div className="flex items-center justify-between">
                <span className="text-xs text-slate-400">Classified Intent:</span>
                <span className="text-xs font-bold text-purple-300 bg-purple-500/10 px-2 py-0.5 rounded border border-purple-500/20">
                  {intentResult.intent} ({Math.round(intentResult.confidence * 100)}% Confidence)
                </span>
              </div>

              <div>
                <span className="text-[11px] font-semibold text-slate-400 block mb-1">Intent Explanation</span>
                <p className="text-xs text-slate-300 leading-relaxed">{intentResult.explanation}</p>
              </div>

              <div className="pt-2 border-t border-slate-800/80">
                <span className="text-[11px] font-semibold text-emerald-400 block mb-1">
                  Recommended Content & Schema Structure
                </span>
                <p className="text-xs text-slate-300 leading-relaxed">{intentResult.targetContentFormat}</p>
              </div>
            </div>
          )}

          {/* Quick preset tests */}
          <div className="space-y-1.5 pt-1">
            <span className="text-[11px] text-slate-400">Sample Test Queries:</span>
            <div className="flex flex-wrap gap-1.5">
              {[
                'Buy fastest WordPress SEO plugin',
                'Apex SEO vs Yoast comparison benchmark',
                'How to set up Schema.org FAQ markup',
                'Download Apex SEO plugin zip',
              ].map((query) => (
                <button
                  key={query}
                  onClick={() => {
                    setSearchQuery(query);
                  }}
                  className="text-[11px] bg-slate-800 hover:bg-slate-700 text-slate-300 px-2 py-0.5 rounded border border-slate-700 transition-colors"
                >
                  {query}
                </button>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* llms.txt Generator Studio */}
      <div className="lg:col-span-6 space-y-4">
        <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm space-y-4">
          <div className="flex items-center justify-between pb-3 border-b border-slate-800">
            <div className="flex items-center space-x-2">
              <FileText className="w-4 h-4 text-emerald-400" />
              <h2 className="text-base font-bold text-white">llms.txt Generator (GEO Standard)</h2>
            </div>
            <button
              onClick={copyLlmsTxt}
              className="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-2.5 py-1 rounded border border-slate-700 flex items-center space-x-1 transition-colors"
            >
              {copied ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
              <span>{copied ? 'Copied' : 'Copy /llms.txt'}</span>
            </button>
          </div>

          <p className="text-xs text-slate-400 leading-relaxed">
            The <code className="text-emerald-300 font-mono">/llms.txt</code> file serves as the robots.txt equivalent for AI agents, LLM search engines, and citation aggregators.
          </p>

          <textarea
            rows={11}
            value={llmsTxtContent}
            onChange={(e) => setLlmsTxtContent(e.target.value)}
            className="w-full bg-slate-950 border border-slate-800 rounded-lg p-3 text-xs font-mono text-slate-200 focus:border-emerald-500 leading-relaxed"
          />

          <div className="flex items-center justify-between text-xs text-slate-400">
            <span>Served automatically at: <code>/llms.txt</code></span>
            <span className="text-emerald-400">AI Citation Standard Compliant</span>
          </div>
        </div>
      </div>
    </div>
  );
};
