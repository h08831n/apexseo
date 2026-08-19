<?php
namespace ApexSEO\Schema\Types;

/**
 * JobPosting Structured Data Schema Type (APEX-073).
 * Conforms to Google JobPosting Rich Result specifications.
 */
class JobPostingSchema extends AbstractSchemaType {
    /**
     * Get Schema.org type name.
     *
     * @return string
     */
    public function getType() {
        return 'JobPosting';
    }

    /**
     * Determine if JobPosting schema applies to context.
     *
     * @param array $context
     * @return bool
     */
    public function isApplicable(array $context = []) {
        return !empty($context['is_job_posting']) || (!empty($context['schema_type']) && $context['schema_type'] === 'JobPosting');
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
            '@type'             => 'JobPosting',
            '@id'               => $canonical . '#jobposting',
            'title'             => isset($context['title']) ? $context['title'] : '',
            'description'       => isset($context['description']) ? $context['description'] : '',
            'datePosted'        => isset($context['date_posted']) ? $context['date_posted'] : (isset($context['date_published']) ? $context['date_published'] : date('c')),
            'validThrough'      => isset($context['valid_through']) ? $context['valid_through'] : date('c', strtotime('+30 days')),
            'employmentType'    => isset($context['employment_type']) ? $context['employment_type'] : 'FULL_TIME',
            'hiringOrganization' => [
                '@type'  => 'Organization',
                'name'   => isset($context['hiring_organization']) ? $context['hiring_organization'] : get_bloginfo('name'),
                'sameAs' => isset($context['organization_url']) ? $context['organization_url'] : home_url(),
            ],
            'jobLocation'       => [
                '@type'   => 'Place',
                'address' => [
                    '@type'           => 'PostalAddress',
                    'streetAddress'   => isset($context['job_street']) ? $context['job_street'] : '',
                    'addressLocality' => isset($context['job_city']) ? $context['job_city'] : '',
                    'addressRegion'   => isset($context['job_region']) ? $context['job_region'] : '',
                    'postalCode'      => isset($context['job_postal_code']) ? $context['job_postal_code'] : '',
                    'addressCountry'  => isset($context['job_country']) ? $context['job_country'] : 'US',
                ],
            ],
        ];

        if (!empty($context['is_remote'])) {
            $data['jobLocationType'] = 'TELECOMMUTE';
            $data['applicantLocationRequirements'] = [
                '@type' => 'Country',
                'name'  => isset($context['remote_country']) ? $context['remote_country'] : 'Worldwide',
            ];
        }

        if (!empty($context['base_salary_value'])) {
            $data['baseSalary'] = [
                '@type'    => 'MonetaryAmount',
                'currency' => isset($context['currency']) ? $context['currency'] : 'USD',
                'value'    => [
                    '@type'    => 'QuantitativeValue',
                    'value'    => (float) $context['base_salary_value'],
                    'unitText' => isset($context['salary_unit']) ? $context['salary_unit'] : 'YEAR',
                ],
            ];
        }

        return $data;
    }
}
