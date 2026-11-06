<?php

namespace App\Http\Middleware;

use App\Support\Typography;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Applique la typographie française (espaces insécables) à toutes les pages HTML. */
class FrenchTypography
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        if (str_starts_with((string) $response->headers->get('Content-Type'), 'text/html')
            && ! $response instanceof \Symfony\Component\HttpFoundation\StreamedResponse
            && ! $response instanceof \Symfony\Component\HttpFoundation\BinaryFileResponse
            && is_string($content = $response->getContent()) && $content !== '') {
            $response->setContent(Typography::html($content));
        }

        return $response;
    }
}
