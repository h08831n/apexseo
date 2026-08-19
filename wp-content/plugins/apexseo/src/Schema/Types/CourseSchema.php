<?php
namespace ApexSEO\Schema\Types;

/**
 * Course Structured Data Schema Type (APEX-074).
 * Conforms to Google Course Rich Result specifications.
 */
class CourseSchema extends AbstractSchemaType {
    /**
     * Get Schema.org type name.
     *
     * @return string
     */
    public function getType() {
        return 'Course';
    }

    /**
     * Determine if Course schema applies to context.
     *
     * @param array $context
     * @return bool
     */
    public function isApplicable(array $context = []) {
        return !empty($context['is_course']) || (!empty($context['schema_type']) && $context['schema_type'] === 'Course');
    }

    /**
     * Generate Schema.org structured data array.
     *
     * @param array $context
     * @return array
     */
    public function generate(array $context = []) {
        $canonical = $this->getCanonicalUrl($context);
        $data = [
            '@type'        => 'Course',
            '@id'          => $canonical . '#course',
            'name'         => isset($context['title']) ? $context['title'] : '',
            'description'  => isset($context['description']) ? $context['description'] : '',
            'provider'     => [
                '@type'  => 'Organization',
                'name'   => isset($context['course_provider']) ? $context['course_provider'] : get_bloginfo('name'),
                'sameAs' => isset($context['provider_url']) ? $context['provider_url'] : home_url(),
            ],
        ];

        if (!empty($context['course_code'])) {
            $data['courseCode'] = $context['course_code'];
        }

        if (!empty($context['educational_credential'])) {
            $data['educationalCredentialAwarded'] = $context['educational_credential'];
        }

        if (!empty($context['has_course_instance'])) {
            $data['hasCourseInstance'] = [
                '@type'            => 'CourseInstance',
                'courseMode'       => isset($context['course_mode']) ? $context['course_mode'] : 'online',
                'courseWorkload'   => isset($context['course_workload']) ? $context['course_workload'] : 'PT10H',
            ];
        }

        if (isset($context['price'])) {
            $data['offers'] = [
                '@type'         => 'Offer',
                'category'      => 'Paid',
                'price'         => (string) $context['price'],
                'priceCurrency' => isset($context['currency']) ? $context['currency'] : 'USD',
                'availability'  => 'https://schema.org/InStock',
            ];
        }

        return $data;
    }
}
