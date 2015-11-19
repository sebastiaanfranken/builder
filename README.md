# PHP Library for transforming JSON files into HTML

This is a simple PHP library (for use in Laravel, mostly) that transforms a JSON file into
usable HTML code with a _specific_ HTML structure (Semantic-UI).

## Howto

	<?php
	$file = file_get_contents('path/to/your/file.json');
	$builder = new Sfranken\Builder($file);

	$settings = $builder->getCollection('user');

	print $settings->build();

Where the contents of _path/to/your/file.json_ would look like this:

	{
		"user": {
			"option name": {
				"label": "The option label",
				"type": "select",
				"default": "username",
				"values": {
					"username": "The username",
					"password": "The password"
				}
			}
		}
	}

### JSON Structure
- `option name` should make sense
- `label` is the HTML label (gets wrapped in a `<label>` tag)
	- The `option name` is used as the `for` attribute
- `type` can be one of the following:
	- `select`
	- `boolean`
	- `sort`
- `default` is the default value, if this isn't set the 1st value from `values` will be used
- `values` is a JSON object (key => value) with possible values for the setting you're changing
