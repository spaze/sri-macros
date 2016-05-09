<?php
namespace Spaze\SubresourceIntegrity\Bridges\Latte;

class Macros
{

	/** @var \Spaze\SubresourceIntegrity\Config */
	private $sriConfig;


	/**
	 * Constructor.
	 *
	 * @param \Spaze\SubresourceIntegrity\Config $sriConfig
	 */
	public function __construct(\Spaze\SubresourceIntegrity\Config $sriConfig)
	{
		$this->sriConfig = $sriConfig;
	}


	/**
	 * Install macros.
	 *
	 * @param \Latte\Compiler $compiler
	 * @return \Latte\Macros\MacroSet
	 */
	public function install(\Latte\Compiler $compiler)
	{
		$set = new \Latte\Macros\MacroSet($compiler);
		$set->addMacro('script', array($this, 'macroScript'));
		return $set;
	}


	/**
	 * {script ...}
	 *
	 * @param \Latte\MacroNode $node
	 * @param \Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroScript(\Latte\MacroNode $node, \Latte\PhpWriter $writer)
	{
		if ($node->modifiers) {
			trigger_error("Modifiers are not allowed in {{$node->name}}", E_USER_WARNING);
		}

		$resource = $node->tokenizer->fetchWord();
		$url = $this->sriConfig->getUrl($resource);
		$hash = $this->sriConfig->getHash($resource);

		$attributes = '';
		while ($words = $node->tokenizer->fetchWords()) {
			if (count($words) > 1) {
				$attributes .= " . ' ' . %escape(" . $this->getValue($words[0]) . ")"
					. " . '=\"' . %escape(" . $this->getValue($words[1]) . ") . '\"'";
			} else {
				$attributes .= " . ' ' . %escape(" . $this->getValue($words[0]) . ")";
			}
		}

		return $writer->write(
			"echo '<script"
			. " src=\"' . %escape('" . $url . "') . '\""
			. " integrity=\"' . %escape('" . $hash . "') . '\"'"
			. $attributes
			. " . '></script>';"
		);
	}


	/**
	 * Get variable value or string for non-variables.
	 *
	 * @param string $token
	 * @return string
	 */
	private function getValue($token)
	{
		return (isset($token[0]) && $token[0] === '$' ? $token : "'{$token}'");
	}

}
