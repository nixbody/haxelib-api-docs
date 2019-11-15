<?php

class ApiDoc
{
	private $packages = [];

	function __construct()
	{
		foreach (SourceFile::findAll() as $file) {
			$lib = $file->library();
			$packageName = $file->package();
			$this->packages[$lib] = $this->packages[$lib] ?? [];
			$this->packages[$lib][$packageName] = $this->packages[$lib][$packageName] ?? [];
			foreach ($file->classes() as $class) {
				$this->packages[$lib][$packageName][$class->name()] = $class;
			}
			ksort($this->packages[$lib][$packageName]);
			ksort($this->packages[$lib]);
		}
		ksort($this->packages);
	}

	function html(): string
	{
		static $cdn = 'https://cdnjs.cloudflare.com/ajax/libs/prism/1.17.1';
		static $typeCol = 'blue';
		$showLib = $_GET['lib'] ?? '';
		$showPkg = $_GET['pkg'] ?? '';
		$showClass = $_GET['class'] ?? '';
		ob_start();
	?>
		<!doctype html>
		<html>
			<head>
				<meta charset="utf-8">
				<title></title>
				<style><?=file_get_contents("$cdn/themes/prism-coy.min.css")?></style>
				<style>
					* { box-sizing: border-box; }
					html, body { height: 100%; margin:0; padding: 0; }
					body { font-family: Consolas, Monaco, 'DejaVu Sans Mono', monospace; font-size: 15px; }
					h1 { font-size: 175%; }
					h2 { font-size: 140%; padding: 20px 0 5px 0; border-bottom: 1px solid lightgray; }
					h1 small { font-style: italic; color: <?=esc($typeCol)?>; }
					a { color: darkviolet; text-decoration: none; }
					a:hover { text-decoration: underline; }
					.packages form { text-align: center; padding: 10px 0; border-bottom: 1px solid lightgray; }
					.packages form select { width: 150px; }
					.packages, .class-data { float: left; overflow: auto; height: 100%; }
					.packages { width: 300px; padding: 0; background: whitesmoke; }
					.packages ul { padding: 0 0 0 20px; font-size: 14px; }
					.packages li:hover .pkg-name { cursor: pointer; font-weight: bold; }
					.class-data { width: calc(100% - 300px); padding: 0 20px; }
					.path i { color: gray; }
					.sig { font-weight: bold; }
					.sig .tag, .sig .rest { font-weight: normal; }
					.sig[onclick]:hover { cursor: pointer; border-bottom: 1px solid black; }
					.tag { display: inline-block; padding: 2px 3px 2px 2px; border-radius: 3px; font-size: 14px; }
					.var, .method { font-style: italic; color: gray; }
					.public { background: green; color: white; }
					.private { background: gold; color: black; }
					.static { background: dodgerblue; color: white; }
					.override { background: slateblue; color: white; }
					.inline { background: lightslategray; color: white; }
					.type { color: <?=esc($typeCol)?>; }
				</style>
				<script>
					function toggle(id) {
						const el = document.getElementById(id);
						if (el) el.style.display = el.style.display ? '' : 'none';
						return false;
					}
				</script>
			</head>
			<body>
				<div class="packages">
					<form action="" method="get">
						<select name="lib" onchange="this.form.submit()">
						<?php foreach (array_keys($this->packages) as $lib) : ?>
							<option value="<?=esc($lib)?>"<?=$lib === $showLib ? 'selected' : ''?>>
								<?=esc($lib)?>
							</option>
						<?php endforeach; ?>
						</select>
						<input type="submit" value="Show">
					</form>
					<ul>
					<?php foreach ($this->packages[$showLib] ?? [] as $packageName => $package) if ($packageName) : ?>
						<li>
							<span class="pkg-name" onclick="toggle('pkg_classes_<?=!isset($id) ? $id = 0 : $id?>')">
								<?=($packageName === $showPkg
									? '<strong>' . esc($packageName) . '</strong>'
									: esc($packageName)
								)?>
							</span>
							<ul id="pkg_classes_<?=$id++?>" class="<?=$packageName !== $showPkg ? 'hidden' : ''?>">
							<?php foreach ($package as $className => $class) : ?>
								<li>
									<a href="?<?=http_build_query([
										'lib' => $showLib, 'pkg' => $packageName, 'class' => $className
									])?>">
										<?=($className === $showClass
											? '<strong>' . esc($className) . '</strong>'
											: esc($className)
										)?>
									</a>
								</li>
							<?php endforeach; ?>
							</ul>
						</li>
					<?php endif; foreach ($this->packages[''] ?? [] as $className => $class) : ?>
						<li><a href="?class=<?=esc($className)?>"><?=esc($className)?></a></li>
					<?php endforeach; ?>
					</ul>
				</div>
				<div class="class-data">
				<?php if (isset($this->packages[$showLib][$showPkg][$showClass])) : ?>
					<?=$this->packages[$showLib][$showPkg][$showClass]->html()?>
				<?php endif; ?>
				</div>
				<script><?=file_get_contents("$cdn/components/prism-core.min.js")?></script>
				<script><?=file_get_contents("$cdn/components/prism-clike.min.js")?></script>
				<script><?=file_get_contents("$cdn/components/prism-haxe.min.js")?></script>
				<script><?=file_get_contents("$cdn/plugins/normalize-whitespace/prism-normalize-whitespace.min.js")?></script>
				<script>
					Prism.plugins.NormalizeWhitespace.setDefaults({
						'remove-trailing': true,
						'remove-indent': true,
						'left-trim': true,
						'right-trim': true,
						'tabs-to-spaces': 4
					});
					Array.from(document.body.getElementsByClassName('hidden')).map(e => e.style.display = 'none');
					Array.from(document.body.getElementsByTagName('pre')).map(e => e.style.display = 'none');
				</script>
			</body>
		</html>
	<?php
		return trim(ob_get_clean());
	}
}
