<?php
// Core/helpers.php

if (!function_exists('sanitize')) {
    function sanitize($input) {
        return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('onlyNumbers')) {
    function onlyNumbers($input) {
        return preg_replace('/\D/', '', $input);
    }
}