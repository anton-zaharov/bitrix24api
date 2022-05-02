<?php
namespace Bitrix24api;

use Closure;

class HookCheckMemberIdMiddleware
{
    public function handle($request, Closure $next)
    {
        $path = app()->basePath('app/Bitrix24/settings.json');
        if (file_exists($path)) {
            $sets = file_get_contents($path);
            $data = json_decode($sets, true);
            $auth = $request->get('auth');
            if ($data['member_id']??null === $auth['member_id']??'wtf'){
                return $next($request);
            } 
        }
        abort(403);
    }
}
