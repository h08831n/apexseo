export type TabType = 
  | 'overview' 
  | 'serp_preview' 
  | 'schema_graph' 
  | 'performance_cache' 
  | 'ai_geo_studio' 
  | 'analytics' 
  | 'architecture';

export interface ModuleStatus {
  id: string;
  name: string;
  category: string;
  state: 'BOOTED' | 'ENABLED' | 'DISABLED';
  services: string[];
  description: string;
}

export interface SeoPreviewState {
  titleTemplate: string;
  descriptionTemplate: string;
  canonicalUrl: string;
  ogImage: string;
  robotsNoIndex: boolean;
  robotsNoFollow: boolean;
  pageTitle: string;
  siteName: string;
  separator: string;
  authorName: string;
  category: string;
}

export interface SchemaConfig {
  type: 'Article' | 'Product' | 'FAQPage' | 'LocalBusiness' | 'Organization' | 'WebSite';
  headline: string;
  author: string;
  price: string;
  currency: string;
  inStock: boolean;
  rating: number;
  faqQuestions: Array<{ q: string; a: string }>;
  businessName: string;
  businessPhone: string;
  businessAddress: string;
}

export interface KeywordRankItem {
  id: string;
  keyword: string;
  url: string;
  currentPosition: number;
  previousPosition: number;
  searchVolume: number;
  lastUpdated: string;
}

export interface FourOhFourItem {
  id: string;
  url: string;
  hits: number;
  referrer: string;
  lastDetected: string;
  resolved: boolean;
}
