<?php
namespace ApexSEO\Schema\Types;

/**
 * Event Structured Data Schema Type (APEX-075).
 * Conforms to Google Event Rich Result specifications.
 */
class EventSchema extends AbstractSchemaType {
    /**
     * Get Schema.org type name.
     *
     * @return string
     */
    public function getType() {
        return 'Event';
    }

    /**
     * Determine if Event schema applies to context.
     *
     * @param array $context
     * @return bool
     */
    public function isApplicable(array $context = []) {
        return !empty($context['is_event']) || (!empty($context['schema_type']) && $context['schema_type'] === 'Event');
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
            '@type'             => 'Event',
            '@id'               => $canonical . '#event',
            'name'              => isset($context['title']) ? $context['title'] : '',
            'description'       => isset($context['description']) ? $context['description'] : '',
            'startDate'         => isset($context['event_start_date']) ? $context['event_start_date'] : date('c', strtotime('+7 days')),
            'eventStatus'       => isset($context['event_status']) ? $context['event_status'] : 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => isset($context['event_attendance_mode']) ? $context['event_attendance_mode'] : 'https://schema.org/OfflineEventAttendanceMode',
            'organizer'         => [
                '@type'  => 'Organization',
                'name'   => isset($context['organizer_name']) ? $context['organizer_name'] : get_bloginfo('name'),
                'url'    => isset($context['organizer_url']) ? $context['organizer_url'] : home_url(),
            ],
        ];

        if (!empty($context['event_end_date'])) {
            $data['endDate'] = $context['event_end_date'];
        }

        if (!empty($context['featured_image'])) {
            $data['image'] = [
                '@type' => 'ImageObject',
                'url'   => $context['featured_image'],
            ];
        }

        // Location handling (Physical vs Virtual)
        if (!empty($context['is_online_event'])) {
            $data['eventAttendanceMode'] = 'https://schema.org/OnlineEventAttendanceMode';
            $data['location'] = [
                '@type' => 'VirtualLocation',
                'url'   => isset($context['event_stream_url']) ? $context['event_stream_url'] : $canonical,
            ];
        } else {
            $data['location'] = [
                '@type'   => 'Place',
                'name'    => isset($context['venue_name']) ? $context['venue_name'] : 'Event Venue',
                'address' => [
                    '@type'           => 'PostalAddress',
                    'streetAddress'   => isset($context['venue_street']) ? $context['venue_street'] : '',
                    'addressLocality' => isset($context['venue_city']) ? $context['venue_city'] : '',
                    'addressRegion'   => isset($context['venue_region']) ? $context['venue_region'] : '',
                    'postalCode'      => isset($context['venue_postal_code']) ? $context['venue_postal_code'] : '',
                    'addressCountry'  => isset($context['venue_country']) ? $context['venue_country'] : 'US',
                ],
            ];
        }

        if (isset($context['price'])) {
            $data['offers'] = [
                '@type'         => 'Offer',
                'price'         => (string) $context['price'],
                'priceCurrency' => isset($context['currency']) ? $context['currency'] : 'USD',
                'availability'  => 'https://schema.org/InStock',
                'validFrom'     => isset($context['valid_from']) ? $context['valid_from'] : date('c'),
                'url'           => $canonical,
            ];
        }

        return $data;
    }
}
