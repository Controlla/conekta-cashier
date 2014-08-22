<?php namespace Dinkbit\ConektaCashier;

use Carbon\Carbon;
use Illuminate\Support\Facades\View;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Process\Process;
use Symfony\Component\HttpFoundation\Response;

class Invoice {

	/**
	 * The billable instance.
	 *
	 * @var \Dinkbit\ConektaCashier\BillableInterface
	 */
	protected $billable;

	/**
	 * The Conekta invoice instance.
	 *
	 * @var object
	 */
	protected $conektaInvoice;

	/**
	 * Create a new invoice instance.
	 *
	 * @param  \Dinkbit\ConektaCashier\BillableInterface  $billable
	 * @param  object
	 * @return void
	 */
	public function __construct(BillableInterface $billable, $invoice)
	{
		$this->billable = $billable;
		$this->conektaInvoice = $invoice;

		$this->files = new Filesystem;
	}

	/**
	 * Get the formatted dollar amount for the invoice.
	 *
	 * @return string
	 */
	public function dollars()
	{
		if (starts_with($total = $this->total(), '-'))
		{
			return '-$'.ltrim($total, '-');
		}
		else
		{
			return '$'.$total;
		}
	}

	/**
	 * Get the total of the invoice (after discounts).
	 *
	 * @return float
	 */
	public function total()
	{
		return number_format($this->total / 100, 2);
	}

	/**
	 * Get the total of the invoice (before discounts).
	 *
	 * @return float
	 */
	public function subtotal()
	{
		return number_format($this->subtotal / 100, 2);
	}

	/**
	 * Get all of the "invoice item" line items.
	 *
	 * @return array
	 */
	public function invoiceItems()
	{
		return $this->lineItemsByType('invoiceitem');
	}

	/**
	 * Get all of the "subscription" line items.
	 *
	 * @return array
	 */
	public function subscriptions()
	{
		return $this->lineItemsByType('subscription');
	}

	/**
	 * Get all of the line items by a given type.
	 *
	 * @param  string  $type
	 * @return array
	 */
	public function lineItemsByType($type)
	{
		$lineItems = [];

		if (isset($this->lines->data))
		{
			foreach ($this->lines->data as $line)
			{
				if ($line->type == $type)
				{
					$lineItems[] = new LineItem($line);
				}
			}
		}

		return $lineItems;
	}

	/**
	 * Determine if the invoice has a discount.
	 *
	 * @return bool
	 */
	public function hasDiscount()
	{
		return $this->total > 0 && $this->subtotal != $this->total;
	}

	/**
	 * Get the discount amount in dollars.
	 *
	 * @return string
	 */
	public function discountDollars()
	{
		return '$'.$this->discount();
	}

	/**
	 * Get the discount amount in dollars.
	 *
	 * @return float
	 */
	public function discount()
	{
		return round(money_format('%i', ($this->subtotal / 100) - ($this->total / 100)), 2);
	}

	/**
	 * Get the coupon code applied to the invoice.
	 *
	 * @return string|null
	 */
	public function coupon()
	{
		if (isset($this->conektaInvoice->discount))
		{
			return $this->discount->coupon->id;
		}
	}

	/**
	 * Determine if the discount is a percentage.
	 *
	 * @return bool
	 */
	public function discountIsPercentage()
	{
		return ! is_null($this->percentOff());
	}

	/**
	 * Get the discount percentage for the invoice.
	 *
	 * @return int|null
	 */
	public function percentOff()
	{
		return $this->discount->coupon->percent_off;
	}

	/**
	 * Get the discount amount for the invoice.
	 *
	 * @return float|null
	 */
	public function amountOff()
	{
		if (isset($this->discount->coupon->amount_off))
		{
			return number_format($this->discount->coupon->amount_off / 100, 2);
		}
	}

	/**
	 * Get a Carbon date for the invoice.
	 *
	 * @param  \DateTimeZone|string  $timezone
	 * @return \Carbon\Carbon
	 */
	public function date($timezone = null)
	{
		$carbon = Carbon::createFromTimestamp($this->date);

		return $timezone ? $carbon->setTimezone($timezone) : $carbon;
	}

	/**
	 * Get a human readable date for the invoice.
	 *
	 * @param  \DateTimeZone|string  $timezone
	 * @return string
	 */
	public function dateString($timezone = null)
	{
		return $this->date()->toDayDateTimeString();
	}

	/**
	 * Get the View instance for the invoice.
	 *
	 * @param  array  $data
	 * @return \Illuminate\View\View
	 */
	public function view(array $data)
	{
		$data = array_merge($data, ['invoice' => $this, 'billable' => $this->billable]);

		return View::make('cashier::receipt', $data);
	}

	/**
	 * Get the rendered HTML content of the invoice view.
	 *
	 * @param  array  $data
	 * @return string
	 */
	public function render(array $data)
	{
		return $this->view($data)->render();
	}

	/**
	 * Create an invoice download response.
	 *
	 * @param  array   $data
	 * @param  string  $storagePath
	 * @return \Symfony\Component\HttpFoundation\Response
	 */
	public function download(array $data, $storagePath = null)
	{
		$filename = $this->getDownloadFilename($data['product']);

		$document = $this->writeInvoice($data, $storagePath);

		$response = new Response($this->files->get($document), 200, [
			'Content-Description' => 'File Transfer',
			'Content-Disposition' => 'attachment; filename="'.$filename.'"',
			'Content-Transfer-Encoding' => 'binary',
			'Content-Type' => 'application/pdf',
		]);

		$this->files->delete($document);

		return $response;
	}

	/**
	 * Write the raw PDF bytes for the invoice via PhantomJS.
	 *
	 * @param  array  $data
	 * @param  string  $storagePath
	 * @return string
	 */
	protected function writeInvoice(array $data, $storagePath)
	{
		// To properly capture a screenshot of the invoice view, we will pipe out to
		// PhantomJS, which is a headless browser. We'll then capture a PNG image
		// of the webpage, which will produce a very faithful copy of the page.
		$viewPath = $this->writeViewForImaging($data, $storagePath);

		$this->getPhantomProcess($viewPath)
							->setTimeout(10)->run();

		return $viewPath;
	}

	/**
	 * Write the view HTML so PhantomJS can access it.
	 *
	 * @param  array  $data
	 * @param  string  $storagePath
	 * @return string
	 */
	protected function writeViewForImaging(array $data, $storagePath)
	{
		$storagePath = $storagePath ?: storage_path().'/meta';

		$this->files->put($path = $storagePath.'/'.md5($this->id).'.pdf', $this->render($data));

		return $path;
	}

	/**
	 * Get the PhantomJS process instance.
	 *
	 * @param  string  $viewPath
	 * @return \Symfony\Component\Process\Process
	 */
	public function getPhantomProcess($viewPath)
	{
		$system = $this->getSystem();

		$phantom = __DIR__.'/bin/'.$system.'/phantomjs'.$this->getExtension($system);

		return new Process($phantom.' invoice.js '.$viewPath, __DIR__);
	}

	/**
	 * Get the filename for the invoice download.
	 *
	 * @param  string  $prefix
	 * @return string
	 */
	protected function getDownloadFilename($prefix)
	{
		$prefix = ! is_null($prefix) ? $prefix.'_' : '';

		return $prefix.$this->date()->month.'_'.$this->date()->year;
	}

	/**
	 * Set the filesystem instance.
	 *
	 * @param  \Illuminate\Filesystem\Filesystem
	 * @return \Dinkbit\ConektaCashier\Invoice
	 */
	public function setFiles(Filesystem $files)
	{
		$this->files = $files;

		return $this;
	}

	/**
	 * Get the Conekta invoice object.
	 *
	 * @return object
	 */
	public function getConektaInvoice()
	{
		return $this->conektaInvoice;
	}

	/**
	 * Get the operating system for the current platform.
	 *
	 * @return string
	 */
	protected function getSystem()
	{
		$uname = strtolower(php_uname());

		if (str_contains($uname, 'darwin'))
		{
			return 'macosx';
		}
		elseif (str_contains($uname, 'win'))
		{
			return 'windows';
		}
		elseif (str_contains($uname, 'linux'))
		{
			return PHP_INT_SIZE === 4 ? 'linux-i686' : 'linux-x86_64';
		}
		else
		{
			throw new \RuntimeException("Unknown operating system.");
		}
	}

	/**
	 * Get the binary extension for the system.
	 *
	 * @param  string  $system
	 * @return string
	 */
	protected function getExtension($system)
	{
		return $system == 'windows' ? '.exe' : '';
	}

	/**
	 * Dynamically get values from the Conekta invoice.
	 *
	 * @param  string  $key
	 * @return mixed
	 */
	public function __get($key)
	{
		return $this->conektaInvoice->{$key};
	}

	/**
	 * Dynamically set values on the Conekta invoice.
	 *
	 * @param  string  $key
	 * @param  mixed   $value
	 * @return void
	 */
	public function __set($key, $value)
	{
		$this->conektaInvoice->{$key} = $value;
	}

}
