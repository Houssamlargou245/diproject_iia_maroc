<?php

namespace App\Support;

use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;

/**
 * Inline QR codes, rendered as SVG.
 *
 * The SVG backend is used rather than the PNG one so nothing depends on the GD
 * extension and the code stays crisp at any zoom or pixel density — a delegate
 * scanning it from a phone held at arm's length is the whole point.
 */
final class QrCode
{
    /**
     * The SVG markup for a QR code encoding the given content.
     */
    public static function svg(string $content, int $size = 240, int $margin = 1): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, $margin),
            new SvgImageBackEnd
        );

        return (new Writer($renderer))->writeString($content);
    }
}
