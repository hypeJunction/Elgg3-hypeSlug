<?php

namespace hypeJunction\Slug;

/**
 * AddFormField class.
 */
class AddFormField {

	/**
	 * Add slug field
	 *
	 * @param \Elgg\Event $event Hook
	 *
	 * @return mixed
	 * @throws \Elgg\Exceptions\InvalidParameterException
	 */
	public function __invoke(\Elgg\Event $event) {

		$fields = $event->getValue();
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
