<?php

use App\Support\QuillHtmlSanitizer;

it('strips script tags and event handlers', function () {
    $html = '<p onclick="alert(1)">Hello</p><script>alert(1)</script>';
    $sanitized = QuillHtmlSanitizer::sanitize($html);

    expect($sanitized)->toContain('<p>Hello</p>')
        ->and($sanitized)->not()->toContain('<script')
        ->and($sanitized)->not()->toContain('onclick');
});

it('removes unsafe href/src protocols', function () {
    $html = '<p><a href="javascript:alert(1)">x</a> <img src="data:image/png;base64,AAAA" /></p>';
    $sanitized = QuillHtmlSanitizer::sanitize($html);

    // Links/images should remain but without unsafe URL attributes.
    expect($sanitized)->toContain('<a')
        ->and($sanitized)->not()->toContain('javascript:')
        ->and($sanitized)->toContain('<img')
        ->and($sanitized)->not()->toContain('data:image');
});
