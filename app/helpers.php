<?php

/**
 * Shortcut for obtaining the app name
 *
 * @return mixed
 */
function appName()
{
    return config('app.name');
}

/**
 * Get the application domain or one of the sub-domains.
 * Root domain must be set from the env file
 *
 * @param null|string $prefix
 * @return null|string
 */
function appDomain($prefix = null)
{
    if (is_null($prefix)) {
        return config('app.domain');
    }

    return $prefix . '.' . config('app.domain');
}

/**
 * Get the subd-domain component
 * Inverse of appDomain()
 *
 * @return string|null
 */
function domainPrefix()
{
    $host = Request::getHost();

    if ($host === appDomain()) {
        return null;
    }

    $hostWithoutRootDomain = str_replace(appDomain(), '', $host);
    return $hostWithoutRootDomain = rtrim($hostWithoutRootDomain, '.');
}

/**
 * Whether the request url begins with "m."
 *
 * @return bool
 */
function isWechat()
{
    $isMobile = starts_with(Request::getHost(), 'm.');

    return $isMobile;
}

/**
 * Determine the user type from the request. Can be 'students', 'teachers' or 'admins'
 *
 * @return \Illuminate\Routing\Route|object|string
 */
function userType()
{
    $sessionKey = 'user_type';

    if (Session::has($sessionKey)) {
        return Session::get($sessionKey);
    }

    $userType = str_replace('m.', '', domainPrefix());
    Session::put($sessionKey, $userType);

    if ($userType) {
        return $userType;
    }

    return 'students';
}

/**
 * Returns the localized translation of user type
 *
 * @return string|\Symfony\Component\Translation\TranslatorInterface
 */
function userTypeCn()
{
    return trans('user.type.' . userType());
}

/**
 * Returns the currently authenticated user by its type
 *
 * @return \Illuminate\Contracts\Auth\Authenticatable|null
 */
function authUser()
{
    return Auth::guard(userType())->user();
}

/**
 * Returns the id of the currently authenticated user by its type
 *
 * @return int|null
 */
function authId()
{
    return Auth::guard(userType())->id();
}

/**
 * Check if the current user type is logged in
 *
 * @return bool
 */
function authCheck()
{
    return Auth::guard(userType())->check();
}

/**
 * "backend" for teachers and admins
 * "wechat" for student views
 * "frontend" as a fallback for students
 *
 * @return string
 */
function viewPrefix()
{
    $prefix = "";
    $userType = userType();

    if ($userType === 'teachers' || $userType === 'admins') {
        $prefix = 'backend/';
    } elseif ($userType === 'students') {
        $prefix = isWechat() ? 'wechat/' : 'frontend/';
    }

    return $prefix;
}

/**
 * Fall back to a default placeholder if not set
 *
 * @param $url
 * @param $preset
 * @return string
 */
function getAvatar($url, $preset)
{
    if ($url) {
        return $url . '?p=' . $preset;
    }

    return '/default/avatar.png?p=' . $preset;
}

/**
 * @param $route
 * @return bool
 */
function isPageActive($route)
{
    if ($bct = \Page::bct()) {
        $activeRoutes = $bct->pluck('route');
        return $activeRoutes->contains($route);
    }

    return false;
}

/**
 * Convert Carbon timestamps to Chinese human readable format
 * TODO this package is quite limited. Need to write own formatter.
 *
 * @param $timestamp
 * @return string
 */
function humanDateTime($timestamp)
{
    return Date::parse($timestamp)->format('Fj\\号, l, h:i A');
}

function humanTime($timestamp)
{
    return Date::parse($timestamp)->format('h:i A');
}

function humanDate($timestamp)
{
    return Date::parse($timestamp)->format('Fj\\号, l');
}


/**
 * Generate absolute path to route file given the file name
 * All route files are stored in app\Http\Routes
 *
 * @param $file
 * @return string
 */
function routeFile($file)
{
    return app_path('Http' . DIRECTORY_SEPARATOR . 'Routes' . DIRECTORY_SEPARATOR . $file);
}

/**
 * Pads nested arrays to be of equal length resursively
 *
 * @param array|\Illuminate\Support\Collection $array
 * @return array
 */
function padArray($array)
{
    $length = 0;

    /* first we get the max length */
    foreach ($array as $item) {
        if (! is_array($item) AND ! $item instanceof \Illuminate\Contracts\Support\Arrayable) {
            continue;
        } elseif (count($array) > $length) {
            $length = count($array);
        }
    }

    if ($length === 0) {
        return $array;
    }

    /* now fill it up */
    foreach ($array as $item) {
        if (is_array($item) OR $item instanceof \Illuminate\Contracts\Support\Arrayable) {
            fillArray($item, $length);
        }
    }

    return $array;
}

/**
 * @param array|\Illuminate\Support\Collection $array
 * @param int $length
 * @return array
 */
function fillArray($array, int $length)
{
    if (count($array) < $length){
        for ($i = 0; $i < $length - count($array); $i++) {
            $array[] = false;
        }
    }

    return $array;
}