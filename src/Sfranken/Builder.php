<?php
namespace Sfranken;

/**
 * A PHP class to generate HTML from two possible datasets
 *
 * @author Sebastiaan Franken <sebastiaan@sebastiaanfranken.nl>
 */

use DOMDocument;
use DOMElement;

class Builder
{
	/**
	 * The primary dataset. This is usually fed from
	 * a database
	 *
	 * @var array
	 */
	protected $primary = [];

	/**
	 * The secondary dataset. This is usually fed from
	 * a JSON file
	 *
	 * @var array
	 */
	protected $secondary = [];

	/**
	 * Resets the primary and secondary datasets to be empty arrays
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->primary = [];
		$this->secondary = [];
	}

	/**
	 * Getter for the primary dataset
	 *
	 * @return array
	 */
	public function getPrimary()
	{
		return $this->primary;
	}

	/**
	 * Setter for the primary dataset
	 *
	 * @param array $primary The new primary dataset
	 * @return Builder
	 */
	public function setPrimary(array $primary = array())
	{
		$this->primary = $primary;
		return $this;
	}

	/**
	 * Getter for the secondary dataset
	 *
	 * @return array
	 */
	public function getSecondary()
	{
		return $this->secondary;
	}

	/**
	 * Setter for the secondary dataset
	 *
	 * @param array $secondary The secondary dataset
	 * @return Builder
	 */
	public function setSecondary(array $secondary = array())
	{
		$this->secondary = $secondary;
		return $this;
	}

	/**
	 * Get a "collection" from the secondary
	 * datasets if it exists.
	 *
	 * If it doesn't it'll return the current class instance
	 *
	 * @param string $collection The collection to check for/return
	 * @return mixed
	 */
	public function getCollection($collection)
	{
		if(array_key_exists($collection, $this->secondary))
		{
			$primary = $this->getPrimary();
			$secondary = $this->getSecondary();

			if(array_key_exists($collection, $secondary))
			{
				$secondary = $secondary[$collection];
			}

			$builder = new Builder();
			$builder->setPrimary($primary);
			$builder->setSecondary($secondary);

			return $builder;
		}

		return $this;
	}

	/**
	 * Transform both datasets into HTML with Semantic-UI syntax
	 *
	 * @return mixed
	 */
	public function build()
	{
		if(is_array($this->getSecondary()) && count($this->getSecondary()) > 0)
		{
			$dom = new DOMDocument('1.0', 'utf-8');
			$primary = $this->getPrimary();
			$secondary = $this->getSecondary();

			foreach($secondary as $key => $preferences)
			{
				/*
				 * Create the div the field will be appended into
				 */
				$div = new DOMElement('div');
				$dom->appendChild($div);
				$div->setAttribute('class', 'field');

				/*
				 * Create the label for the field
				 */
				$label = new DOMElement('label', $preferences['label']);
				$div->appendChild($label);
				$label->setAttribute('for', $key);

				/*
				 * Create the select HTML element
				 */
				$select = new DOMElement('select');
				$div->appendChild($select);
				$select->setAttribute('name', $key);

				/*
				 * Loop over the possible fieldtypes and
				 * apply logic accordingly
				 */
				switch($preferences['type'])
				{
					case "boolean":
						$yes = new DOMElement('option', 'Ja');
						$select->appendChild($yes);
						$yes->setAttribute('value', 'true');

						$no = new DOMElement('option', 'Nee');
						$select->appendChild($no);
						$no->setAttribute('value', 'false');

						if(array_key_exists($key, $primary))
						{
							/*
							 * Watch out for this one:
							 * My database stores booleans as strings, because the field
							 * type is "varchar". So, false literally becomes "false"
							 * (as in the English word)..
							 *
							 * So, instead of comparing for a boolean we have to check
							 * for a string.
							 */
							if($primary[$key] == "true")
							{
								$yes->setAttribute('selected', 'selected');
							}
							else
							{
								$no->setAttribute('selected', 'selected');
							}
						}
						elseif(!array_key_exists($key, $primary) && array_key_exists('default', $preferences))
						{
							if($preferences['default'] == true)
							{
								$yes->setAttribute('selected', 'selected');
							}
							else
							{
								$no->setAttribute('selected', 'selected');
							}
						}
					break;

					case "sort":
						$asc = new DOMElement('option', 'Oplopend');
						$select->appendChild($asc);
						$asc->setAttribute('value', 'asc');

						$desc = new DOMElement('option', 'Aflopend');
						$select->appendChild($desc);
						$desc->setAttribute('value', 'desc');

						if(array_key_exists($key, $primary) || (!array_key_exists($key, $primary) && array_key_exists('default', $preferences)))
						{
							if($primary[$key] == "asc")
							{
								$asc->setAttribute('selected', 'selected');
							}
							else
							{
								$desc->setAttribute('selected', 'selected');
							}
						}
					break;

					case "select":
						foreach($preferences['values'] as $value => $label)
						{
							$option = new DOMElement('option', $label);
							$select->appendChild($option);
							$option->setAttribute('value', $value);

							if((array_key_exists($key, $primary) && $primary[$key] == $value) || (!array_key_exists($key, $primary) && array_key_exists('default', $preferences) && $preferences['default'] == $value))
							{
								$option->setAttribute('selected', 'selected');
							}
						}
					break;
				}
			}

			return $dom->saveHTML();
		}

		return false;
	}
}
