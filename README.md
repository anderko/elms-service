PremekKoch\ElmsService
=========================

Elms Servis API communication service. It serves to create an order for sending the stored goods to the end customer.

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

### 2. Set-up "orderSourceCode" in extension configuration in `config.neon`

```
elms:
	orderSourceCode: 'yourClientCodeFromElmsServis'
```

How to use
----------

### 1. Inject an service into presenter or somewhere you need

```
	/** @var PremekKoch\Elms\ElmsService @inject */
	public $elmsService;
```

### 2. Set a customer and order data

minimum you must set is:

```
	:
	$this->elmsService->customer->createCustomer('Přemek', 'Koch', 'Ulice', 'Město', '12345', ElmsCustomer::COUNTRY_CZE);
	$this->elmsService->createOrder('ORD_123', 'INV_123', false, ElmsService::CURRENCY_CZK);
	$this->elmsService->addProduct('plu', 1234.56, 1, 21);
	$this->elmsService->addProduct('cpostrr', 55, 1, 21);
	$this->elmsService->sendOrder();
	:
```

you can set another customer properties:
 
```
	:
	$this->elmsService->customer->setCompany('Firma', '123456789', 'CZ123456789'); 
	$this->elmsService->customer->setContact('premek.koch@gmail.com', '+420777888999');
	$this->elmsService->customer->setDeliveryAddress('Jana', 'Nováková', 'Jiná ulice', 'Jiné město', '98765', ElmsCustomer::COUNTRY_SVK);
	$this->elmsService->customer->setDeliveryCompany('Jiná firma');
	$this->elmsService->customer->setDeliveryContact('novakova@jinafirma.sk', '+421333444555');
	:
```

and you can more specify an order items:

```
	:
	// Discount 100 CZK (value or ammount must be negative)
  $this->elmsService->addDiscount(-100, 1, 21);
  
  // Rounding (-0.99 to +0.99)
	$this->elmsService->addRounding(-0.56);
```
**Remember: when you use CZK and cashOnDelivery, you must round an order to integers;** 


### 3. Send an order
```
	:
	$this->elmsService->sendOrder();
	:
	catch (PremekKoch\Elms\ElmsException $exc){
    // something goes wrong... 			
  }
```
When send fails, API returns only brief error. Email with whole request and problems descriptions should arrive from ELMS Sevis soon.

