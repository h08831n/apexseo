<?php
namespace ApexSEO\Schema\Types;

/**
 * Schema.org FAQPage Structured Data Generator.
 */
class FAQPageSchema extends AbstractSchemaType {
    /**
     * {@inheritdoc}
     */
    public function getType() {
        return 'FAQPage';
    }

    /**
     * {@inheritdoc}
     */
    public function isApplicable(array $context = []) {
        return !empty($context['faq_items']) && is_array($context['faq_items']);
    }

    /**
     * {@inheritdoc}
     */
    public function generate(array $context = []) {
        $questions = [];
        $items = !empty($context['faq_items']) ? $context['faq_items'] : [];

        foreach ($items as $item) {
            if (empty($item['question']) || empty($item['answer'])) {
                continue;
            }
            $questions[] = [
                '@type'          => 'Question',
                'name'           => $item['question'],
                'acceptedAnswer' => [
                    '@type' => 'Answer',
                    'text'  => $item['answer'],
                ],
            ];
        }

        return $this->cleanData([
            '@type'      => 'FAQPage',
            'mainEntity' => $questions,
        ]);
    }
}
