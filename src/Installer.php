<?php

namespace luya\composer;

use Composer\Installer\LibraryInstaller;
use Composer\Repository\InstalledRepositoryInterface;
use Composer\Package\PackageInterface;

class Installer extends LibraryInstaller
{
	const LUYA_EXTRA = 'luya';
	
	const LUYA_FILE = 'luyadev/installer.php';
	
	public function supports($packageType)
	{
		return true;
	}
	
	public function install(InstalledRepositoryInterface $repo, PackageInterface $package)
	{
		// install the package the normal composer way
		parent::install($repo, $package);
		
		$this->extractRepoConfig($package);
	}
	
	public function update(InstalledRepositoryInterface $repo, PackageInterface $initial, PackageInterface $target)
	{
		parent::update($repo, $initial, $target);
		
		$this->extractRepoConfig($target);
	}
	
	public function uninstall(InstalledRepositoryInterface $repo, PackageInterface $package)
	{
		parent::uninstall($repo, $package);
		
		$this->extractRepoConfig($target, true);
	}
	
	protected function extractRepoConfig(PackageInterface $package, $remove = false)
	{
		$extra = $package->getExtra();
		
		$config = isset($package->getExtra()[self::LUYA_EXTRA]);
		
		if ($config) {
			if ($remove) {
				$data = $this->removeConfig($package);
			} else {
				$data = $this->addConfig($package);
			}
			$this->writeInstaller($data);
		}
	}
	
	protected function getInstallers()
	{
		$file = $this->vendorDir . DIRECTORY_SEPARATOR . self::LUYA_FILE;
		
		if (!file_exists($file)) {
			return [];
		}
		
		if (function_exists('opcache_invalidate')) {
			@opcache_invalidate($file, true);
		}
		
		$data = require($file);

		return $data;
	}
	
	protected function ensureConfig(PackageInterface $package, array $config)
	{
		$packageConfig = [
			'package' => ['name' => $package->getName(), 'prettyName' => $package->getPrettyName(), 'version' => $package->getVersion()],
			'blocks' => [],
			'bootstrap' => (isset($config['bootstrap'])) ? $config['bootstrap'] : [],
		];
		
		$blocks = (isset($config['blocks'])) ? $config['blocks'] : [];
	
		foreach ($blocks as $blockFolder) {
			$packageConfig['blocks'][] = $this->vendorDir . DIRECTORY_SEPARATOR . $package->getPrettyName() . DIRECTORY_SEPARATOR . ltrim($blockFolder, '/');
		}
		
		return $packageConfig;
	}
		
	protected function removeConfig(PackageInterface $package)
	{
		$data = $this->getInstallers();
		unset($data[$package->getName()]);
		$data['__timestamp'] = time();
		
		return $data;
	}
	
	protected function addConfig(PackageInterface $package)
	{
		$data = $this->getInstallers();
		$data[$package->getName()] = $this->ensureConfig($package, $package->getExtra()[self::LUYA_EXTRA]);
		$data['__timestamp'] = time();
		
		return $data;
	}
	
	
	protected function writeInstaller(array $data)
	{
		$file = $this->vendorDir . DIRECTORY_SEPARATOR . self::LUYA_FILE;
		
		if (!file_exists(dirname($file))) {
			mkdir(dirname($file), 0777, true);
		}
		
		$array = str_replace("'<vendor-dir>", '$vendorDir . \'', var_export($data, true));
		if (file_put_contents($file, "<?php\n\n\$vendorDir = dirname(__DIR__);\n\nreturn $array;\n") === false) {
			$this->io->writeError("Unable to create luya installer file.");
		}
		
		// Invalidate opcache of plugins.php if it exists
		if (function_exists('opcache_invalidate')) {
			@opcache_invalidate($file, true);
		}
	}
}