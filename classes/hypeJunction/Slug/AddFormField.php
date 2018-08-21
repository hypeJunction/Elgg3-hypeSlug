<?php

namespace hypeJunction\Slug;

class AddFormField {

	/**
	 * Add slug field
	 *
	 * @param \Elgg\Hook $hook Hook
	 *
	 * @return mixed
	 * @throws \InvalidParameterException
	 */
	public function __invoke(\Elgg\Hook $hook) {

		$fields = $hook->getValue();
		/* @var $fields \hypeJunction\Fields\Collection */

		$fields->add('slug', new SlugField([
			'type' => 'text',
			'is_profile_field' => false,
			'is_admin_field' => true,
			'section' => 'sidebar',
			'priority' => 200,
		]));

		return $fields;
	}
}
