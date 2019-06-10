<?php

interface Middleware {

    public static function handle(Closure $next);

}