<?php

namespace OpenDominion\Tests\Unit\Calculators\Dominion;

use Mockery as m;
use OpenDominion\Calculators\Dominion\BuildingCalculator;
use OpenDominion\Models\Dominion;
use OpenDominion\Tests\BaseTestCase;

class BuildingCalculatorTest extends BaseTestCase
{
    /** @var Dominion */
    protected $dominionMock;

    /** @var BuildingCalculator */
    protected $buildingCalculator;

    protected function setUp()
    {
        parent::setUp();

        $this->dominionMock = m::mock(Dominion::class);

        $this->buildingCalculator = $this->app->make(BuildingCalculator::class)
            ->setDominion($this->dominionMock);
    }

    public function testGetTotalBuildings()
    {
        $buildingTypes = [
            'home',
            'alchemy',
            'farm',
            'lumberyard',
            'barracks',
        ];

        $expected = 0;

        for ($i = 0, $countBuildingTypes = count($buildingTypes); $i < $countBuildingTypes; ++$i) {
            $this->dominionMock->shouldReceive('getAttribute')->with("building_{$buildingTypes[$i]}")->andReturn(1 << $i);
            $expected += (1 << $i);
        }

        $this->assertEquals($expected, $this->buildingCalculator->getTotalBuildings());
    }
}