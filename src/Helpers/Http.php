<?php

declare(strict_types=1);

namespace AAD\Telgraf\Helpers;

use AAD\Telgraf\Services;
use Swoole\Http\Request;
use Swoole\Http\Response;
use Respect\Validation\Validator as v;

/**
 * Class Http
 * @package AAD\Telgraf\Helpers
 */
class Http
{
    /**
     * @param Request $request
     * @return array
     */
    public static function getParsedBody(Request $request): array
    {
        $body = $request->post;
        if (!v::arrayType()->notEmpty()->validate($body)) {
            $body = json_decode($request->rawContent(), true) ?? [];
        }
        return $body;
    }

    /**
     * @param Response $response
     * @param array $data
     * @param int $status
     * @param string $contentType
     * @return mixed
     */
    public static function response(Response $response, array $data = [], int $status = 200, string $contentType = 'application/json')
    {
        $response->header('Content-Type', $contentType);
        $response->status($status);
        if ($contentType === 'application/json') {
            $data = json_encode($data);
        }
        return $response->end($data);
    }

    /**
     * @param Request $request
     * @param Response $response
     * @return mixed
     */
    public static function requestResolver(Request $request, Response $response)
    {
        if ($request->server['request_method'] != 'POST' || !v::attribute('server', v::key('path_info', v::startsWith('/app-')->length(7)))->validate($request)) {
            $data = [
                'status' => 'error',
                'message' => 'Unknown command',
            ];
            return self::response($response, $data, 404);
        }

        if (Services::authorization()->check($request)) {
            $body = self::getParsedBody($request);
            $data = Services::telegram()->messageResolver($body);
            return self::response($response, $data);
        }

        $data = [
            'status' => 'error',
            'message' => 'Authorization failed',
        ];
        return self::response($response, $data, 404);
    }
}
