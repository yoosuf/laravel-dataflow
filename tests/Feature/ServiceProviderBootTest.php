<?php

declare(strict_types=1);

it('loads default dataflow configuration', function (): void {
    expect(config('dataflow.streaming.enabled'))->toBeTrue();
    expect(config('dataflow.features.exports'))->toBeFalse();
});
