<?php

use Controlla\ConektaCashier\LineItem;
use Mockery as m;
use PHPUnit\Framework\TestCase;

class LineItemTest extends TestCase
{
    public function tearDown()
    {
        m::close();
    }

    public function testReceivingDollarTotal()
    {
        $line = new LineItem($billable = m::mock('Controlla\ConektaCashier\Contracts\Billable'), (object) ['amount' => 10000]);
        $billable->shouldReceive('formatCurrency')->andReturn(100.00);
        $this->assertEquals(100.00, $line->total());
    }
}
