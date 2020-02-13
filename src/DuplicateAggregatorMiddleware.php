<?php

namespace ViaWork\LeverPhp;

use Psr\Http\Message\RequestInterface;

class DuplicateAggregatorMiddleware
{
    public static function buildQuery()
    {
        return function (callable $handler) {
            return function (RequestInterface $request, array $options) use ($handler) {
                $query = $request->getUri()->getQuery();
                $request = $request->withUri(
                    $request->getUri()->withQuery(preg_replace('/%5B[0-9]%5D/', '', $query)),
                    true
                );

                return $handler($request, $options);
            };
        };
    }
}
