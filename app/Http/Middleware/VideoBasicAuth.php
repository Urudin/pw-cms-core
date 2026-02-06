<?php

namespace App\Http\Middleware;

use App\Models\Video;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class VideoBasicAuth
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Video|null $video */
        $video = $request->route('video');

        // ha {id}-vel hívod és nem model binding, akkor:
        if (! $video instanceof Video) {
            $videoId = $request->route('id') ?? $request->route('video');
            $video = Video::query()->find($videoId);
        }

        if (! $video) {
            abort(404);
        }

        $user = (string) $request->getUser();
        $pass = (string) $request->getPassword();

        $expectedUser = (string) ($video->username ?? '');
        $expectedPass = (string) ($video->password ?? '');

        $okUser = $expectedUser !== '' && hash_equals($expectedUser, $user);
        $okPass = $expectedPass !== '' && hash_equals($expectedPass, $pass);

        if (! ($okUser && $okPass)) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Video Access", charset="UTF-8"',
            ]);
        }

        return $next($request);
    }
}
