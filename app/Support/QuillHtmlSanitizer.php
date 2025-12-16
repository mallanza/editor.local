<?php

namespace App\Support;

class QuillHtmlSanitizer
{
    /**
     * Very small allow-list sanitizer for Quill HTML snapshots.
     *
     * Notes:
     * - This is defense-in-depth. The Quill Delta should remain the canonical source.
     * - We strip scripts/styles/handlers and enforce safe URL protocols.
     */
    public static function sanitize(?string $html): ?string
    {
        $html = is_string($html) ? trim($html) : '';
        if ($html === '') {
            return null;
        }

        // Wrap so we can reliably extract innerHTML without DOMDocument adding <html><body>.
        $wrapped = '<div id="__q2_root__">' . $html . '</div>';

        $prevUseErrors = libxml_use_internal_errors(true);
        $doc = new \DOMDocument('1.0', 'UTF-8');
        try {
            // Prefix XML declaration to force UTF-8.
            $doc->loadHTML('<?xml encoding="utf-8" ?>' . $wrapped, \LIBXML_HTML_NOIMPLIED | \LIBXML_HTML_NODEFDTD);
        } catch (\Throwable $e) {
            libxml_clear_errors();
            libxml_use_internal_errors($prevUseErrors);
            return null;
        }
        libxml_clear_errors();
        libxml_use_internal_errors($prevUseErrors);

        $root = $doc->getElementById('__q2_root__');
        if (! $root) {
            return null;
        }

        $allowedTags = array_fill_keys([
            'p', 'br',
            'strong', 'em', 'u', 's', 'span',
            'h1', 'h2', 'h3',
            'blockquote',
            'ul', 'ol', 'li',
            'a', 'img',
            'table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'colgroup', 'col',
        ], true);

        $blocklistedTags = array_fill_keys([
            'meta', 'style', 'script', 'link', 'iframe', 'object', 'embed', 'xml',
        ], true);

        $globalAttrs = array_fill_keys([
            'href', 'src', 'alt', 'title', 'colspan', 'rowspan', 'cellpadding', 'cellspacing', 'width', 'height', 'scope',
        ], true);

        $tagAttrs = [
            'a' => array_fill_keys(['href', 'title', 'target', 'rel'], true),
            'img' => array_fill_keys(['src', 'alt', 'title', 'width', 'height', 'data-q2-img-w', 'data-q2-img-h', 'data-q2-img-align'], true),
            'td' => array_fill_keys(['colspan', 'rowspan', 'width', 'height'], true),
            'th' => array_fill_keys(['colspan', 'rowspan', 'scope', 'width', 'height'], true),
            'table' => array_fill_keys(['border', 'cellpadding', 'cellspacing'], true),
            'col' => array_fill_keys(['span', 'width'], true),
            'colgroup' => array_fill_keys(['span'], true),
        ];

        $safeDataAttr = static fn (string $name): bool => (bool) preg_match('/^data-[a-z0-9_\-:]+$/i', $name);

        $isUnsafeUrl = static function (string $attrName, string $value): bool {
            $raw = trim($value);
            if ($raw === '') {
                return false;
            }
            $lower = strtolower($raw);

            // Disallow protocol-relative URLs.
            if (str_starts_with($lower, '//')) {
                return true;
            }

            foreach (['javascript:', 'vbscript:', 'file:', 'blob:', 'cid:'] as $bad) {
                if (str_starts_with($lower, $bad)) {
                    return true;
                }
            }

            // For stored HTML snapshots, disallow data: URLs (including images).
            if (str_starts_with($lower, 'data:')) {
                return true;
            }

            $parsed = @parse_url($raw);
            $scheme = is_array($parsed) && isset($parsed['scheme']) ? strtolower((string) $parsed['scheme']) : null;
            if (! $scheme) {
                // Relative path or fragment.
                return false;
            }

            if ($attrName === 'href') {
                return ! in_array($scheme, ['http', 'https', 'mailto', 'tel'], true);
            }

            // src
            return ! in_array($scheme, ['http', 'https'], true);
        };

        $unwrap = static function (\DOMNode $node) {
            $doc = $node->ownerDocument;
            if (! $doc || ! $node->parentNode) {
                return;
            }
            $fragment = $doc->createDocumentFragment();
            while ($node->firstChild) {
                $fragment->appendChild($node->firstChild);
            }
            $node->parentNode->replaceChild($fragment, $node);
        };

        $walk = static function (\DOMNode $node) use (&$walk, $allowedTags, $blocklistedTags, $globalAttrs, $tagAttrs, $safeDataAttr, $isUnsafeUrl, $unwrap): void {
            // Iterate using a snapshot array because we may modify children during traversal.
            $children = [];
            for ($child = $node->firstChild; $child; $child = $child->nextSibling) {
                $children[] = $child;
            }

            foreach ($children as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    if (!($child instanceof \DOMElement)) {
                        $walk($child);
                        continue;
                    }
                    $el = $child;
                    $tag = strtolower($child->nodeName);

                    if (isset($blocklistedTags[$tag]) || str_starts_with($tag, 'o:')) {
                        $child->parentNode?->removeChild($child);
                        continue;
                    }

                    if (! isset($allowedTags[$tag])) {
                        // Unknown tag: unwrap to preserve text content.
                        $unwrap($child);
                        continue;
                    }

                    if ($el->hasAttributes()) {
                        /** @var \DOMNamedNodeMap $attrs */
                        $attrs = $el->attributes;
                        $toRemove = [];
                        foreach ($attrs as $attr) {
                            $name = strtolower($attr->nodeName);

                            if (str_starts_with($name, 'data-q2-')) {
                                continue;
                            }
                            if (str_starts_with($name, 'data-')) {
                                if ($safeDataAttr($name)) {
                                    continue;
                                }
                                $toRemove[] = $attr->nodeName;
                                continue;
                            }
                            if (str_starts_with($name, 'aria-')) {
                                continue;
                            }
                            if ($name === 'style' || str_starts_with($name, 'on')) {
                                $toRemove[] = $attr->nodeName;
                                continue;
                            }
                            if ($name === 'class') {
                                // Keep known-safe classes only.
                                // - Table elements: keep tokenized classes (Quill table tooling)
                                // - Change tracking spans: keep the marker class so Quill can recognize the custom blot
                                if ($tag === 'span') {
                                    $tokens = preg_split('/\s+/', (string) $attr->nodeValue, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                                    $tokens = array_values(array_filter($tokens, static fn ($t) => $t === 'q2-change-inline'));
                                    if (count($tokens)) {
                                        $el->setAttribute($attr->nodeName, implode(' ', $tokens));
                                        continue;
                                    }
                                }

                                if (in_array($tag, ['table', 'thead', 'tbody', 'tfoot', 'tr', 'td', 'th', 'colgroup', 'col'], true)) {
                                    // Minimal token sanitization.
                                    $tokens = preg_split('/\s+/', (string) $attr->nodeValue, -1, PREG_SPLIT_NO_EMPTY) ?: [];
                                    $tokens = array_values(array_filter($tokens, static fn ($t) => (bool) preg_match('/^[a-z0-9_\-]+$/i', $t)));
                                    if (count($tokens)) {
                                        $el->setAttribute($attr->nodeName, implode(' ', $tokens));
                                        continue;
                                    }
                                }
                                $toRemove[] = $attr->nodeName;
                                continue;
                            }

                            $tagAllow = $tagAttrs[$tag] ?? null;
                            if (is_array($tagAllow)) {
                                if (isset($tagAllow[$name]) || isset($globalAttrs[$name])) {
                                    // URL protocol enforcement for href/src.
                                    if (($name === 'href' || $name === 'src') && $isUnsafeUrl($name, (string) $attr->nodeValue)) {
                                        $toRemove[] = $attr->nodeName;
                                    }
                                    continue;
                                }
                                $toRemove[] = $attr->nodeName;
                                continue;
                            }

                            if (! isset($globalAttrs[$name])) {
                                $toRemove[] = $attr->nodeName;
                                continue;
                            }

                            if (($name === 'href' || $name === 'src') && $isUnsafeUrl($name, (string) $attr->nodeValue)) {
                                $toRemove[] = $attr->nodeName;
                            }
                        }

                        foreach ($toRemove as $name) {
                            $el->removeAttribute($name);
                        }
                    }

                    $walk($el);
                    continue;
                }

                if ($child->nodeType === XML_COMMENT_NODE) {
                    $child->parentNode?->removeChild($child);
                    continue;
                }

                // Text nodes are fine.
            }
        };

        $walk($root);

        // Extract innerHTML of wrapper.
        $out = '';
        foreach (iterator_to_array($root->childNodes) as $child) {
            $out .= $doc->saveHTML($child);
        }

        $out = trim($out);
        return $out === '' ? null : $out;
    }
}
