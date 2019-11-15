<?php

class SourceFile
{
	private $path;
	private $contents;

	static function findAll(): Iterator
	{
		$files = new CallbackFilterIterator(
			new RecursiveIteratorIterator(new RecursiveDirectoryIterator(haxeLibDir())),
			function (SplFileInfo $file): bool { return $file->isFile() && $file->getExtension() === 'hx'; }
		);
		foreach ($files as $file)
			yield new static($file);
	}

	function __construct(string $path)
	{
		$this->path = $path;
		$this->contents = file_get_contents($path);
	}

	function library(): string
	{
		$parts = explode(DIRECTORY_SEPARATOR, substr($this->path, strlen(haxeLibDir()) + 1));
		return preg_replace('/,/', '.', trim(implode('@', array_slice($parts, 0, 2))));
	}

	function package(): string
	{
		preg_match('/package\\s*([0-9A-Za-z_.]*)\\s*;/', $this->contents, $matches);
		return trim($matches[1] ?? '');
	}

	function classes(): array
	{
		return array_map(
			curry(factory(ClassData::class), $this->path, $this->package()),
			array_filter(
				array_map(
					function (string $class) { return 'class ' . trim($class); },
					array_slice(
						preg_split('/\\s+class\\s+/', preg_replace(['~//.*~', '~/\\*.*\\*/~s'], '', $this->contents)),
						1
					)
				),
				function (string $class): bool { return preg_match('/class\\s+[^\'"}]*\\{.*\\}/s', $class); }
			)
		);
	}
}
