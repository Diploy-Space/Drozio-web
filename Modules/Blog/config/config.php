<?php

use Modules\Blog\app\Services\New\BlogService;

return [
    'name' => 'Blog',

    'thumbnail' => getModuleDynamicAsset(path: 'public/Modules/Blog/module-assets/thumbnail.png'),

    'advanced_search' => [
        'common_sources' => [
            'blogs' => [
                'service' => BlogService::class,
                'label' => 'Blogs',
                'permission' => 'settings.content',
                'title' => ['title'],
                'image' => 'thumbnail_full_url',
                'image_type' => 'backend-basic',
                'route' => 'admin.settings.content.blog.edit',
                'route_parameters' => ['id' => 'id'],
                'filters' => [],
                'data_limit' => 5,
            ],
        ],
    ],
];
