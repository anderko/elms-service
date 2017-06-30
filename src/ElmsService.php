<?php

namespace PremekKoch\Elms;

use Nette\SmartObject;
use Nette\Utils\DateTime;


class ElmsService
{
	use SmartObject;
	const COUNTRY_CZE = 'CZ';
	const COUNTRY_SVK = 'SK';
	const CURRENCY_CZK = 1;
	const CURRENCY_EUR = 2;
	const PLU_ROUNDING = 'zaokrouhleni';
	const PLU_DISCOUNT = 'sleva';
	const DELIVERY_CPOST = 'cpost';                 // doprava Českou poštou (Balík do ruky)
	const DELIVERY_CPOSTRR = 'cpostrr';             // doprava Českou poštou zásilka RR (Doporučené psaní do výšky 5cm)
	const DELIVERY_SPOST = 'spost';                 // doprava Slovenskou poštou
	const DELIVERY_GLSCZ = 'glscz';                 // doprava GLS Česko
	const DELIVERY_GLSSK = 'glssk';                 // doprava GLS Slovensko
	const DELIVERY_PPLCZ = 'pplcz';                 // doprava PPL CZ (není aktivní)
	const DELIVERY_DPD = 'dpd';                     // doprava DPD Classic CZ s možností platit dobírku kartou
	const DELIVERY_DPDNC = 'dpdnocard';             // doprava DPD Classic CZ bez možnosti platit dobírku kartou
	const DELIVERY_DPDP = 'dpdprivate';             // doprava DPD Private CZ s možností platit dobírku kartou
	const DELIVERY_DPDPNC = 'dpdprivatenocard';     // doprava DPD Private CZ bez možnosti platit dobírku kartou
	const DELIVERY_ZASILKOVNA = 'zasilkovna';       // Zásilkovna s dodáním na místo s ID xxx
	const DELIVERY_ZASILKOMAT = 'zasilkomat';       // Zásilkomat s dodáním na místo s ID xxx (není aktivní)
	const DELIVERY_PERSONAL = 'osobni_odber_elms';  // Osobní odběr v ELMS
	const ELMS_SERVICE_URL = 'http://fulfillment.elmsservice.cz/orders/import';

	private $deliveryCodes = [
		'cpost',                    // doprava Českou poštou (Balík do ruky)
		'cpostrr',                  // doprava Českou poštou zásilka RR (Doporučené psaní do výšky 5cm)
		'spost',                    // doprava Slovenskou poštou
		'glscz',                    // doprava GLS Česko
		'glssk',                    // doprava GLS Slovensko
		'dpd',                      // doprava DPD Classic CZ s možností platit dobírku kartou
		'dpdnocard',                // doprava DPD Classic CZ bez možnosti platit dobírku kartou
		'dpdprivate',               // doprava DPD Private CZ s možností platit dobírku kartou
		'dpdprivatenocard',         // doprava DPD Private CZ bez možnosti platit dobírku kartou
		'osobni_odber_elms',        // Osobní odběr v ELMS
		//	'pplcz',                    // doprava PPL CZ (není aktivní)
		//	'zasilkovna',               // Zásilkovna s dodáním na místo s ID xxx
		//	'zasilkomat',               // Zásilkomat s dodáním na místo s ID xxx (není aktivní)
	];

	/** @var string */
	private $source;

	/** @var bool */
	private $debugMode;

	/** @var string */
	private $orderNumber;

	/** @var string */
	private $invoiceNumber;

	/** @var int */
	private $currency = self::CURRENCY_CZK;

	/** @var bool */
	private $cashOnDelivery = false;

	/** @var float */
	private $total = 0;

	/** @var array */
	private $products = [];

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


	/**
	 * ElmsService constructor.
	 *
	 * @param $orderSourceCode
	 * @param $debugMode
	 * @throws ElmsException
	 */
	public function __construct($orderSourceCode, $debugMode)
	{
		$this->source = $orderSourceCode;
		$this->debugMode = $debugMode;

		if (!function_exists('curl_init')) {
			throw new ElmsException('CURL PHP extension required.');
		}
		if (!function_exists('json_decode')) {
			throw new ElmsException('JSON PHP extension required.');
		}
	}


	/**
	 * @param string $orderNumber
	 * @param string $invoiceNumber
	 * @param bool   $cashOnDelivery
	 * @param int    $currency
	 * @throws ElmsException
	 */
	public function createOrder(string $orderNumber, string $invoiceNumber, bool $cashOnDelivery = false, int $currency = self::CURRENCY_CZK)
	{
		$this->orderNumber = $orderNumber;
		$this->invoiceNumber = $invoiceNumber;
		$this->cashOnDelivery = $cashOnDelivery;
		$this->currency = $currency;

		if (!is_numeric($orderNumber)) {
			throw new ElmsException('Order number should be numeric.');
		}

		if ($currency !== self::CURRENCY_CZK && $currency !== self::CURRENCY_EUR) {
			throw new ElmsException('Unknown currency code.');
		}
		if (!$this->source) {
			throw new ElmsException('Order source code is not set in extension`s config.');
		}
	}


	/**
	 * @param string $name
	 * @param string $surname
	 * @param string $street
	 * @param string $city
	 * @param string $zip
	 * @param string $country
	 * @throws ElmsException
	 */
	public function addCustomer(string $name, string $surname, string $street, string $city, string $zip, string $country)
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
	}


	/**
	 * @param string $plu
	 * @param float  $price
	 * @param float  $amount
	 * @param float  $vat
	 * @throws ElmsException
	 */
	public function addProduct(string $plu, float $price, float $amount, float $vat)
	{
		if (abs($price - round($price, 2)) > 0) {
			throw new ElmsException('Wrong price precision. Only two decimals allowed.');
		}

		$this->products[] = [
			'product' => $plu,
			'price' => $price,
			'pcs' => $amount,
			'tax' => $vat,
		];
	}


	/**
	 * @param array $products
	 * @throws ElmsException
	 */
	public function addProducts(array $products)
	{
		foreach ($products as $product) {
			$keys = array_keys($product);
			if (!empty(array_diff(['plu', 'price', 'amount', 'vat'], $keys))) {
				throw new ElmsException('Wrong product structure.');
			}

			$this->addProduct($product['plu'], $product['price'], $product['amount'], $product['vat']);
		}
	}


	/**
	 * @param float $price
	 * @param float $amount
	 * @param float $vat
	 * @throws ElmsException
	 */
	public function addDiscount(float $price, float $amount, float $vat)
	{
		if ($price * $amount > 0) {
			throw new ElmsException('Price or ammount must be negative for discount.');
		}
		$this->addProduct(self::PLU_DISCOUNT, $price, $amount, $vat);
	}


	/**
	 * @param float $value
	 * @throws ElmsException
	 */
	public function addRounding(float $value)
	{
		if (abs($value) > 0.99) {
			throw new ElmsException('Rounding value can be between -0.99 to 0.99 only.');
		}
		$this->addProduct(self::PLU_ROUNDING, $value, 1, 0);
	}


	/**
	 * @param string      $company
	 * @param string      $ic
	 * @param string|null $dic
	 */
	public function setCustomerCompany(string $company, string $ic, string $dic = null)
	{
		$this->company = $company;
		$this->ic = $ic;
		$this->dic = $dic;
	}


	/**
	 * @param string|null $email
	 * @param string|null $phone
	 * @throws ElmsException
	 */
	public function setCustomerContact(string $email = null, string $phone = null)
	{
		$this->email = $email;
		$this->phone = $phone;

		if (!$this->checkEmail($email)) {
			throw new ElmsException('Invalid email.');
		}
	}


	/**
	 * @param string $deliveryName
	 * @param string $deliverySurname
	 * @param string $deliveryStreet
	 * @param string $deliveryCity
	 * @param string $deliveryZip
	 * @param string $deliveryCountry
	 * @throws ElmsException
	 */
	public function setCustomerDeliveryAddress(string $deliveryName, string $deliverySurname, string $deliveryStreet, string $deliveryCity, string $deliveryZip, string $deliveryCountry)
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


	/**
	 * @param string $deliveryCompany
	 */
	public function setCustomerDeliveryCompany(string $deliveryCompany)
	{
		$this->deliveryCompany = $deliveryCompany;
	}


	/**
	 * @param string|null $deliveryEmail
	 * @param string|null $deliveryPhone
	 * @throws ElmsException
	 */
	public function setCustomerDeliveryContact(string $deliveryEmail = null, string $deliveryPhone = null)
	{
		$this->deliveryEmail = $deliveryEmail;
		$this->deliveryPhone = $deliveryPhone;

		if (!$this->checkEmail($deliveryEmail)) {
			throw new ElmsException('Invalid delivery email.');
		}
	}


	/**
	 * @return float
	 */
	public function getTotal()
	{
		$this->calculateTotal();
		return $this->total;
	}


	/**
	 *
	 */
	public function sendOrder()
	{
		$data = $this->exportData();
		$data = json_encode($data);
		$data = base64_encode($data);
		$this->send($data);
	}


	/**
	 * @throws ElmsException
	 */
	private function calculateTotal()
	{
		if (empty($this->products)) {
			throw new ElmsException('No products defined. Add some product first.');
		}
		$productSum = 0;
		foreach ($this->products as $product) {
			$productSum += $product['price'];
		}

		if ($this->cashOnDelivery && $this->currency === self::CURRENCY_CZK) {
			$total = round($productSum, 0);
		} else {
			$total = round($productSum, 2);
		}

		if ($total !== $productSum) {
			throw new ElmsException('Unexpected total value. Add rounding item.');
		}

		$this->total = $total;
	}


	/**
	 * @return array
	 */
	private function exportData()
	{
		$this->checkDelivery();
		$orderDate = new DateTime();
		return [
			'source' => $this->source,
			'order_number' => $this->orderNumber,
			'order_date' => $orderDate->format('Y-m-d H-i-s'),
			'invoice_number' => $this->invoiceNumber,
			'currency_id' => $this->currency,
			'cod' => $this->cashOnDelivery,
			'total' => $this->getTotal(),
			'products' => $this->products,
			'customer' => $this->getCustomer(),
		];
	}


	/**
	 * @throws ElmsException
	 */
	private function checkDelivery()
	{
		$deliveryExists = false;
		foreach ($this->products as $product) {
			if (in_array($product['product'], $this->deliveryCodes, true)) {
				$deliveryExists = true;
				break;
			}
		}

		if (!$deliveryExists) {
			throw new ElmsException('No delivery set. Add delivery product item.');
		}
	}


	/**
	 * @return array
	 * @throws ElmsException
	 */
	private function getCustomer()
	{
		if (!$this->name || !$this->surname || !$this->street || !$this->city || !$this->country || !$this->zip) {
			throw new ElmsException('Customer is not set properly.');
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


	/**
	 * @param string|null $country
	 * @return bool
	 */
	private function checkCountry($country)
	{
		return $country === self::COUNTRY_CZE || $country === self::COUNTRY_SVK;
	}


	/**
	 * @param string|null $zip
	 * @return bool
	 */
	private function checkZip($zip)
	{
		if (!$zip) {
			return true;
		}
		return preg_match('/[1-9][0-9][0-9][0-9][0-9]/', $zip) === 1;
	}


	/**
	 * @param string|null $email
	 * @return bool
	 */
	private function checkEmail($email)
	{
		if (!$email) {
			return true;
		}
		return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
	}


	/**
	 * @param array $data
	 * @throws ElmsException
	 */
	private function send(string $data)
	{
		if ($this->debugMode) {
			return; //No data send in debug mode...
		}

		$curl = curl_init();
		if ($curl === false) {
			throw new ElmsException('cURL failed to initialize.');
		}
		$url = self::ELMS_SERVICE_URL . '?data=' . $data;

		curl_setopt($curl, CURLOPT_URL, $url);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($curl, CURLOPT_FAILONERROR, false); // to get error messages in response body
		curl_setopt($curl, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
		curl_setopt($curl, CURLOPT_SSL_VERIFYHOST, 2);
		curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

		$response = curl_exec($curl);
		$info = curl_getinfo($curl);

		if ($response !== 'OK') {
			throw new ElmsException(sprintf('cURL failed with error #%d: %s', curl_errno($curl), $response), curl_errno($curl));
		}

		curl_close($curl);

		if ($info['http_code'] >= 400) {
			throw new ElmsException($response, $info['http_code']);
		}
	}
}
