<?php
namespace Sfranken;

/**
 * A PHP class to generate HTML fields (with Semantic-UI specific HTML markup)
 * from JSON files or a database.
 *
 * @author Sebastiaan Franken <sebastiaan@sebastiaanfranken.nl>
 */

use DOMDocument;
use DOMElement;

class Builder
{

	/**
	 * The raw JSON preferences object
	 *
	 * @var object
	 * @access protected
	 */
	protected $preferences;

	/**
	 * The database model (Eloquent for now, so Laravel only)
	 * to use as a data source
	 *
	 * @var array
	 * @access protected
	 */
	protected $model;

	/**
	 * The constructor takes care of loading the preferences (in a JSON format)
	 * into the global $preferences object.
	 *
	 * @param string $preferences The users' preferences in JSON format
	 * @param array $model The model to use
	 * @return void
	 */
	public function __construct($preferences, $model = null)
	{
		$this->preferences = json_decode($preferences);

		if(!is_null($model))
		{
			$this->model = $model;
		}
	}

	/**
	 * Getter for the class preferences property
	 *
	 * @return stdClass
	 */
	public function preferences()
	{
		return $this->preferences;
	}

	/**
	 * Get a specific key from the class collection property if it exists.
	 * If it doesn't it'll return the current class instance.
	 *
	 * @param string $collection The collection key to check for
	 * @return mixed
	 */
	public function getCollection($collection)
	{
		if(property_exists($this->preferences, $collection))
		{
			$return = json_encode($this->preferences->$collection);

			return is_null($this->model) ? new Builder($return) : new Builder($return, $this->model);
		}

		return $this;
	}

	/**
	 * Transforms the preferences object into HTML with a Semantic-UI specific
	 * syntax based on a ruleset found in a JSON file
	 *
	 * @return mixed
	 */
	public function build()
	{
		if(is_object($this->preferences) && count($this->preferences) > 0)
		{
			$dom = new DOMDocument('1.0', 'utf-8');

			foreach($this->preferences as $key => $preferences)
			{
				$div = new DOMElement('div');
				$dom->appendChild($div);
				$div->setAttribute('class', 'field');

				$label = new DOMElement('label', $preferences->label);
				$div->appendChild($label);
				$label->setAttribute('for', $key);

				$select = new DOMElement('select');
				$div->appendChild($select);
				$select->setAttribute('name', $key);

				switch($preferences->type)
				{
					case "boolean":
						$yes = new DOMElement('option', 'Ja');
						$select->appendChild($yes);
						$yes->setAttribute('value', 'true');

						$no = new DOMElement('option', 'Nee');
						$select->appendChild($no);
						$no->setAttribute('value', 'false');

						if(is_array($this->model) && array_key_exists($key, $this->model))
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
							if($this->model[$key] == 'true')
							{
								$yes->setAttribute('selected', 'selected');
							}
							else
							{
								$no->setAttribute('selected', 'selected');
							}
						}
						elseif(!array_key_exists($key, $this->model) && property_exists($preferences, 'default'))
						{
							if($preferences->default == true)
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

						if( (is_array($this->model) && array_key_exists($key, $this->model)) || (!array_key_exists($key, $this->model) && property_exists($preferences, 'default')) )
						{
							if($this->model[$key] == 'asc')
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
						foreach($preferences->values as $value => $label)
						{
							$option = new DOMElement('option', $label);
							$select->appendChild($option);
							$option->setAttribute('value', $value);

							if(is_array($this->model) && array_key_exists($key, $this->model) && $this->model[$key] == $value)
							{
								$option->setAttribute('selected', 'selected');
							}
							elseif(!array_key_exists($key, $this->model) && property_exists($preferences, 'default') && $preferences->default == $value)
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
