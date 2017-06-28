<?php

namespace PremekKoch\Elms;

use Nette\SmartObject;
use Nette\Utils\DateTime;


class ElmsService
{
	use SmartObject;
	const CURRENCY_CZK = 1;
	const CURRENCY_EUR = 2;
	const PLU_ROUNDING = 'zaokrouhleni';
	const PLU_DISCOUNT = 'sleva';
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

	/** @var array */
	private $products = [];

	/** @var ElmsCustomer */
	public $customer;

	/** @var float */
	private $total = 0;

	/** @var bool */
	private $cashOnDelivery = false;

	/** @var int */
	private $currency = self::CURRENCY_CZK;

	/** @var string */
	private $orderNumber;

	/** @var string */
	private $source;

	/** @var string */
	private $invoiceNumber;


	public function __construct($orderSourceCode)
	{
		$this->source = $orderSourceCode;
		$this->customer = new ElmsCustomer();
	}


	public function createOrder(string $orderNumber, string $invoiceNumber, bool $cashOnDelivery = false, int $currency = self::CURRENCY_CZK): void
	{
		$this->orderNumber = $orderNumber;
		$this->invoiceNumber = $invoiceNumber;
		$this->cashOnDelivery = $cashOnDelivery;
		$this->currency = $currency;

		if ($currency !== self::CURRENCY_CZK && $currency !== self::CURRENCY_EUR) {
			throw new ElmsException('Unknown currency code.');
		}
		if (!$this->source) {
			throw new ElmsException('Order source code is not set in extension`s config.');
		}
		if (!function_exists('curl_init')) {
			throw new ElmsException('CURL PHP extension required.');
		}
		if (!function_exists('json_decode')) {
			throw new ElmsException('JSON PHP extension required.');
		}
	}


	public function sendOrder(): void
	{
		$data = $this->exportData();
		$data = json_encode($data);
		$data = base64_encode($data);
		$this->send($data);
	}


	public function addProduct(string $plu, float $price, float $amount, float $vat): void
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


	public function addDiscount(float $price, float $amount, float $vat): void
	{
		if ($price * $amount > 0) {
			throw new ElmsException('Price or ammount must be negative for discount.');
		}
		$this->addProduct(self::PLU_DISCOUNT, $price, $amount, $vat);
	}


	public function addRounding($value): void
	{
		if (abs($value) > 0.99) {
			throw new ElmsException('Rounding value can be between -0.99 to 0.99 only.');
		}
		$this->addProduct(self::PLU_ROUNDING, $value, 1, 0);
	}


	public function getTotal(): float
	{
		$this->calculateTotal();
		return $this->total;
	}


	private function calculateTotal(): void
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
	}


	private function exportData(): array
	{
		$this->checkDelivery();
		$orderDate = new DateTime();
		return [
			'source' => $this->source,
			'order_number' => $this->orderNumber,
			'ordet_date' => $orderDate->format('Y-m-d H-i-s'),
			'invoice_number' => $this->invoiceNumber,
			'currency_id' => $this->currency,
			'cod' => $this->cashOnDelivery,
			'total' => $this->getTotal(),
			'products' => $this->products,
			'customer' => $this->customer->getCustomer(),
		];
	}


	private function checkDelivery(): void
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


	private function send($data): void
	{
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
		//	curl_setopt($c, CURLOPT_USERAGENT, );

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
