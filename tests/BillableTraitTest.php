<?php

use Mockery as m;
use Illuminate\Support\Facades\Config;

class BillableTraitTest extends PHPUnit_Framework_TestCase {

	public function tearDown()
	{
		m::close();
	}


	public function testFindInvoiceOrFailReturnsInvoice()
	{
		$billable = m::mock('BillableTraitTestStub[findInvoice]');
		$billable->shouldReceive('findInvoice')->once()->with('id')->andReturn('foo');

		$this->assertEquals('foo', $billable->findInvoiceOrFail('id'));
	}


	/**
	 * @expectedException Symfony\Component\HttpKernel\Exception\NotFoundHttpException
	 */
	public function testFindInvoiceOrFailsThrowsExceptionWhenNotFound()
	{
		$billable = m::mock('BillableTraitTestStub[findInvoice]');
		$billable->shouldReceive('findInvoice')->once()->with('id')->andReturn(null);

		$billable->findInvoiceOrFail('id');
	}


	public function testDownloadCallsDownloadOnInvoice()
	{
		$billable = m::mock('BillableTraitTestStub[findInvoice]');
		$billable->shouldReceive('findInvoice')->once()->with('id')->andReturn($invoice = m::mock('StdClass'));
		$invoice->shouldReceive('download')->once()->with(['foo']);

		$billable->downloadInvoice('id', ['foo']);
	}


	public function testOnTrialMethodReturnsTrueIfTrialDateGreaterThanCurrentDate()
	{
		$billable = m::mock('BillableTraitTestStub[getTrialEndDate]');
		$billable->shouldReceive('getTrialEndDate')->andReturn(Carbon\Carbon::now()->addDays(5));

		$this->assertTrue($billable->onTrial());
	}


	public function testOnTrialMethodReturnsFalseIfTrialDateLessThanCurrentDate()
	{
		$billable = m::mock('BillableTraitTestStub[getTrialEndDate]');
		$billable->shouldReceive('getTrialEndDate')->andReturn(Carbon\Carbon::now()->subDays(5));

		$this->assertFalse($billable->onTrial());
	}


	public function testSubscribedChecksConektaIsActiveIfCardRequiredUpFront()
	{
		$billable = new BillableTraitCardUpFrontTestStub;
		$billable->conekta_active = true;
		$billable->subscription_ends_at = null;
		$this->assertTrue($billable->subscribed());

		$billable = new BillableTraitCardUpFrontTestStub;
		$billable->conekta_active = false;
		$billable->subscription_ends_at = null;
		$this->assertFalse($billable->subscribed());

		$billable = new BillableTraitCardUpFrontTestStub;
		$billable->conekta_active = false;
		$billable->subscription_ends_at = Carbon\Carbon::now()->addDays(5);
		$this->assertTrue($billable->subscribed());

		$billable = new BillableTraitCardUpFrontTestStub;
		$billable->conekta_active = false;
		$billable->subscription_ends_at = Carbon\Carbon::now()->subDays(5);
		$this->assertFalse($billable->subscribed());
	}


	public function testSubscribedHandlesNoCardUpFront()
	{
		$billable = new BillableTraitTestStub;
		$billable->trial_ends_at = null;
		$billable->conekta_active = null;
		$billable->subscription_ends_at = null;
		$this->assertFalse($billable->subscribed());

		$billable = new BillableTraitTestStub;
		$billable->conekta_active = 0;
		$billable->trial_ends_at = Carbon\Carbon::now()->addDays(5);
		$this->assertTrue($billable->subscribed());

		$billable = new BillableTraitTestStub;
		$billable->conekta_active = true;
		$billable->trial_ends_at = Carbon\Carbon::now()->subDays(5);
		$this->assertTrue($billable->subscribed());

		$billable = new BillableTraitTestStub;
		$billable->conekta_active = false;
		$billable->trial_ends_at = Carbon\Carbon::now()->subDays(5);
		$billable->subscription_ends_at = null;
		$this->assertFalse($billable->subscribed());

		$billable = new BillableTraitTestStub;
		$billable->trial_ends_at = null;
		$billable->conekta_active = null;
		$billable->subscription_ends_at = Carbon\Carbon::now()->addDays(5);
		$this->assertTrue($billable->subscribed());

		$billable = new BillableTraitTestStub;
		$billable->trial_ends_at = null;
		$billable->conekta_active = null;
		$billable->subscription_ends_at = Carbon\Carbon::now()->subDays(5);
		$this->assertFalse($billable->subscribed());
	}


	public function testReadyForBillingChecksConektaReadiness()
	{
		$billable = new BillableTraitTestStub;
		$billable->conekta_id = null;
		$this->assertFalse($billable->readyForBilling());

		$billable = new BillableTraitTestStub;
		$billable->conekta_id = 1;
		$this->assertTrue($billable->readyForBilling());
	}


	public function testGettingConektaKey()
	{
		Config::shouldReceive('get')->once()->with('services.conekta.secret')->andReturn('foo');
		$this->assertEquals('foo', BillableTraitTestStub::getConektaKey());
	}

}

class BillableTraitTestStub implements dinkbit\ConektaCashier\BillableInterface {
	use dinkbit\ConektaCashier\BillableTrait;
	public $cardUpFront = false;
	public function save() {}
}

class BillableTraitCardUpFrontTestStub implements dinkbit\ConektaCashier\BillableInterface {
	use dinkbit\ConektaCashier\BillableTrait;
	public $cardUpFront = true;
	public function save() {}
}
