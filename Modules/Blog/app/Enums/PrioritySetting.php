<?php

namespace Modules\Blog\app\Enums;

enum PrioritySetting
{
    const CATEGORY_LIST = 'blog_category_list_priority';

    const LIST = 'blog_list_priority';

    /** @var array<int, string> */
    const ALL = [self::CATEGORY_LIST, self::LIST];
}
