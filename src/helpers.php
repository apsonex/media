<?php


if (!function_exists('media')) {
    /**
     * Get media factory instance
     */
    function media(): \Apsonex\Media\Factory\Media
    {
        return resolve('media');
    }
}