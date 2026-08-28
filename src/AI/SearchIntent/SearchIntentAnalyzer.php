<?php
namespace ApexSEO\AI\SearchIntent;

class SearchIntentAnalyzer {
    public function analyze(string $keyword): string {
        $kw = strtolower($keyword);
        if (preg_match('/\b(buy|price|cost|discount|coupon|shop)\b/', $kw)) {
            return 'transactional';
        }
        if (preg_match('/\b(best|review|vs|compare|top)\b/', $kw)) {
            return 'commercial';
        }
        if (preg_match('/\b(how|what|why|who|guide|tutorial)\b/', $kw)) {
            return 'informational';
        }
        return 'navigational';
    }
}
