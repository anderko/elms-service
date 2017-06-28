PremekKoch\ElmsService
=========================

ELMS Servis API communication service. It serves to create an order for sending the stored goods to the end customer.

Instalation
-----------

```
composer require premekkoch/elms-service
```

Configuration
-------------
### 1. Register new extension in `config.neon`
```
extensions:
	elms: PremekKoch\Elms\ElmsExtension
```

### 2. Set-up extension in `config.neon`

```
elms:
	orderSourceCode: 'yourClientCodeFromElmsServis' 
	debugMode: false  # in debug mode no data are send
```

How to use
----------

### 1. Inject an service into presenter or somewhere you need

```
	/** @var PremekKoch\Elms\ElmsService @inject */
	public $elmsService;
```

### 2. Set a customer and order data

Start with creating order and customer basic data:

```
	:
	$this->elmsService->createOrder('123', 'INV_123', false, ElmsService::CURRENCY_CZK);
	$this->elmsService->addCustomer('Jan', 'Novák', 'Dlouhá 5', 'Dlouhá Lhota', '12345', ElmsService::COUNTRY_CZE);
	:
```

You can set another customer properties:
 
```
	:
	$this->elmsService->setCustomerCompany('Firma s.r.o.', '123456789', 'CZ123456789');
	$this->elmsService->setCustomerContact('novak@firma.cz', '+420777666555');
	:
```

or even diferent delivery address. If delivery address is not set, shipping will be provided to customer`s address.

```
	:
	$this->elmsService->setCustomerDeliveryAddress('Jana', 'Nováková', 'Krátká 8', 'Krátká Lhota', '54321', ElmsService::COUNTRY_SVK);
	$this->elmsService->setCustomerDeliveryCompany('Jana Nováková OSVČ');
	$this->elmsService->setCustomerDeliveryContact('novakova@novakova.sk', '+421777888999');
	:
```


Next you must specify products to delivery. You can add more products, minimaly you must set one product and shipping:

```
	:
	$this->elmsService->addProduct('Product PLU', 1234.56, 1, 21);
	$this->elmsService->addProduct(ElmsService::DELIVERY_CPOSTRR, 55, 1, 21);
	:	
```

Alternatively, you can set products in bulk:
```
	:
	$this->elmsService->addProducts([
		[
			'plu' => 'Product PLU',
		  'price' => 1234.56,
		  'amount' => 1,
		  'vat' => 21,
		],
		[
		  'plu' => ElmsService::DELIVERY_CPOSTRR,
		  'price' => 55,
		  'amount' => 1,
		  'vat' => 21,
	  ],
	]);
	:
```

You can add special product items:

```
	:
	// Discount 100 CZK (value or ammount must be negative)
	$this->elmsService->addDiscount(-100, 1, 21);
	:
	// Rounding (-0.99 to +0.99)
	$this->elmsService->addRounding(-0.56);
	:
```
**Remember:** when you use CZK and cashOnDelivery, you must round an order to integers. In order cases (EURs or not cashOnDelivery) use two decimals maximally. 


### 3. Send an order
```
	:
	$this->elmsService->sendOrder();
	:
	catch (PremekKoch\Elms\ElmsException $exc){
		// something goes wrong... 			
	}
```
When send fails, API returns only brief error. Email with whole request and problems descriptions should arrive from ELMS Servis soon - they checks every failed request manually.

