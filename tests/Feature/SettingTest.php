<?php

use App\Models\Setting;

it('normalizes legacy stored image paths for cleanup', function () {
    expect(Setting::normalizeStoragePath('media/site/old.jpg'))->toBe('site/old.jpg')
        ->and(Setting::normalizeStoragePath('storage/app/public/site/old.jpg'))->toBe('site/old.jpg')
        ->and(Setting::normalizeStoragePath('storage/app/site/old.jpg'))->toBe('site/old.jpg')
        ->and(Setting::normalizeStoragePath('site/old.jpg'))->toBe('site/old.jpg');
});
