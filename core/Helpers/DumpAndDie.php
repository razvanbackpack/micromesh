<?php

namespace Core\Helpers;

function dd(...$output_object)
{
    dump($output_object);
    die();
}

function d(...$output_object)
{
    dump($output_object);
}