<?php

namespace Illuminate\Tests\Database;

use Mockery as m;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\Pivot;

class DatabaseEloquentPivotTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testPropertiesAreSetCorrectly()
    {
        $parent = m::mock(Model::class.'[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->twice()->andReturn('connection');
        $parent->getConnection()->getQueryGrammar()->shouldReceive('getDateFormat')->andReturn('Y-m-d H:i:s');
        $parent->setDateFormat('Y-m-d H:i:s');
        $pivot = Pivot::fromAttributes($parent, ['foo' => 'bar', 'created_at' => '2015-09-12'], 'table', true);

        $this->assertEquals(['foo' => 'bar', 'created_at' => '2015-09-12 00:00:00'], $pivot->getAttributes());
        $this->assertEquals('connection', $pivot->getConnectionName());
        $this->assertEquals('table', $pivot->getTable());
        $this->assertTrue($pivot->exists);
    }

    public function testMutatorsAreCalledFromConstructor()
    {
        $parent = m::mock(Model::class.'[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $pivot = DatabaseEloquentPivotTestMutatorStub::fromAttributes($parent, ['foo' => 'bar'], 'table', true);

        $this->assertTrue($pivot->getMutatorCalled());
    }

    public function testFromRawAttributesDoesNotDoubleMutate()
    {
        $parent = m::mock(Model::class.'[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $pivot = DatabaseEloquentPivotTestJsonCastStub::fromRawAttributes($parent, ['foo' => json_encode(['name' => 'Taylor'])], 'table', true);

        $this->assertEquals(['name' => 'Taylor'], $pivot->foo);
    }

    public function testFromRawAttributesDoesNotMutate()
    {
        $parent = m::mock(Model::class.'[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->once()->andReturn('connection');

        $pivot = DatabaseEloquentPivotTestMutatorStub::fromRawAttributes($parent, ['foo' => 'bar'], 'table', true);

        $this->assertFalse($pivot->getMutatorCalled());
    }

    public function testPropertiesUnchangedAreNotDirty()
    {
        $parent = m::mock(Model::class.'[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->once()->andReturn('connection');
        $pivot = Pivot::fromAttributes($parent, ['foo' => 'bar', 'shimy' => 'shake'], 'table', true);

        $this->assertEquals([], $pivot->getDirty());
    }

    public function testPropertiesChangedAreDirty()
    {
        $parent = m::mock(Model::class.'[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->once()->andReturn('connection');
        $pivot = Pivot::fromAttributes($parent, ['foo' => 'bar', 'shimy' => 'shake'], 'table', true);
        $pivot->shimy = 'changed';

        $this->assertEquals(['shimy' => 'changed'], $pivot->getDirty());
    }

    public function testTimestampPropertyIsSetIfCreatedAtInAttributes()
    {
        $parent = m::mock(Model::class.'[getConnectionName,getDates]');
        $parent->shouldReceive('getConnectionName')->andReturn('connection');
        $parent->shouldReceive('getDates')->andReturn([]);
        $pivot = DatabaseEloquentPivotTestDateStub::fromAttributes($parent, ['foo' => 'bar', 'created_at' => 'foo'], 'table');
        $this->assertTrue($pivot->timestamps);

        $pivot = DatabaseEloquentPivotTestDateStub::fromAttributes($parent, ['foo' => 'bar'], 'table');
        $this->assertFalse($pivot->timestamps);
    }

    public function testTimestampPropertyIsTrueWhenCreatingFromRawAttributes()
    {
        $parent = m::mock(Model::class.'[getConnectionName,getDates]');
        $parent->shouldReceive('getConnectionName')->andReturn('connection');
        $pivot = Pivot::fromRawAttributes($parent, ['foo' => 'bar', 'created_at' => 'foo'], 'table');
        $this->assertTrue($pivot->timestamps);
    }

    public function testKeysCanBeSetProperly()
    {
        $parent = m::mock(Model::class.'[getConnectionName]');
        $parent->shouldReceive('getConnectionName')->once()->andReturn('connection');
        $pivot = Pivot::fromAttributes($parent, ['foo' => 'bar'], 'table');
        $pivot->setPivotKeys('foreign', 'other');

        $this->assertEquals('foreign', $pivot->getForeignKey());
        $this->assertEquals('other', $pivot->getOtherKey());
    }

    public function testDeleteMethodDeletesModelByKeys()
    {
        $pivot = $this->getMockBuilder(Pivot::class)->setMethods(['newQueryWithoutRelationships'])->getMock();
        $pivot->setPivotKeys('foreign', 'other');
        $pivot->foreign = 'foreign.value';
        $pivot->other = 'other.value';
        $query = m::mock(stdClass::class);
        $query->shouldReceive('where')->once()->with(['foreign' => 'foreign.value', 'other' => 'other.value'])->andReturn($query);
        $query->shouldReceive('delete')->once()->andReturn(true);
        $pivot->expects($this->once())->method('newQueryWithoutRelationships')->will($this->returnValue($query));

        $rowsAffected = $pivot->delete();
        $this->assertEquals(1, $rowsAffected);
    }

    public function testPivotModelTableNameIsSingular()
    {
        $pivot = new Pivot;

        $this->assertEquals('pivot', $pivot->getTable());
    }

    public function testPivotModelWithParentReturnsParentsTimestampColumns()
    {
        $parent = m::mock(Model::class);
        $parent->shouldReceive('getCreatedAtColumn')->andReturn('parent_created_at');
        $parent->shouldReceive('getUpdatedAtColumn')->andReturn('parent_updated_at');

        $pivotWithParent = new Pivot;
        $pivotWithParent->pivotParent = $parent;

        $this->assertEquals('parent_created_at', $pivotWithParent->getCreatedAtColumn());
        $this->assertEquals('parent_updated_at', $pivotWithParent->getUpdatedAtColumn());
    }

    public function testPivotModelWithoutParentReturnsModelTimestampColumns()
    {
        $model = new DummyModel;

        $pivotWithoutParent = new Pivot;

        $this->assertEquals($model->getCreatedAtColumn(), $pivotWithoutParent->getCreatedAtColumn());
        $this->assertEquals($model->getUpdatedAtColumn(), $pivotWithoutParent->getUpdatedAtColumn());
    }
}

class DatabaseEloquentPivotTestDateStub extends Pivot
{
    public function getDates()
    {
        return [];
    }
}

class DatabaseEloquentPivotTestMutatorStub extends Pivot
{
    private $mutatorCalled = false;

    public function setFooAttribute($value)
    {
        $this->mutatorCalled = true;

        return $value;
    }

    public function getMutatorCalled()
    {
        return $this->mutatorCalled;
    }
}

class DatabaseEloquentPivotTestJsonCastStub extends Pivot
{
    protected $casts = [
        'foo' => 'json',
    ];
}

class DummyModel extends Model
{
    //
}
