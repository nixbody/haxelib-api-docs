<?php

class ClassData
{
	private $path;
	private $package;
	private $source;
	private $vars;
	private $props;
	private $methods;
	private $constructor;

	function __construct(string $path, string $package, string $source)
	{
		$this->path = $path;
		$this->package = $package;
		$this->source = $source;
		$fields = ClassField::findAll($source);
		$this->vars = array_filter($fields, ClassField::is('var'));
		$this->props = array_filter($fields, ClassField::is('prop'));
		$this->methods = array_filter($fields, ClassField::is('method'));
		$firstMethod = current($this->methods);
		if ($firstMethod && ClassField::is('constructor')($firstMethod)) {
			$this->constructor = array_shift($this->methods);
		}
	}

	function name(): string
	{
		preg_match('/class\\s+(.+?)(?:\\s+|$)/', $this->decl(), $matches);
		return trim($matches[1] ?? '');
	}

	function html(): string
	{
		ob_start();
	?>
		<h1><?=preg_replace('/(class|extends|implements)/', '<small>\\1</small>', esc($this->decl()))?></h1>
		<p class="package">Package: <strong><?=esc($this->package ?: 'none')?></strong></p>
		<p class="path">Path: <i><?=esc($this->path)?></i></p>
		<?php if (!empty($this->constructor)) : ?>
			<h2>Constructor</h2><?=$this->constructor->html()?>
		<?php endif; if (!empty($this->vars)) : ?>
			<h2>Variables</h2><?php foreach ($this->vars as $var) echo $var->html(); ?>
		<?php endif; if (!empty($this->props)) : ?>
			<h2>Properties</h2><?php foreach ($this->props as $prop) echo $prop->html(); ?>
		<?php endif; if (!empty($this->methods)) : ?>
			<h2>Methods</h2><?php foreach ($this->methods as $method) echo $method->html(); ?>
		<?php endif; ?>
	<?php
		return trim(ob_get_clean());
	}

	private function decl(): string
	{
		preg_match('/(class\\s+.*)\\s*[{#]/', $this->source, $matches);
		return trim($matches[1] ?? '');
	}
}
