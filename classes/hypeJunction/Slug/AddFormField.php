<?php

namespace hypeJunction\Slug;

class AddFormField {

	/**
	 * Add slug field
	 *
	 * @param \Elgg\Hook $hook Hook
	 *
	 * @return mixed
	 */
	public function __invoke(\Elgg\Hook $hook) {

		$fields = $hook->getValue();

		$fields['slug'] = [
			'#type' => 'text',
			'#setter' => function (\ElggEntity $entity, $value) {
				return elgg()->get('posts.slug')->setSlug($entity, $value);
			},
			'#section' => 'sidebar',
			'#priority' => 200,
			'#visibility' => function (\ElggEntity $entity) use ($hook) {
				if (!elgg_is_admin_logged_in()) {
					return false;
				}

				$params = [
					'entity' => $entity,
				];

				return $hook->elgg()->hooks->trigger(
					'uses:slug',
					"$entity->type:$entity->subtype",
					$params,
					true
				);
			},
			'#profile' => false,
		];

		return $fields;
	}
}
