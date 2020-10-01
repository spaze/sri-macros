<?php
declare(strict_types = 1);

namespace Spaze\SubresourceIntegrity\Resource;

class FileResource implements ResourceInterface
{

	/** @var string */
	private $filename;


	public function __construct(string $filename)
	{
		$this->filename = $filename;
	}


	public function getContent(): string
	{
		return file_get_contents($this->filename);
	}


	public function getExtension(): ?string
	{
		return pathinfo($this->filename, PATHINFO_EXTENSION);
	}

}
