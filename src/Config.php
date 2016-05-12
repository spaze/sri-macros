<?php
namespace Spaze\SubresourceIntegrity;

/**
 * SubresourceIntegrity\Config service.
 *
 * @author Michal Špaček
 */
class Config
{

	/** @var array of key => array of resources */
	protected $resources = array();

	/** @var array of (url => prefix, path => prefix) */
	protected $localPrefix = array();

	/** @var array of hashing algorithms */
	protected $hashingAlgos = array();


	/**
	 * Set resources.
	 *
	 * @param array $resources
	 */
	public function setResources(array $resources)
	{
		$this->resources = $resources;
	}


	/**
	 * Set prefix for local resources.
	 *
	 * @param string $prefix
	 */
	public function setLocalPrefix($prefix)
	{
		$this->localPrefix = $prefix;
	}


	/**
	 * Set one or more hashing algorithms.
	 *
	 * @param string|array $algos
	 */
	public function setHashingAlgos($algos)
	{
		$this->hashingAlgos = (array)$algos;
	}


	/**
	 * Get full URL for a resource.
	 *
	 * @param string $resource
	 * @return string
	 */
	public function getUrl($resource)
	{
		if (is_array($this->resources[$resource])) {
			$url = $this->resources[$resource]['url'];
		} else {
			$url = sprintf('%s/%s',
				rtrim($this->localPrefix['url'], '/'),
				ltrim($this->resources[$resource], '/')
			);
		}
		return $url;
	}


	/**
	 * Get SRI hash for a resource.
	 *
	 * @param string $resource
	 * @return array
	 */
	public function getHash($resource)
	{
		if (is_array($this->resources[$resource])) {
			if (is_array($this->resources[$resource]['hash'])) {
				$hash = implode(' ', $this->resources[$resource]['hash']);
			} else {
				$hash = $this->resources[$resource]['hash'];
			}
		} else {
			$file = sprintf('%s/%s/%s',
				rtrim(getcwd(), '/'),
				trim($this->localPrefix['path'], '/'),
				ltrim($this->resources[$resource], '/')
			);
			$fileHashes = array();
			foreach ($this->hashingAlgos as $algo) {
				$fileHashes[] = $algo . '-' . base64_encode(hash_file($algo, $file, true));
			}
			$hash = implode(' ', $fileHashes);
		}
		return $hash;
	}

}
