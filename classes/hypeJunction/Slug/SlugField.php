<?php

namespace hypeJunction\Slug;

use ElggEntity;
use hypeJunction\Fields\Field;
use Symfony\Component\HttpFoundation\ParameterBag;

class SlugField extends Field {

	/**
     * @param ElggEntity $entity
     * @param mixed $context
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
     * @param ElggEntity $entity
     * @param ParameterBag $parameters
     */
    public function save(ElggEntity $entity, ParameterBag $parameters) {
		$slugs = elgg()->{'posts.slug'};
		/* @var $slugs \hypeJunction\Slug\SlugService */

		$slugs->setSlug($entity, $parameters->get($this->name));
	}

	/**
     * @param ElggEntity $entity
     * @return mixed
     */
    public function retrieve(ElggEntity $entity) {
		$slugs = elgg()->{'posts.slug'};
		/* @var $slugs \hypeJunction\Slug\SlugService */

		return $slugs->getSlug($entity);
	}
}