<?php
namespace ApexSEO\SEO\Models;

class Indexable {
    private $id;
    private $objectId;
    private $objectType = 'post';
    private $objectSubType = 'post';
    private $permalink = '';
    private $canonicalUrl = '';
    private $title = '';
    private $description = '';
    private $robotsIndex = true;
    private $robotsFollow = true;
    private $primaryFocusKeyword = '';
    private $keywordDensity = 0.0;
    private $readabilityScore = 0;
    private $contentAnalysis = [];
    private $isCornerstone = false;

    public function __construct(array $attributes = []) {
        $this->fill($attributes);
    }

    public function fill(array $attributes): void {
        if (isset($attributes['id'])) $this->id = (int)$attributes['id'];
        if (isset($attributes['object_id'])) $this->objectId = (int)$attributes['object_id'];
        if (isset($attributes['object_type'])) $this->objectType = (string)$attributes['object_type'];
        if (isset($attributes['object_sub_type'])) $this->objectSubType = (string)$attributes['object_sub_type'];
        if (isset($attributes['permalink'])) $this->permalink = (string)$attributes['permalink'];
        if (isset($attributes['canonical_url'])) $this->canonicalUrl = (string)$attributes['canonical_url'];
        if (isset($attributes['title'])) $this->title = (string)$attributes['title'];
        if (isset($attributes['description'])) $this->description = (string)$attributes['description'];
        if (isset($attributes['robots_index'])) $this->robotsIndex = (bool)$attributes['robots_index'];
        if (isset($attributes['robots_follow'])) $this->robotsFollow = (bool)$attributes['robots_follow'];
        if (isset($attributes['primary_focus_keyword'])) $this->primaryFocusKeyword = (string)$attributes['primary_focus_keyword'];
        if (isset($attributes['keyword_density'])) $this->keywordDensity = (float)$attributes['keyword_density'];
        if (isset($attributes['readability_score'])) $this->readabilityScore = (int)$attributes['readability_score'];
        if (isset($attributes['content_analysis'])) {
            $this->contentAnalysis = is_array($attributes['content_analysis']) ? $attributes['content_analysis'] : (json_decode($attributes['content_analysis'], true) ?: []);
        }
        if (isset($attributes['is_cornerstone'])) $this->isCornerstone = (bool)$attributes['is_cornerstone'];
    }

    public function getId(): ?int { return $this->id; }
    public function getObjectId(): int { return $this->objectId ?? 0; }
    public function getObjectType(): string { return $this->objectType; }
    public function getObjectSubType(): string { return $this->objectSubType; }
    public function getPermalink(): string { return $this->permalink; }
    public function getCanonicalUrl(): string { return $this->canonicalUrl; }
    public function getTitle(): string { return $this->title; }
    public function getDescription(): string { return $this->description; }
    public function getRobotsIndex(): bool { return $this->robotsIndex; }
    public function getRobotsFollow(): bool { return $this->robotsFollow; }
    public function getPrimaryFocusKeyword(): string { return $this->primaryFocusKeyword; }
    public function getKeywordDensity(): float { return $this->keywordDensity; }
    public function getReadabilityScore(): int { return $this->readabilityScore; }
    public function getContentAnalysis(): array { return $this->contentAnalysis; }
    public function isCornerstone(): bool { return $this->isCornerstone; }

    public function setId(int $id): void { $this->id = $id; }
    public function setObjectId(int $id): void { $this->objectId = $id; }
    public function setObjectType(string $type): void { $this->objectType = $type; }
    public function setObjectSubType(string $sub): void { $this->objectSubType = $sub; }
    public function setPermalink(string $link): void { $this->permalink = $link; }
    public function setCanonicalUrl(string $url): void { $this->canonicalUrl = $url; }
    public function setTitle(string $title): void { $this->title = $title; }
    public function setDescription(string $desc): void { $this->description = $desc; }
    public function setRobotsIndex(bool $idx): void { $this->robotsIndex = $idx; }
    public function setRobotsFollow(bool $flw): void { $this->robotsFollow = $flw; }
    public function setPrimaryFocusKeyword(string $kw): void { $this->primaryFocusKeyword = $kw; }
    public function setKeywordDensity(float $kd): void { $this->keywordDensity = $kd; }
    public function setReadabilityScore(int $rs): void { $this->readabilityScore = $rs; }
    public function setContentAnalysis(array $ca): void { $this->contentAnalysis = $ca; }
    public function setIsCornerstone(bool $cs): void { $this->isCornerstone = $cs; }

    public function toArray(): array {
        return [
            'id'                     => $this->id,
            'object_id'              => $this->objectId,
            'object_type'            => $this->objectType,
            'object_sub_type'        => $this->objectSubType,
            'permalink'              => $this->permalink,
            'canonical_url'          => $this->canonicalUrl,
            'title'                  => $this->title,
            'description'            => $this->description,
            'robots_index'           => $this->robotsIndex ? 1 : 0,
            'robots_follow'          => $this->robotsFollow ? 1 : 0,
            'primary_focus_keyword'  => $this->primaryFocusKeyword,
            'keyword_density'        => $this->keywordDensity,
            'readability_score'      => $this->readabilityScore,
            'content_analysis'       => $this->contentAnalysis,
            'is_cornerstone'         => $this->isCornerstone ? 1 : 0,
        ];
    }
}
