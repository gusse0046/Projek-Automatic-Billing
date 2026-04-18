<?php

if (!function_exists('str_slug')) {
    function str_slug($title, $separator = '-', $language = 'en')
    {
        return \Illuminate\Support\Str::slug($title, $separator, $language);
    }
}