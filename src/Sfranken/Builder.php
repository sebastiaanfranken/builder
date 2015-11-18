<?php
namespace Sfranken;

use DOMDocument;
use DOMElement;

class Builder
{
	protected $preferences;

	public function __construct($preferences)
	{
		$this->preferences = json_decode($preferences);
	}

	public function preferences()
	{
		return $this->preferences;
	}

	public function getCollection($collection)
	{
		if(property_exists($this->preferences, $collection))
		{
			$return = json_encode($this->preferences->$collection);

			return new Builder($return);
		}

		return $this;
	}

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

						if(property_exists($preferences, 'default'))
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

					case "select":
						foreach($preferences->values as $value => $label)
						{
							$option = new DOMElement('option', $label);
							$select->appendChild($option);
							$option->setAttribute('value', $value);

							if(property_exists($preferences, 'default') && $preferences->default == $value)
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
