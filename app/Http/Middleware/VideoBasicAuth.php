<?php

namespace App\Http\Middleware;

use App\Models\Video;
use App\Models\VideoAccess;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
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

        if (! $video instanceof Video) {
            $videoId = $request->route('id') ?? $request->route('video');
            $video = Video::query()->find($videoId);
        }

        if (! $video) {
            abort(404);
        }

        $user = (string) $request->getUser();
        $pass = (string) $request->getPassword();
        $username = request()->input('u');


        $access = VideoAccess::query()->where('email', $user)->where('password', $pass)->where('video_id', $video->id)->first();

        if (empty($access)) {
            return response('Unauthorized', 401, [
                'WWW-Authenticate' => 'Basic realm="Video Access", charset="UTF-8"',
            ]);
        }
        if($username !== $access->username){
            abort(403, 'You are not authorized to access with this link');
        }

        if(empty($access->first_used_at))
        {
            $access->first_used_at = now();
        }

        $access->usage_count++;
        $access->save();

        return $next($request);
    }
}
