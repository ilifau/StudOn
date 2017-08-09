<?php
namespace ILIAS\UI\Implementation\Component\Table;

use ILIAS\UI\Component\Table as T;
use ILIAS\UI\Implementation\Component\SignalGeneratorInterface;

/**
 * Implementation of factory for tables
 *
 * @author Nils Haagen <nhaageng@concepts-and-training.de>
 */
class Factory implements T\Factory {

	/**
	 * @var SignalGeneratorInterface
	 */
	protected $signal_generator;

	/**
	 * @param SignalGeneratorInterface $signal_generator
	 */
	public function __construct(SignalGeneratorInterface $signal_generator) {
		$this->signal_generator = $signal_generator;
	}

	/**
	 * @inheritdoc
	 */
	public function presentation($title, array $view_controls, array $rows) {
		return new Presentation($title, $view_controls, $rows);
	}


	/**
	 * @inheritdoc
	 */
	public function presentationRow($title_field) {
		return new PresentationRow($title_field, $this->signal_generator);
	}

}
