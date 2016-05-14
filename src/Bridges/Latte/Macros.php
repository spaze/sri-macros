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
		$set->addMacro('stylesheet', array($this, 'macroStylesheet'));
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

		return $writer->write(
			"echo '<script"
			. " src=\"' . %escape('" . $url . "') . '\""
			. " integrity=\"' . %escape('" . $hash . "') . '\"'"
			. $this->buildAttributes($node->tokenizer)
			. " . '></script>';"
		);
	}


	/**
	 * {stylesheet ...}
	 *
	 * @param \Latte\MacroNode $node
	 * @param \Latte\PhpWriter $writer
	 * @return string
	 */
	public function macroStylesheet(\Latte\MacroNode $node, \Latte\PhpWriter $writer)
	{
		if ($node->modifiers) {
			trigger_error("Modifiers are not allowed in {{$node->name}}", E_USER_WARNING);
		}

		$resource = $node->tokenizer->fetchWord();
		$url = $this->sriConfig->getUrl($resource);
		$hash = $this->sriConfig->getHash($resource);

		return $writer->write(
			"echo '<link rel=\"stylesheet\""
			. " href=\"' . %escape('" . $url . "') . '\""
			. " integrity=\"' . %escape('" . $hash . "') . '\"'"
			. $this->buildAttributes($node->tokenizer)
			. " . '>';"
		);
	}


	/**
	 * Build attributes.
	 *
	 * @param \Latte\MacroTokens $tokens
	 * @return string
	 */
	private function buildAttributes(\Latte\MacroTokens $tokens)
	{
		$attributes = array("'crossorigin'" => "'anonymous'");
		$isAttrName = true;
		$attrName = $attrValue = null;
		while ($tokens->nextToken()) {
			if ($tokens->isCurrent(\Latte\MacroTokens::T_SYMBOL, \Latte\MacroTokens::T_VARIABLE)) {
				${$isAttrName ? 'attrName' : 'attrValue'} = ($tokens->isCurrent(\Latte\MacroTokens::T_VARIABLE) ? $tokens->currentValue() : "'{$tokens->currentValue()}'");
			} elseif ($tokens->isCurrent('=', '=>')) {
				$isAttrName = false;
			} elseif ($tokens->isCurrent(',')) {
				$attributes[$attrName] = ($attrValue ?: null);
				$isAttrName = true;
				$attrName = $attrValue = null;
			} elseif (!$tokens->isCurrent(\Latte\MacroTokens::T_WHITESPACE)) {
				throw new \Latte\CompileException("Unexpected '{$tokens->currentValue()}' in {script $node->args}");
			}
			if (!$tokens->isNext()) {
				$attributes[$attrName] = ($attrValue ?: null);
			}
		}

		$attrCode = '';
		foreach ($attributes as $name => $value) {
			$attrCode .= " . ' ' . %escape(" . $name . ")";
			if ($value !== null) {
				$attrCode .= " . '=\"' . %escape(" . $value . ") . '\"'";
			}
		}

		return $attrCode;
	}

}
