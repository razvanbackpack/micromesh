<?php

namespace Core\Container\Exceptions;

/**
 * Thrown when a circular dependency is detected during resolution.
 */
class CircularDependencyException extends ContainerException
{
}
