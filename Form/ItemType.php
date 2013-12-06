<?php

namespace Calitarus\BitPayBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class ItemType extends AbstractType {

	private $item_id;

	public function __construct($item_id) {
		$this->item_id = $item_id;
	}

	public function getName() {
		return ''; // set to empty so the fields have the names that the bitpay API expects
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
			'intention'       => 'cart_item',
		));
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {

		$builder->add('action', 'hidden', array(
			'data' => "cartAdd"
		));

		$builder->add('data', 'hidden', array(
			'data' => $this->item_id
		));
	}
}
