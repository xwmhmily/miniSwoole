<?php

interface Middleware {

    public static function handle(swoole_http_request $request, Closure $next);

}