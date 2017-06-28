<?php

namespace PremekKoch\Elms;

use Nette\SmartObject;


final class ElmsCustomer
{
	use SmartObject;
	const COUNTRY_CZE = 'CZ';
	const COUNTRY_SVK = 'SK';

	/** @var bool */
	private $initialized = false;

	/** @var string */
	private $company;

	/** @var string */
	private $name;

	/** @var string */
	private $surname;

	/** @var string */
	private $email;

	/** @var string */
	private $street;

	/** @var string */
	private $city;

	/** @var string */
	private $country;

	/** @var string */
	private $zip;

	/** @var string */
	private $phone;

	/** @var string */
	private $ic;

	/** @var string */
	private $deliveryCompany;

	/** @var string */
	private $dic;

	/** @var string */
	private $deliveryName;

	/** @var string */
	private $deliverySurname;

	/** @var string */
	private $deliveryStreet;

	/** @var string */
	private $deliveryCity;

	/** @var string */
	private $deliveryZip;

	/** @var string */
	private $deliveryCountry;

	/** @var string */
	private $deliveryPhone;

	/** @var string */
	private $deliveryEmail;


	public function createCustomer(string $name, string $surname, string $street, string $city, string $zip, string $country): void
	{
		if (!$this->checkCountry($country)) {
			throw new ElmsException('Undefined country code.');
		}

		if (!$this->checkZip($zip)) {
			throw new ElmsException('Wrong zip format, only 5 digits allowed.');
		}

		$this->name = $name;
		$this->surname = $surname;
		$this->street = $street;
		$this->city = $city;
		$this->country = $country;
		$this->zip = $zip;
		$this->initialized = true;
	}


	public function setCompany(string $company, string $ic, string $dic = null): void
	{
		$this->company = $company;
		$this->ic = $ic;
		$this->dic = $dic;
	}


	public function setContact(?string $email, ?string $phone): void
	{
		$this->email = $email;
		$this->phone = $phone;

		if (!$this->checkEmail($email)) {
			throw new ElmsException('Invalid email.');
		}
	}


	public function setDeliveryAddress(?string $deliveryName, ?string $deliverySurname, ?string $deliveryStreet, ?string $deliveryCity, ?string $deliveryZip, ?string $deliveryCountry): void
	{
		$this->deliveryName = $deliveryName;
		$this->deliverySurname = $deliverySurname;
		$this->deliveryStreet = $deliveryStreet;
		$this->deliveryCity = $deliveryCity;
		$this->deliveryZip = $deliveryZip;
		$this->deliveryCountry = $deliveryCountry;

		if (!$this->checkCountry($deliveryCountry)) {
			throw new ElmsException('Undefined country code.');
		}

		if (!$this->checkZip($deliveryZip)) {
			throw new ElmsException('Wrong zip format, only 5 digits allowed.');
		}
	}


	public function setDeliveryCompany(string $deliveryCompany): void
	{
		$this->deliveryCompany = $deliveryCompany;
	}


	public function setDeliveryContact(?string $deliveryEmail, ?string $deliveryPhone): void
	{
		$this->deliveryEmail = $deliveryEmail;
		$this->deliveryPhone = $deliveryPhone;

		if (!$this->checkEmail($deliveryEmail)) {
			throw new ElmsException('Invalid delivery email.');
		}
	}


	public function getCustomer(): array
	{
		if (!$this->initialized) {
			throw new ElmsException('Subject was not set.');
		}

		$customer = [
			'name' => $this->name,
			'surname' => $this->surname,
			'street' => $this->street,
			'city' => $this->city,
			'country' => $this->country,
			'postal_code' => $this->zip,
		];

		if ($this->company) {
			$customer['company'] = $this->company;
		}
		if ($this->ic) {
			$customer['ic'] = $this->ic;
		}
		if ($this->dic) {
			$customer['dic'] = $this->dic;
		}
		if ($this->email) {
			$customer['email'] = $this->email;
		}
		if ($this->phone) {
			$customer['phone'] = $this->phone;
		}
		if ($this->deliveryName) {
			$customer['del_name'] = $this->deliveryName;
		}
		if ($this->deliverySurname) {
			$customer['del_surname'] = $this->deliverySurname;
		}
		if ($this->deliveryStreet) {
			$customer['del_street'] = $this->deliveryStreet;
		}
		if ($this->deliveryCity) {
			$customer['del_city'] = $this->deliveryCity;
		}
		if ($this->deliveryZip) {
			$customer['del_postal_code'] = $this->deliveryZip;
		}
		if ($this->deliveryCountry) {
			$customer['del_country'] = $this->deliveryCountry;
		}
		if ($this->deliveryCompany) {
			$customer['del_company'] = $this->deliveryCompany;
		}
		if ($this->deliveryEmail) {
			$customer['del_email'] = $this->deliveryEmail;
		}
		if ($this->deliveryPhone) {
			$customer['del_phone'] = $this->deliveryPhone;
		}

		return $customer;
	}


	private function checkCountry(?string $country): bool
	{
		return $country === self::COUNTRY_CZE || $country === self::COUNTRY_SVK;
	}


	private function checkZip(?string $zip): bool
	{
		if (!$zip) {
			return true;
		}
		return preg_match('/[1-9][0-9][0-9][0-9][0-9]/', $zip) === 1;
	}


	private function checkEmail(?string $email): bool
	{
		if (!$email) {
			return true;
		}
		return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
	}
}
