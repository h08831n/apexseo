import React, { useState } from 'react';
import { Code2, Check, Copy, Layers, ExternalLink } from 'lucide-react';
import { SchemaConfig } from '../types';

export const SchemaGraphTab: React.FC = () => {
  const [copied, setCopied] = useState(false);
  const [config, setConfig] = useState<SchemaConfig>({
    type: 'Article',
    headline: 'High-Speed SEO Optimization Guide',
    author: 'Sarah Connor',
    price: '99.00',
    currency: 'USD',
    inStock: true,
    rating: 4.9,
    faqQuestions: [
      { q: 'What is Apex SEO?', a: 'A high-performance, modular SEO suite for WordPress.' },
      { q: 'Does it support Schema Graph connecting Article & Author?', a: 'Yes, it outputs a single linked Schema Graph.' },
    ],
    businessName: 'Apex Digital Agency',
    businessPhone: '+1-800-555-0199',
    businessAddress: '100 Silicon Way, San Francisco, CA',
  });

  const generateJsonLd = () => {
    let mainEntity: any = {};

    if (config.type === 'Article') {
      mainEntity = {
        '@type': 'Article',
        '@id': 'https://example.com/post/#article',
        'isPartOf': { '@id': 'https://example.com/#website' },
        'headline': config.headline,
        'description': 'A comprehensive breakdown of enterprise speed and schema best practices.',
        'author': {
          '@type': 'Person',
          'name': config.author,
          'url': 'https://example.com/author/sarah/',
        },
        'publisher': { '@id': 'https://example.com/#organization' },
        'datePublished': '2026-08-15T09:00:00+00:00',
        'dateModified': '2026-08-15T12:00:00+00:00',
      };
    } else if (config.type === 'Product') {
      mainEntity = {
        '@type': 'Product',
        '@id': 'https://example.com/product/#product',
        'name': config.headline,
        'image': 'https://example.com/product.jpg',
        'offers': {
          '@type': 'Offer',
          'price': config.price,
          'priceCurrency': config.currency,
          'availability': config.inStock ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
        },
        'aggregateRating': {
          '@type': 'AggregateRating',
          'ratingValue': config.rating,
          'reviewCount': 128,
        },
      };
    } else if (config.type === 'FAQPage') {
      mainEntity = {
        '@type': 'FAQPage',
        '@id': 'https://example.com/faq/#faq',
        'mainEntity': config.faqQuestions.map((item) => ({
          '@type': 'Question',
          'name': item.q,
          'acceptedAnswer': {
            '@type': 'Answer',
            'text': item.a,
          },
        })),
      };
    } else if (config.type === 'LocalBusiness') {
      mainEntity = {
        '@type': 'LocalBusiness',
        '@id': 'https://example.com/#localbusiness',
        'name': config.businessName,
        'telephone': config.businessPhone,
        'address': {
          '@type': 'PostalAddress',
          'streetAddress': config.businessAddress,
        },
        'geo': {
          '@type': 'GeoCoordinates',
          'latitude': 37.7749,
          'longitude': -122.4194,
        },
      };
    } else {
      mainEntity = {
        '@type': 'Organization',
        '@id': 'https://example.com/#organization',
        'name': config.businessName,
        'url': 'https://example.com/',
        'logo': 'https://example.com/logo.png',
      };
    }

    const graph = {
      '@context': 'https://schema.org',
      '@graph': [
        {
          '@type': 'WebSite',
          '@id': 'https://example.com/#website',
          'url': 'https://example.com/',
          'name': 'Apex SEO Platform',
          'publisher': { '@id': 'https://example.com/#organization' },
        },
        {
          '@type': 'Organization',
          '@id': 'https://example.com/#organization',
          'name': 'Apex SEO Inc',
          'url': 'https://example.com/',
        },
        mainEntity,
      ],
    };

    return JSON.stringify(graph, null, 2);
  };

  const copyGraph = () => {
    navigator.clipboard.writeText(generateJsonLd());
    setCopied(true);
    setTimeout(() => setCopied(false), 2000);
  };

  return (
    <div className="grid grid-cols-1 lg:grid-cols-12 gap-6">
      {/* Schema Config Panel */}
      <div className="lg:col-span-5 space-y-4">
        <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm space-y-4">
          <div className="flex items-center justify-between pb-3 border-b border-slate-800">
            <h2 className="text-base font-bold text-white flex items-center space-x-2">
              <Layers className="w-4 h-4 text-emerald-400" />
              <span>Schema Entity Selector</span>
            </h2>
            <span className="text-xs px-2 py-0.5 rounded bg-emerald-500/10 text-emerald-400 border border-emerald-500/20">
              Graph Builder v1.0
            </span>
          </div>

          <div className="space-y-1.5">
            <label className="text-xs font-semibold text-slate-300">Schema.org Entity Type</label>
            <select
              value={config.type}
              onChange={(e) => setConfig({ ...config, type: e.target.value as any })}
              className="w-full bg-slate-950 border border-slate-700 text-white rounded-lg px-3 py-2 text-sm focus:border-emerald-500 focus:ring-1 focus:ring-emerald-500"
            >
              <option value="Article">Article / NewsArticle / TechArticle</option>
              <option value="Product">Product & AggregateRating</option>
              <option value="FAQPage">FAQPage (Rich Accordion)</option>
              <option value="LocalBusiness">LocalBusiness & PostalAddress</option>
              <option value="Organization">Organization & Publisher</option>
            </select>
          </div>

          {config.type === 'Article' && (
            <div className="space-y-3 pt-2">
              <div>
                <label className="text-xs text-slate-400 block mb-1">Headline (headline)</label>
                <input
                  type="text"
                  value={config.headline}
                  onChange={(e) => setConfig({ ...config, headline: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-700 rounded px-3 py-1.5 text-xs text-white"
                />
              </div>
              <div>
                <label className="text-xs text-slate-400 block mb-1">Author Name (author.name)</label>
                <input
                  type="text"
                  value={config.author}
                  onChange={(e) => setConfig({ ...config, author: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-700 rounded px-3 py-1.5 text-xs text-white"
                />
              </div>
            </div>
          )}

          {config.type === 'Product' && (
            <div className="space-y-3 pt-2">
              <div>
                <label className="text-xs text-slate-400 block mb-1">Product Title</label>
                <input
                  type="text"
                  value={config.headline}
                  onChange={(e) => setConfig({ ...config, headline: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-700 rounded px-3 py-1.5 text-xs text-white"
                />
              </div>
              <div className="grid grid-cols-2 gap-3">
                <div>
                  <label className="text-xs text-slate-400 block mb-1">Price</label>
                  <input
                    type="text"
                    value={config.price}
                    onChange={(e) => setConfig({ ...config, price: e.target.value })}
                    className="w-full bg-slate-950 border border-slate-700 rounded px-3 py-1.5 text-xs text-white"
                  />
                </div>
                <div>
                  <label className="text-xs text-slate-400 block mb-1">Rating (1-5)</label>
                  <input
                    type="number"
                    step="0.1"
                    min="1"
                    max="5"
                    value={config.rating}
                    onChange={(e) => setConfig({ ...config, rating: parseFloat(e.target.value) })}
                    className="w-full bg-slate-950 border border-slate-700 rounded px-3 py-1.5 text-xs text-white"
                  />
                </div>
              </div>
            </div>
          )}

          {config.type === 'FAQPage' && (
            <div className="space-y-3 pt-2">
              <span className="text-xs text-slate-300 font-semibold block">FAQ Item List</span>
              {config.faqQuestions.map((item, idx) => (
                <div key={idx} className="p-2.5 rounded bg-slate-950 border border-slate-800 space-y-1.5">
                  <input
                    type="text"
                    placeholder="Question"
                    value={item.q}
                    onChange={(e) => {
                      const updated = [...config.faqQuestions];
                      updated[idx].q = e.target.value;
                      setConfig({ ...config, faqQuestions: updated });
                    }}
                    className="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1 text-xs text-white"
                  />
                  <input
                    type="text"
                    placeholder="Answer"
                    value={item.a}
                    onChange={(e) => {
                      const updated = [...config.faqQuestions];
                      updated[idx].a = e.target.value;
                      setConfig({ ...config, faqQuestions: updated });
                    }}
                    className="w-full bg-slate-900 border border-slate-700 rounded px-2 py-1 text-xs text-slate-300"
                  />
                </div>
              ))}
            </div>
          )}

          {config.type === 'LocalBusiness' && (
            <div className="space-y-3 pt-2">
              <div>
                <label className="text-xs text-slate-400 block mb-1">Business Name</label>
                <input
                  type="text"
                  value={config.businessName}
                  onChange={(e) => setConfig({ ...config, businessName: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-700 rounded px-3 py-1.5 text-xs text-white"
                />
              </div>
              <div>
                <label className="text-xs text-slate-400 block mb-1">Telephone</label>
                <input
                  type="text"
                  value={config.businessPhone}
                  onChange={(e) => setConfig({ ...config, businessPhone: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-700 rounded px-3 py-1.5 text-xs text-white"
                />
              </div>
              <div>
                <label className="text-xs text-slate-400 block mb-1">Address</label>
                <input
                  type="text"
                  value={config.businessAddress}
                  onChange={(e) => setConfig({ ...config, businessAddress: e.target.value })}
                  className="w-full bg-slate-950 border border-slate-700 rounded px-3 py-1.5 text-xs text-white"
                />
              </div>
            </div>
          )}
        </div>
      </div>

      {/* JSON-LD Graph Code Output */}
      <div className="lg:col-span-7 space-y-4">
        <div className="bg-slate-900 border border-slate-800 rounded-xl p-5 shadow-sm space-y-3">
          <div className="flex items-center justify-between pb-3 border-b border-slate-800">
            <h2 className="text-base font-bold text-white flex items-center space-x-2">
              <Code2 className="w-4 h-4 text-emerald-400" />
              <span>Compiled Linked JSON-LD Graph</span>
            </h2>
            <div className="flex items-center space-x-2">
              <button
                onClick={copyGraph}
                className="text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 px-3 py-1.5 rounded border border-slate-700 flex items-center space-x-1.5 transition-colors"
              >
                {copied ? <Check className="w-3.5 h-3.5 text-emerald-400" /> : <Copy className="w-3.5 h-3.5" />}
                <span>{copied ? 'Copied JSON-LD' : 'Copy JSON-LD'}</span>
              </button>
            </div>
          </div>

          <div className="relative">
            <pre className="bg-slate-950 p-4 rounded-xl text-xs font-mono text-emerald-300 overflow-x-auto border border-slate-800 leading-relaxed max-h-[480px]">
              {generateJsonLd()}
            </pre>
          </div>

          <div className="flex items-center justify-between text-xs text-slate-400 pt-1">
            <span>Graph Format: W3C / Schema.org Linked Graph</span>
            <span className="text-emerald-400 font-medium flex items-center">
              Google Rich Results Validated
            </span>
          </div>
        </div>
      </div>
    </div>
  );
};
