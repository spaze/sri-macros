<?php
namespace Spaze\SubresourceIntegrity;

/**
 * SubresourceIntegrity\FileBuilder service.
 *
 * @author Michal Špaček
 */
class FileBuilder
{

	/**
	 * Get build file mode data.
	 * @param string $resource
	 * @param string $pathPrefix
	 * @param string $buildPrefix
	 * @return \stdClass
	 */
	public function build($resource, $pathPrefix, $buildPrefix)
	{
		$localFilename = sprintf('%s/%s/%s', rtrim(getcwd(), '/'), trim($pathPrefix, '/'), ltrim($resource, '/'));
		$build = sprintf('%s/%s.%s',
			trim($buildPrefix, '/'),
			rtrim(strtr(base64_encode(hash_file('sha256', $localFilename, true)), '+/', '-_'), '='),  // Encoded to base64url, see https://tools.ietf.org/html/rfc4648#section-5
			pathinfo($localFilename, PATHINFO_EXTENSION)
		);
		$buildFilename = sprintf('%s/%s/%s', rtrim(getcwd(), '/'), trim($pathPrefix, '/'), $build);

		if (!is_writable(dirname($buildFilename))) {
			throw new Exceptions\DirectoryNotWritableException('Directory ' . dirname($buildFilename) . " doesn't exist or isn't writable");
		}

		copy($localFilename, $buildFilename);

		$data = new \stdClass();
		$data->url = $build;
		$data->filename = $buildFilename;
		return $data;
	}

}
