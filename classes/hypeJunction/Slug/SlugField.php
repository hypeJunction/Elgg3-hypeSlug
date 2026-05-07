<?php

namespace hypeJunction\Slug;

use ElggEntity;
use hypeJunction\Fields\Field;
use Symfony\Component\HttpFoundation\ParameterBag;

/**
 * SlugField class.
 */
class SlugField extends Field {

	/**
	 * Is visible.
	 *
	 * @param mixed $entity  Entity
	 * @param mixed $context Context
	 * @return mixed
	 */
	public function isVisible(ElggEntity $entity, $context = null) {

		$params = [
			'entity' => $entity,
		];

		$enabled = elgg()->hooks->trigger(
			'uses:slug',
			"$entity->type:$entity->subtype",
			$params,
			true
		);

		if (!$enabled) {
			return false;
		}

		return parent::isVisible($entity, $context);
	}

	/**
	 * Save.
	 *
	 * @param mixed $entity     Entity
	 * @param mixed $parameters Parameters
	 * @return mixed
	 */
	public function save(ElggEntity $entity, ParameterBag $parameters) {
		$slugs = elgg()->{'posts.slug'};
		/* @var $slugs \hypeJunction\Slug\SlugService */

		$slugs->setSlug($entity, $parameters->get($this->name));
	}

	/**
	 * Retrieve.
	 *
	 * @param mixed $entity Entity
	 * @return mixed
	 */
	public function retrieve(ElggEntity $entity) {
		$slugs = elgg()->{'posts.slug'};
		/* @var $slugs \hypeJunction\Slug\SlugService */

		return $slugs->getSlug($entity);
	}
}
