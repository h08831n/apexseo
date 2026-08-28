<?php
namespace ApexSEO\SEO\Permalinks;

class CategoryBaseStripper {
    public function removeCategoryBase(string $link): string {
        return str_replace('/category/', '/', $link);
    }
}
