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
		$isAttrName = true;
		while ($node->tokenizer->nextToken()) {
			if ($node->tokenizer->isCurrent(\Latte\MacroTokens::T_SYMBOL, \Latte\MacroTokens::T_VARIABLE)) {
				$attributes .= " . '" . ($isAttrName ? ' ' : '"') . "'";
				if ($node->tokenizer->currentToken()[\Latte\Tokenizer::TYPE] === \Latte\MacroTokens::T_VARIABLE) {
					$attributes .= ' . %escape(' . $node->tokenizer->currentValue() . ')';
				} else {
					$attributes .= " . %escape('" . $node->tokenizer->currentValue() . "')";
				}
				$attributes .= ($isAttrName ? '' : " . '\"'");
			} elseif ($node->tokenizer->isCurrent('=', '=>')) {
				$attributes .= " . '='";
				$isAttrName = false;
			} elseif ($node->tokenizer->isCurrent(',')) {
				$isAttrName = true;
			} elseif (!$node->tokenizer->isCurrent(\Latte\MacroTokens::T_WHITESPACE)) {
				throw new \Latte\CompileException("Unexpected '{$node->tokenizer->currentValue()}' in {script $node->args}");
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

}
