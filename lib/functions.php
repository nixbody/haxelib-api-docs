<?php

function haxeLibDir(): string
{
	return realpath(getenv('HAXELIB_PATH') ?: (getenv('HAXEPATH') . DIRECTORY_SEPARATOR . 'lib'));
}

function esc(string $input): string
{
	return htmlspecialchars($input, ENT_QUOTES, 'UTF-8', false);
}

function factory(string $class): Closure
{
	return function (...$args) use ($class): object {
		return new $class(...$args);
	};
}

function curry(callable $callable, ...$boundArgs): Closure
{
	return function (...$args) use ($callable, $boundArgs) {
		return call_user_func($callable, ...$boundArgs, ...$args);
	};
}
