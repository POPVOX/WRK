<?php

use App\Services\DocumentSafety;

it('normalizes relative paths and strips traversal', function () {
    expect(DocumentSafety::normalizeRelativePath('../etc/passwd'))->toBe('etc/passwd');
    expect(DocumentSafety::normalizeRelativePath('./foo/./bar//baz'))->toBe('foo/bar/baz');
    expect(DocumentSafety::normalizeRelativePath('..\\..\\windows\\system32'))->toBe('windows/system32');
});

it('detects allowed extensions', function () {
    expect(DocumentSafety::isAllowedExtension('md'))->toBeTrue();
    expect(DocumentSafety::isAllowedExtension('TXT'))->toBeTrue();
    expect(DocumentSafety::isAllowedExtension('exe'))->toBeFalse();
});

it('hashes content consistently', function () {
    $a = DocumentSafety::hashContent('hello');
    $b = DocumentSafety::hashContent('hello');
    $c = DocumentSafety::hashContent('world');

    expect($a)->toBeString()->and($a)->toEqual($b)->and($a)->not()->toEqual($c);
});
