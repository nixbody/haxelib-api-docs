<?php

class ClassField
{
	private static $nextId = 0;

	private $id;
	private $decl;
	private $src;
	private $isVar;
	private $isProp;
	private $isMethod;
	private $isPublic;
	private $isStatic;
	private $isOverriding;
	private $isInline;
	private $isConstructor;

	static function is(string $what): Closure
	{
		$property = 'is' . ucfirst($what);
		return function (ClassField $field) use ($property): bool { return $field->{$property}; };
	}

	static function cmp(): Closure
	{
		return function (ClassField $a, ClassField $b): int {
			return
				(10000 * ($b->isConstructor <=> $a->isConstructor)) +
				(1000 * ($b->isPublic <=> $a->isPublic)) +
				(100 * ($b->isStatic <=> $a->isStatic)) +
				(10 * ($b->isOverriding <=> $a->isOverriding)) +
				(1 * ($a->decl <=> $b->decl));
		};
	}

	static function findAll(string $classSource): array
	{
		preg_match_all('/var\\s+[^;]+;/', self::stripMethods($classSource, $methods), $matches);
		$all = array_merge(array_map(factory(static::class), $matches[0]), $methods);
		usort($all, static::cmp());
		return $all;
	}

	private static function stripMethods(string $classSource, array &$methods = null): string
	{
		$src = $classSource;
		if ($methods === null) $methods = [];
		static $regex = '/(?:(?:public|private|static|override|inline)\\s+)*function\\s+[^(]+\\(.*?\\)[^{]*\\{/s';
		preg_match_all($regex, $src, $matches);
		foreach ($matches[0] ?? [] as $method) {
			if (($methodBgn = $pos = strpos($src, $method)) !== false) {
				for ($tries = 0; $tries < 10; ++$tries) {
					$bgn = $pos += strlen($method);
					$level = 0;
					for (; isset($src{$pos}) && ($src{$pos} !== '}' || $level > 0); ++$pos) {
						if ($src{$pos} === '{') ++$level;
						elseif ($src{$pos} === '}') --$level;
					}
					$methodSrc = substr($src, $bgn, $pos - $bgn);
					if (
						!preg_match('/(?:^|\W+)return[\\s(;]+/', $methodSrc)
						&& !preg_match('/\\)\\s*:\\s*Void/', $method)
						&& preg_match('/\\)\\s*:/', $method)
					) {
						$pos = strpos($src, '{', $pos) + 1;
						$method = substr($src, $methodBgn, $pos - $methodBgn - 1);
						$pos -= strlen($method);
						continue;
					}
					$methods[] = new static($method, $methodSrc);
					$src = substr($src, 0, $bgn - strlen($method)) . substr($src, $pos + 1);
					break;
				}
			}
		}
		return preg_replace(['/\\s*;\\s*/', '/[\n\r]+\\t+/'], [";\n", "\n"], $src);
	}

	private static function formatDecl(string $decl): string
	{
		return trim(preg_replace(
			[
				'/[\\r\\n\\s]+/',
				'/\\s*[{;]\\s*$/',
				'/\\s*([():?<>{}\\[\\]])\\s*/',
				'/\\s*([=+*\\-\\/\\%])\\s*/',
				'/\\s*-\\s*>\\s*/',
				'/\\s*,\\s*/'
			],
			[' ', '', '\\1', ' \\1 ', ' -> ', ', '],
			$decl
		));
	}

	function __construct(string $decl, string $src = '')
	{
		$this->id = self::$nextId++;
		$this->decl = self::formatDecl($decl);
		$this->src = $src;
		$this->isVar = preg_match('/var\\s+((.*=.*)|[^()])+$/', $this->decl);
		$this->isProp = preg_match('/var\\s+[^=]*\\(.*\\).*$/', $this->decl);
		$this->isMethod = preg_match('/function\\s+/', $this->decl);
		$this->isPublic = preg_match('/public\\s+/', $this->decl);
		$this->isStatic = preg_match('/static\\s+/', $this->decl);
		$this->isOverriding = preg_match('/override\\s+/', $this->decl);
		$this->isInline = preg_match('/inline\\s+/', $this->decl);
		$this->isConstructor = $this->isMethod && preg_match('/\\s*new\\s*\\(/', $this->decl);
	}

	function html(): string
	{
		ob_start();
	?>
		<p class="field">
			<span class="tag <?=$this->isPublic ? 'public' : 'private'?>"><?=$this->isPublic ? 'public' : 'private'?></span>
			<?php if ($this->isStatic) : ?><span class="tag static">static</span><?php endif; ?>
			<?php if ($this->isOverriding) : ?><span class="tag override">override</span><?php endif; ?>
			<?php if ($this->isInline) : ?><span class="tag inline">inline</span><?php endif; ?>
			<?php if ($this->isVar) : ?><span class="var">var</span><?php endif; ?>
			<?php if ($this->isMethod) : ?><span class="method">function</span><?php endif; ?>
			<span class="sig"<?php if ($this->src) : ?> onclick="toggle('field_src_<?=$this->id?>')"<?php endif; ?>>
			<?=preg_replace(
				['/([:(=].*)/', '/\\s*:\\s*(\w+)/', '/(public|private|static|override|inline|var|function)\\s+/'],
				['<span class="rest">\\1</span>', ':<span class="type">\\1</span>', ''],
				esc($this->decl)
			)?>
			</span>
		</p>
		<?php if ($this->src) : ?>
			<pre id="field_src_<?=$this->id?>"><code class="language-haxe"><?=esc($this->src)?></code></pre>
		<?php endif; ?>
	<?php
		return trim(ob_get_clean());
	}
}
