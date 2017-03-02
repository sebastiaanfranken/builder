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
	 * The DOMDocument instance
	 *
	 * @var DOMDocument
	 */
	protected $dom;

	/**
	 * DOM Elements that are get or set anonymously
	 *
	 * @var array
	 */
	protected $elements = [];

	/**
	 * Resets the primary and secondary datasets to be empty arrays
	 *
	 * @return void
	 */
	public function __construct()
	{
		$this->primary = [];
		$this->secondary = [];
		$this->dom = new DOMDocument();
	}

	/**
	 * Magic getter for DOM elements
	 *
	 * @param string $element The element to get
	 * @return DOMElement
	 */
	public function __get($element)
	{
		return $this->elements[$element];
	}

	/**
	 * Magic setter for DOM elements
	 *
	 * @param string $key The elements name
	 * @param DOMElement $element The actual element
	 * @return Builder
	 */
	public function __set($key, $element)
	{
		$this->elements[$key] = $element;
		return $this;
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
	 * Gets a specific HTML element from the elements array if it exists.
	 *
	 * @param string $tag The tagname, can be "div", "select" or any other valid tag
	 * @param string $name The elements name. Is combined with the tag
	 * @return string|false
	 */
	protected function getElement($tag, $name)
	{
		$element = $name . "_" . $tag;

		return array_key_exists($element, $this->elements) ? $this->elements[$element] : false;
	}

	/**
	 * Transform both datasets into HTML with Semantic-UI syntax
	 *
	 * @return mixed
	 */
	public function build()
	{
		$primary = $this->getPrimary();
		$secondary = $this->getSecondary();

		if(is_array($secondary) && count($secondary) > 0)
		{
			foreach($secondary as $key => $preferences)
			{
				if(Auth::user()->can($preferences["can"]))
				{
					$name = $key . "_div";
					$div = $this->$name = new DOMElement("div");
					$this->dom->appendChild($div);
					$div->setAttribute("class", "field");

					$name = $key . "_label";
					$label = $this->$name = new DOMElement("label", $preferences["label"]);
					$this->dom->appendChild($label);
					$label->setAttribute("for", $key);

					$name = $key . "_select";
					$select = $this->$name = new DOMElement("select");
					$this->dom->appendChild($select);
					$select->setAttribute("name", $key);

					switch($preferences["type"])
					{
						case "boolean":
							$this->buildBoolean($key, $preferences);
						break;

						case "sort":
							$this->buildSort($key, $preferences);
						break;

						case "select":
							$this->buildSelect($key, $preferences);
						break;
					}
				}
			}

			return $this->dom->saveHTML();
		}

		return false;
	}

	/**
	 * Builds the HTML for the boolean type fields
	 *
	 * @param string $key The HTML name (key)
	 * @param array $preferences The preferences carried over from build()
	 * @see build()
	 * @see getElement()
	 * @return void
	 */
	private function buildBoolean($key, array $preferences)
	{
		$primary = $this->getPrimary();
		$select = $this->getElement("select", $key);

		$yes = new DOMElement("option", "Ja");
		$select->appendChild($yes);
		$yes->setAttribute("value", "true");

		$no = new DOMElement("option", "Nee");
		$select->appendChild($no);
		$no->setAttribute("value", "false");

		if(array_key_exists($key, $primary))
		{
			/*
			 * Watch out for this one:
			 * The database stores booleans as strings, becasue the field
			 * type is "varchar", so boolean false literally becomes
			 * "false".
			 *
			 * So, instead of comparing for a boolean we have to check for
			 * a string
			 */
			if($primary[$key] == "true")
			{
				$yes->setAttribute("selected", "selected");
			}
			else
			{
				$no->setAttribute("selected", "selected");
			}
		}
		elseif(!array_key_exists($key, $primary) && array_key_exists("default", $preferences))
		{
			if($preferences["default"] == true)
			{
				$yes->setAttribute("selected", "selected");
			}
			else
			{
				$no->setAttribute("selected", "selected");
			}
		}
	}

	/**
	 * Builds the HTML for the fields with the sort type
	 *
	 * @param string $key
	 * @param array $preferences The preferences, taken from build()
	 * @see build()
	 * @see getElement()
	 * @return void
	 */
	private function buildSort($key, array $preferences)
	{
		$primary = $this->getPrimary();
		$select = $this->getElement("select", $key);

		$asc = new DOMElement("option", "Oplopend");
		$select->appendChild($asc);
		$asc->setAttribute("value", "asc");

		$desc = new DOMElement("option", "Aflopend");
		$select->appendChild($desc);
		$desc->setAttribute("value", "desc");

		if(array_key_exists($key, $primary) || (!array_key_exists($key, $primary) && array_key_exists("default", $preferences)))
		{
			if($primary[$key] == "asc")
			{
				$asc->setAttribute("selected", "selected");
			}
			else
			{
				$desc->setAttribute("selected", "selected");
			}
		}
	}

	/**
	 * Builds the HTML for the fields with the "select" type
	 *
	 * @param string $key
	 * @param array $preferences The preferences, passed on in build()
	 * @see build()
	 * @see getElement()
	 * @return void
	 */
	private function buildSelect($key, array $preferences)
	{
		$select = $this->getElement("select", $key);
		$primary = $this->getPrimary();

		foreach($preferences["values"] as $value => $label)
		{
			$option = new DOMElement("option", $label);
			$select->appendChild($option);
			$option->setAttribute("value", $value);

			if((array_key_exists($key, $primary) && $primary[$key] == $value) || (!array_key_exists($key, $primary) && array_key_exists('default', $preferences) && $preferences['default'] == $value))
			{
				$option->setAttribute("selected", "selected");
			}
		}
	}
}
