<?php

return [

    /**
     * The display name of the module.
     */
    'name' => 'AI',

    /**
     * The path to the module's thumbnail image.
     *
     * Uses the `getModuleDynamicAsset` helper to generate
     * the correct public URL for the asset.
     */
    'thumbnail' => getModuleDynamicAsset(path: 'public/Modules/AI/module-assets/thumbnail.png'),

    /**
     * Stand-in product image used by the shopping assistant in development, where
     * no real upload is available to send to the vision model.
     */
    'dev_sample_image_url' => env('AI_DEV_SAMPLE_IMAGE_URL', 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=600'),
];
