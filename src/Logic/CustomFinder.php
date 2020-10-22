<?php


namespace App\Logic;


use Symfony\Component\Finder\Finder;

class CustomFinder
{

	/**
	 * Return each filename in $directory
	 * @param $directory
	 * @return array
	 */
	public function getFilesName(string $directory)
	{
		$results = [];

		if (!is_dir($directory))
			return $results;

		$finder = new Finder();
		$finder->ignoreDotFiles(true)->in($directory)->files();

		foreach ($finder as $file) {
			array_push($results, $file->getFilename());
		}
		return $results;
	}

	/**
	 * Return each directory in $directory
	 * @param $directory
	 * @return array
	 */
	public function getDirsName(string $directory)
	{
		$results = [];

		if (!is_dir($directory))
			return $results;

		$finder = new Finder();
		$finder->ignoreDotFiles(true)->in($directory)->directories();

		foreach ($finder as $dir) {
			array_push($results, $dir->getFilename());
		}
		return $results;
	}

	/**
	 * Return the first file name found in $directory
	 * @param $directory
	 * @return mixed|string
	 */
	public function getFirstFile(string $directory)
	{
		$filesname = $this->getFilesName($directory);
		if (count($filesname) >= 1)
			return $filesname[0];
		return "";
	}

	/**
	 * Return the index of $filename in $directory
	 * @param $directory
	 * @param $filename
	 * @return int
	 */
	public function getFileIndex(string $directory, string $filename)
	{
		$filesname = $this->getFilesName($directory);

		for ($i = 0; $i < count($filesname); $i++) {
			if (substr($filesname[$i], 0, strrpos($filesname[$i], '_')) == substr($filename, 0, strrpos($filename, '_')))
				return $i;
		}
		return -1;
	}

	/**
	 * Return the $index ième file in $directory
	 * @param string $directory
	 * @param int $index
	 * @return mixed|string
	 */
	public function getFileByIndex(string $directory, int $index)
	{
		$filesnames = $this->getFilesName($directory);
		if (isset($filesnames[$index]))
			return $filesnames[$index];
		return "";
	}

	public function deleteDirectory(string $dir)
	{
		if (!file_exists($dir)) {
			return true;
		}

		if (!is_dir($dir)) {
			return unlink($dir);
		}

		foreach (scandir($dir) as $item) {
			if ($item == '.' || $item == '..') {
				continue;
			}

			if (!$this->deleteDirectory($dir . DIRECTORY_SEPARATOR . $item)) {
				return false;
			}

		}

		return rmdir($dir);
	}
}