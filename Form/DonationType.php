<?php

namespace Calitarus\BitPayBundle\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;


class DonationType extends AbstractType {

	private $order_id;
	private $apikey;


	public function __construct($order_id, $apikey) {
		$this->order_id = $order_id;
		$this->apikey = $apikey;
	}

	public function getName() {
		return ''; // set to empty so the fields have the names that the bitpay API expects
	}

	public function setDefaultOptions(OptionsResolverInterface $resolver) {
		$resolver->setDefaults(array(
			'intention'       => 'donation',
		));
	}

	public function buildForm(FormBuilderInterface $builder, array $options) {

		$builder->add('action', 'hidden', array(
			'data' => "checkout"
		));

		$builder->add('orderID', 'hidden', array(
			'data' => $this->order_id
		));

		$builder->add('price', 'number', array(
			'label' => 'amount',
			'data' => '10.00',
			'attr' => array(
				'min' => '0.01',
				'step' => '0.01',
				'maxlength' => 6,
			)
		));

		$builder->add('currency', 'choice', array(
			'label' => 'currency',
			'data' => 'EUR',
			'choices' => array(
				'BTC' => 'c.btc',
				'EUR' => 'c.eur'
			)
		));

		$builder->add('data', 'hidden', array(
			'data' => $this->apikey
		));

		$builder->add('submit', 'submit', array('label'=>'submit'));

	}
}
