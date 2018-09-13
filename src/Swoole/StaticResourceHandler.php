<?php

declare(strict_types=1);

namespace Ypf\Swoole;

use Swoole\Http\Request as  SwooleHttpRequest;
use  Swoole\Http\Response as SwooleHttpResponse;
use function file_exists;
use function pathinfo;
use const PATHINFO_EXTENSION;

class StaticResourceHandler
{
    public const TYPE_MAP_DEFAULT = [
        '7z' => 'application/x-7z-compressed',
        'aac' => 'audio/aac',
        'arc' => 'application/octet-stream',
        'avi' => 'video/x-msvideo',
        'azw' => 'application/vnd.amazon.ebook',
        'bin' => 'application/octet-stream',
        'bmp' => 'image/bmp',
        'bz' => 'application/x-bzip',
        'bz2' => 'application/x-bzip2',
        'css' => 'text/css',
        'csv' => 'text/csv',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'eot' => 'application/vnd.ms-fontobject',
        'epub' => 'application/epub+zip',
        'es' => 'application/ecmascript',
        'gif' => 'image/gif',
        'htm' => 'text/html',
        'html' => 'text/html',
        'ico' => 'image/x-icon',
        'jpg' => 'image/jpg',
        'jpeg' => 'image/jpg',
        'js' => 'text/javascript',
        'json' => 'application/json',
        'mp4' => 'video/mp4',
        'mpeg' => 'video/mpeg',
        'odp' => 'application/vnd.oasis.opendocument.presentation',
        'ods' => 'application/vnd.oasis.opendocument.spreadsheet',
        'odt' => 'application/vnd.oasis.opendocument.text',
        'oga' => 'audio/ogg',
        'ogv' => 'video/ogg',
        'ogx' => 'application/ogg',
        'otf' => 'font/otf',
        'pdf' => 'application/pdf',
        'png' => 'image/png',
        'ppt' => 'application/vnd.ms-powerpoint',
        'pptx' => 'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'rar' => 'application/x-rar-compressed',
        'rtf' => 'application/rtf',
        'svg' => 'image/svg+xml',
        'swf' => 'application/x-shockwave-flash',
        'tar' => 'application/x-tar',
        'tif' => 'image/tiff',
        'tiff' => 'image/tiff',
        'ts' => 'application/typescript',
        'ttf' => 'font/ttf',
        'txt' => 'text/plain',
        'wav' => 'audio/wav',
        'weba' => 'audio/webm',
        'webm' => 'video/webm',
        'webp' => 'image/webp',
        'woff' => 'font/woff',
        'woff2' => 'font/woff2',
        'xhtml' => 'application/xhtml+xml',
        'xls' => 'application/vnd.ms-excel',
        'xlsx' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'xml' => 'application/xml',
        'xul' => 'application/vnd.mozilla.xul+xml',
        'zip' => 'application/zip',
    ];

    private $docRoot;

    public function __construct(array $config)
    {
        $docRoot = $config['document-root'];
        if (!is_dir($docRoot)) {
            throw new \InvalidArgumentException(sprintf(
                'The document root "%s" does not exist; please check your configuration.',
                $docRoot
            ));
        }
        $this->docRoot = $docRoot;
    }

    public function handle(SwooleHttpRequest $request, SwooleHttpResponse $response)
    {
        $fileName = $this->docRoot.$request->server['request_uri'];

        $type = pathinfo($fileName, PATHINFO_EXTENSION);
        if (!isset(static::TYPE_MAP_DEFAULT[$type])) {
            return false;
        }
        if (!file_exists($fileName)) {
            return false;
        }

        $response->status(200);
        $response->header('Content-Type', static::TYPE_MAP_DEFAULT[$type]);
        $response->sendfile($fileName);

        return true;
    }
}
