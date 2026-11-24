<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMText;
use Filament\Forms\Components\RichEditor\RichContentRenderer;

/**
 * Panelde yazılan zengin metni e-posta istemcilerinin güvenle gösterebileceği
 * satır içi stilli, sınırlı HTML'e ve düz metne çevirir.
 */
final class MailRichText
{
    private const TEXT_STYLE = "font-family:'Segoe UI',Arial,Helvetica,sans-serif;font-size:15px;line-height:1.75;color:#3f3b36;";

    /**
     * @var array<string, string>
     */
    private const BLOCK_STYLES = [
        'p' => 'margin:0 0 14px;',
        'ul' => 'margin:0 0 14px;padding:0 0 0 22px;',
        'ol' => 'margin:0 0 14px;padding:0 0 0 22px;',
        'li' => 'margin:0 0 6px;',
        'h2' => "margin:4px 0 10px;font-family:Georgia,'Times New Roman',serif;font-size:19px;line-height:1.35;font-weight:600;color:#161513;",
        'h3' => "margin:4px 0 8px;font-family:Georgia,'Times New Roman',serif;font-size:17px;line-height:1.35;font-weight:600;color:#161513;",
        'blockquote' => 'margin:0 0 14px;padding:4px 0 4px 14px;border-left:3px solid #c9b996;color:#5c554e;font-style:italic;',
    ];

    /**
     * @var list<string>
     */
    private const INLINE_TAGS = ['strong', 'b', 'em', 'i', 'u', 'br', 'a'];

    /**
     * Editör durumu (HTML veya TipTap belgesi) için e-postaya uygun HTML üretir.
     *
     * @param  string|array<string, mixed>|null  $content
     */
    public static function toEmailHtml(string|array|null $content): string
    {
        $html = self::toRawHtml($content);

        if (self::isBlank($html)) {
            return '';
        }

        $document = new DOMDocument('1.0', 'UTF-8');
        libxml_use_internal_errors(true);
        $document->loadHTML('<?xml encoding="UTF-8"><div id="root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        $root = $document->getElementById('root');

        if (! $root instanceof DOMElement) {
            return '';
        }

        return trim(self::renderChildren($root));
    }

    /**
     * @param  string|array<string, mixed>|null  $content
     */
    public static function toPlainText(string|array|null $content): string
    {
        $html = self::toRawHtml($content);
        $html = preg_replace('/<\s*br\s*\/?>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<\/(p|li|h2|h3|blockquote)>/i', "\n", $html) ?? $html;
        $html = preg_replace('/<li[^>]*>/i', '• ', $html) ?? $html;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $text = preg_replace("/[ \t]+\n/", "\n", $text) ?? $text;

        return trim(preg_replace("/\n{3,}/", "\n\n", $text) ?? $text);
    }

    /**
     * @param  string|array<string, mixed>|null  $content
     */
    public static function isBlank(string|array|null $content): bool
    {
        $html = is_array($content) ? self::toRawHtml($content) : (string) $content;
        $text = html_entity_decode(strip_tags($html), ENT_QUOTES | ENT_HTML5, 'UTF-8');

        return trim(str_replace("\u{00A0}", ' ', $text)) === '';
    }

    public static function textStyle(): string
    {
        return self::TEXT_STYLE;
    }

    /**
     * @param  string|array<string, mixed>|null  $content
     */
    private static function toRawHtml(string|array|null $content): string
    {
        if (is_array($content)) {
            return RichContentRenderer::make($content)->toHtml();
        }

        return (string) $content;
    }

    private static function renderChildren(DOMNode $node): string
    {
        $html = '';

        foreach ($node->childNodes as $child) {
            $html .= self::renderNode($child);
        }

        return $html;
    }

    private static function renderNode(DOMNode $node): string
    {
        if ($node instanceof DOMText) {
            return e($node->wholeText, false);
        }

        if (! $node instanceof DOMElement) {
            return '';
        }

        $tag = strtolower($node->tagName);
        $inner = self::renderChildren($node);

        if ($tag === 'h1') {
            $tag = 'h2';
        }

        if (in_array($tag, ['h4', 'h5', 'h6'], true)) {
            $tag = 'h3';
        }

        if (array_key_exists($tag, self::BLOCK_STYLES)) {
            if ($tag === 'p' && trim(strip_tags($inner)) === '') {
                return '';
            }

            return '<'.$tag.' style="'.self::BLOCK_STYLES[$tag].'">'.$inner.'</'.$tag.'>';
        }

        if (! in_array($tag, self::INLINE_TAGS, true)) {
            return $inner;
        }

        return match ($tag) {
            'br' => '<br>',
            'strong', 'b' => '<strong style="color:#161513;">'.$inner.'</strong>',
            'em', 'i' => '<em>'.$inner.'</em>',
            'u' => '<u>'.$inner.'</u>',
            'a' => self::renderLink($node, $inner),
        };
    }

    private static function renderLink(DOMElement $node, string $inner): string
    {
        $href = trim($node->getAttribute('href'));

        if (preg_match('/^(https?:\/\/|mailto:|tel:)/i', $href) !== 1) {
            return $inner;
        }

        return '<a href="'.e($href).'" target="_blank" rel="noopener noreferrer" style="color:#7a6a50;text-decoration:underline;">'.$inner.'</a>';
    }
}
