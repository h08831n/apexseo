<?php
namespace ApexSEO\Schema\Types;

/**
 * Recipe Structured Data Schema Type (APEX-072).
 * Conforms to Google Recipe Rich Result specifications.
 */
class RecipeSchema extends AbstractSchemaType {
    /**
     * Get Schema.org type name.
     *
     * @return string
     */
    public function getType() {
        return 'Recipe';
    }

    /**
     * Determine if Recipe schema applies to context.
     *
     * @param array $context
     * @return bool
     */
    public function isApplicable(array $context = []) {
        return !empty($context['is_recipe']) || (!empty($context['schema_type']) && $context['schema_type'] === 'Recipe');
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
            '@type'            => 'Recipe',
            '@id'              => $canonical . '#recipe',
            'name'             => isset($context['title']) ? $context['title'] : '',
            'description'      => isset($context['description']) ? $context['description'] : '',
            'datePublished'    => isset($context['date_published']) ? $context['date_published'] : date('c'),
            'author'           => [
                '@type' => 'Person',
                'name'  => isset($context['author_name']) ? $context['author_name'] : get_bloginfo('name'),
            ],
            'recipeIngredient' => isset($context['recipe_ingredients']) && is_array($context['recipe_ingredients'])
                ? $context['recipe_ingredients']
                : [],
            'recipeInstructions' => [],
        ];

        // Format Recipe Instructions into HowToStep objects
        if (!empty($context['recipe_instructions']) && is_array($context['recipe_instructions'])) {
            foreach ($context['recipe_instructions'] as $index => $step) {
                $stepText = is_array($step) ? (isset($step['text']) ? $step['text'] : '') : (string) $step;
                $data['recipeInstructions'][] = [
                    '@type' => 'HowToStep',
                    'text'  => $stepText,
                    'position' => $index + 1,
                ];
            }
        }

        if (!empty($context['featured_image'])) {
            $data['image'] = [
                '@type' => 'ImageObject',
                'url'   => $context['featured_image'],
            ];
        }

        if (!empty($context['prep_time'])) {
            $data['prepTime'] = $context['prep_time']; // e.g. PT15M
        }

        if (!empty($context['cook_time'])) {
            $data['cookTime'] = $context['cook_time']; // e.g. PT45M
        }

        if (!empty($context['total_time'])) {
            $data['totalTime'] = $context['total_time']; // e.g. PT1H
        }

        if (!empty($context['recipe_yield'])) {
            $data['recipeYield'] = $context['recipe_yield']; // e.g. "4 servings"
        }

        if (!empty($context['recipe_category'])) {
            $data['recipeCategory'] = $context['recipe_category']; // e.g. "Dessert"
        }

        if (!empty($context['recipe_cuisine'])) {
            $data['recipeCuisine'] = $context['recipe_cuisine']; // e.g. "Italian"
        }

        if (!empty($context['nutrition']) && is_array($context['nutrition'])) {
            $data['nutrition'] = array_merge(['@type' => 'NutritionInformation'], $context['nutrition']);
        }

        if (!empty($context['rating_value']) && !empty($context['review_count'])) {
            $data['aggregateRating'] = [
                '@type'       => 'AggregateRating',
                'ratingValue' => (float) $context['rating_value'],
                'reviewCount' => (int) $context['review_count'],
            ];
        }

        return $data;
    }
}
