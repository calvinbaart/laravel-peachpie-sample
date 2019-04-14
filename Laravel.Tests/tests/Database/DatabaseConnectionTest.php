<?php

namespace Illuminate\Tests\Database;

use PDO;
use DateTime;
use Exception;
use Mockery as m;
use PDOException;
use PDOStatement;
use ErrorException;
use ReflectionClass;
use PHPUnit\Framework\TestCase;
use Illuminate\Database\Connection;
use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Builder;
use Illuminate\Contracts\Events\Dispatcher;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Query\Grammars\Grammar;
use Illuminate\Database\Query\Processors\Processor;
use Illuminate\Database\Events\TransactionBeginning;
use Illuminate\Database\Events\TransactionCommitted;
use Illuminate\Database\Events\TransactionRolledBack;
use Illuminate\Database\Query\Builder as BaseBuilder;

class DatabaseConnectionTest extends TestCase
{
    protected function tearDown(): void
    {
        m::close();
    }

    public function testSettingDefaultCallsGetDefaultGrammar()
    {
        $connection = $this->getMockConnection();
        $mock = m::mock(stdClass::class);
        $connection->expects($this->once())->method('getDefaultQueryGrammar')->will($this->returnValue($mock));
        $connection->useDefaultQueryGrammar();
        $this->assertEquals($mock, $connection->getQueryGrammar());
    }

    public function testSettingDefaultCallsGetDefaultPostProcessor()
    {
        $connection = $this->getMockConnection();
        $mock = m::mock(stdClass::class);
        $connection->expects($this->once())->method('getDefaultPostProcessor')->will($this->returnValue($mock));
        $connection->useDefaultPostProcessor();
        $this->assertEquals($mock, $connection->getPostProcessor());
    }

    public function testSelectOneCallsSelectAndReturnsSingleResult()
    {
        $connection = $this->getMockConnection(['select']);
        $connection->expects($this->once())->method('select')->with('foo', ['bar' => 'baz'])->will($this->returnValue(['foo']));
        $this->assertEquals('foo', $connection->selectOne('foo', ['bar' => 'baz']));
    }

    public function testSelectProperlyCallsPDO()
    {
        $pdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->setMethods(['prepare'])->getMock();
        $writePdo->expects($this->never())->method('prepare');
        $statement = $this->getMockBuilder('PDOStatement')->setMethods(['execute', 'fetchAll', 'bindValue'])->getMock();
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('fetchAll')->will($this->returnValue(['boom']));
        $pdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
        $mock = $this->getMockConnection(['prepareBindings'], $writePdo);
        $mock->setReadPdo($pdo);
        $mock->expects($this->once())->method('prepareBindings')->with($this->equalTo(['foo' => 'bar']))->will($this->returnValue(['foo' => 'bar']));
        $results = $mock->select('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
        $log = $mock->getQueryLog();
        $this->assertEquals('foo', $log[0]['query']);
        $this->assertEquals(['foo' => 'bar'], $log[0]['bindings']);
        $this->assertIsNumeric($log[0]['time']);
    }

    public function testInsertCallsTheStatementMethod()
    {
        $connection = $this->getMockConnection(['statement']);
        $connection->expects($this->once())->method('statement')->with($this->equalTo('foo'), $this->equalTo(['bar']))->will($this->returnValue('baz'));
        $results = $connection->insert('foo', ['bar']);
        $this->assertEquals('baz', $results);
    }

    public function testUpdateCallsTheAffectingStatementMethod()
    {
        $connection = $this->getMockConnection(['affectingStatement']);
        $connection->expects($this->once())->method('affectingStatement')->with($this->equalTo('foo'), $this->equalTo(['bar']))->will($this->returnValue('baz'));
        $results = $connection->update('foo', ['bar']);
        $this->assertEquals('baz', $results);
    }

    public function testDeleteCallsTheAffectingStatementMethod()
    {
        $connection = $this->getMockConnection(['affectingStatement']);
        $connection->expects($this->once())->method('affectingStatement')->with($this->equalTo('foo'), $this->equalTo(['bar']))->will($this->returnValue('baz'));
        $results = $connection->delete('foo', ['bar']);
        $this->assertEquals('baz', $results);
    }

    public function testStatementProperlyCallsPDO()
    {
        $pdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder('PDOStatement')->setMethods(['execute', 'bindValue'])->getMock();
        $statement->expects($this->once())->method('bindValue')->with(1, 'bar', 2);
        $statement->expects($this->once())->method('execute')->will($this->returnValue('foo'));
        $pdo->expects($this->once())->method('prepare')->with($this->equalTo('foo'))->will($this->returnValue($statement));
        $mock = $this->getMockConnection(['prepareBindings'], $pdo);
        $mock->expects($this->once())->method('prepareBindings')->with($this->equalTo(['bar']))->will($this->returnValue(['bar']));
        $results = $mock->statement('foo', ['bar']);
        $this->assertEquals('foo', $results);
        $log = $mock->getQueryLog();
        $this->assertEquals('foo', $log[0]['query']);
        $this->assertEquals(['bar'], $log[0]['bindings']);
        $this->assertIsNumeric($log[0]['time']);
    }

    public function testAffectingStatementProperlyCallsPDO()
    {
        $pdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->setMethods(['prepare'])->getMock();
        $statement = $this->getMockBuilder('PDOStatement')->setMethods(['execute', 'rowCount', 'bindValue'])->getMock();
        $statement->expects($this->once())->method('bindValue')->with('foo', 'bar', 2);
        $statement->expects($this->once())->method('execute');
        $statement->expects($this->once())->method('rowCount')->will($this->returnValue(['boom']));
        $pdo->expects($this->once())->method('prepare')->with('foo')->will($this->returnValue($statement));
        $mock = $this->getMockConnection(['prepareBindings'], $pdo);
        $mock->expects($this->once())->method('prepareBindings')->with($this->equalTo(['foo' => 'bar']))->will($this->returnValue(['foo' => 'bar']));
        $results = $mock->update('foo', ['foo' => 'bar']);
        $this->assertEquals(['boom'], $results);
        $log = $mock->getQueryLog();
        $this->assertEquals('foo', $log[0]['query']);
        $this->assertEquals(['foo' => 'bar'], $log[0]['bindings']);
        $this->assertIsNumeric($log[0]['time']);
    }

    public function testTransactionLevelNotIncrementedOnTransactionException()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $pdo->expects($this->once())->method('beginTransaction')->will($this->throwException(new Exception));
        $connection = $this->getMockConnection([], $pdo);
        try {
            $connection->beginTransaction();
        } catch (Exception $e) {
            $this->assertEquals(0, $connection->transactionLevel());
        }
    }

    public function testBeginTransactionMethodRetriesOnFailure()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $pdo->expects($this->exactly(2))->method('beginTransaction');
        $pdo->expects($this->at(0))->method('beginTransaction')->will($this->throwException(new ErrorException('server has gone away')));
        $connection = $this->getMockConnection(['reconnect'], $pdo);
        $connection->expects($this->once())->method('reconnect');
        $connection->beginTransaction();
        $this->assertEquals(1, $connection->transactionLevel());
    }

    public function testBeginTransactionMethodNeverRetriesIfWithinTransaction()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('exec')->will($this->throwException(new Exception));
        $connection = $this->getMockConnection(['reconnect'], $pdo);
        $queryGrammar = $this->createMock(Grammar::class);
        $queryGrammar->expects($this->once())->method('supportsSavepoints')->will($this->returnValue(true));
        $connection->setQueryGrammar($queryGrammar);
        $connection->expects($this->never())->method('reconnect');
        $connection->beginTransaction();
        $this->assertEquals(1, $connection->transactionLevel());
        try {
            $connection->beginTransaction();
        } catch (Exception $e) {
            $this->assertEquals(1, $connection->transactionLevel());
        }
    }

    public function testSwapPDOWithOpenTransactionResetsTransactionLevel()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $pdo->expects($this->once())->method('beginTransaction')->will($this->returnValue(true));
        $connection = $this->getMockConnection([], $pdo);
        $connection->beginTransaction();
        $connection->disconnect();
        $this->assertEquals(0, $connection->transactionLevel());
    }

    public function testBeganTransactionFiresEventsIfSet()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $connection = $this->getMockConnection(['getName'], $pdo);
        $connection->expects($this->any())->method('getName')->will($this->returnValue('name'));
        $connection->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(TransactionBeginning::class));
        $connection->beginTransaction();
    }

    public function testCommittedFiresEventsIfSet()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $connection = $this->getMockConnection(['getName'], $pdo);
        $connection->expects($this->any())->method('getName')->will($this->returnValue('name'));
        $connection->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(TransactionCommitted::class));
        $connection->commit();
    }

    public function testRollBackedFiresEventsIfSet()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $connection = $this->getMockConnection(['getName'], $pdo);
        $connection->expects($this->any())->method('getName')->will($this->returnValue('name'));
        $connection->beginTransaction();
        $connection->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(TransactionRolledBack::class));
        $connection->rollBack();
    }

    public function testRedundantRollBackFiresNoEvent()
    {
        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $connection = $this->getMockConnection(['getName'], $pdo);
        $connection->expects($this->any())->method('getName')->will($this->returnValue('name'));
        $connection->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldNotReceive('dispatch');
        $connection->rollBack();
    }

    public function testTransactionMethodRunsSuccessfully()
    {
        $pdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->setMethods(['beginTransaction', 'commit'])->getMock();
        $mock = $this->getMockConnection([], $pdo);
        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('commit');
        $result = $mock->transaction(function ($db) {
            return $db;
        });
        $this->assertEquals($mock, $result);
    }

    public function testTransactionMethodRetriesOnDeadlock()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('Deadlock found when trying to get lock (SQL: )');

        $pdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->setMethods(['beginTransaction', 'commit', 'rollBack'])->getMock();
        $mock = $this->getMockConnection([], $pdo);
        $pdo->expects($this->exactly(3))->method('beginTransaction');
        $pdo->expects($this->exactly(3))->method('rollBack');
        $pdo->expects($this->never())->method('commit');
        $mock->transaction(function () {
            throw new QueryException('', [], new Exception('Deadlock found when trying to get lock'));
        }, 3);
    }

    public function testTransactionMethodRollsbackAndThrows()
    {
        $pdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->setMethods(['beginTransaction', 'commit', 'rollBack'])->getMock();
        $mock = $this->getMockConnection([], $pdo);
        $pdo->expects($this->once())->method('beginTransaction');
        $pdo->expects($this->once())->method('rollBack');
        $pdo->expects($this->never())->method('commit');
        try {
            $mock->transaction(function () {
                throw new Exception('foo');
            });
        } catch (Exception $e) {
            $this->assertEquals('foo', $e->getMessage());
        }
    }

    public function testOnLostConnectionPDOIsNotSwappedWithinATransaction()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('server has gone away (SQL: foo)');

        $pdo = m::mock(PDO::class);
        $pdo->shouldReceive('beginTransaction')->once();
        $statement = m::mock(PDOStatement::class);
        $pdo->shouldReceive('prepare')->once()->andReturn($statement);
        $statement->shouldReceive('execute')->once()->andThrow(new PDOException('server has gone away'));

        $connection = new Connection($pdo);
        $connection->beginTransaction();
        $connection->statement('foo');
    }

    public function testOnLostConnectionPDOIsSwappedOutsideTransaction()
    {
        $pdo = m::mock(PDO::class);

        $statement = m::mock(PDOStatement::class);
        $statement->shouldReceive('execute')->once()->andThrow(new PDOException('server has gone away'));
        $statement->shouldReceive('execute')->once()->andReturn('result');

        $pdo->shouldReceive('prepare')->twice()->andReturn($statement);

        $connection = new Connection($pdo);

        $called = false;

        $connection->setReconnector(function ($connection) use (&$called) {
            $called = true;
        });

        $this->assertEquals('result', $connection->statement('foo'));

        $this->assertTrue($called);
    }

    public function testRunMethodRetriesOnFailure()
    {
        $method = (new ReflectionClass(Connection::class))->getMethod('run');
        $method->setAccessible(true);

        $pdo = $this->createMock(DatabaseConnectionTestMockPDO::class);
        $mock = $this->getMockConnection(['tryAgainIfCausedByLostConnection'], $pdo);
        $mock->expects($this->once())->method('tryAgainIfCausedByLostConnection');

        $method->invokeArgs($mock, ['', [], function () {
            throw new QueryException('', [], new Exception);
        }]);
    }

    public function testRunMethodNeverRetriesIfWithinTransaction()
    {
        $this->expectException(QueryException::class);
        $this->expectExceptionMessage('(SQL: ) (SQL: )');

        $method = (new ReflectionClass(Connection::class))->getMethod('run');
        $method->setAccessible(true);

        $pdo = $this->getMockBuilder(DatabaseConnectionTestMockPDO::class)->setMethods(['beginTransaction'])->getMock();
        $mock = $this->getMockConnection(['tryAgainIfCausedByLostConnection'], $pdo);
        $pdo->expects($this->once())->method('beginTransaction');
        $mock->expects($this->never())->method('tryAgainIfCausedByLostConnection');
        $mock->beginTransaction();

        $method->invokeArgs($mock, ['', [], function () {
            throw new QueryException('', [], new Exception);
        }]);
    }

    public function testFromCreatesNewQueryBuilder()
    {
        $conn = $this->getMockConnection();
        $conn->setQueryGrammar(m::mock(Grammar::class));
        $conn->setPostProcessor(m::mock(Processor::class));
        $builder = $conn->table('users');
        $this->assertInstanceOf(BaseBuilder::class, $builder);
        $this->assertEquals('users', $builder->from);
    }

    public function testPrepareBindings()
    {
        $date = m::mock(DateTime::class);
        $date->shouldReceive('format')->once()->with('foo')->andReturn('bar');
        $bindings = ['test' => $date];
        $conn = $this->getMockConnection();
        $grammar = m::mock(Grammar::class);
        $grammar->shouldReceive('getDateFormat')->once()->andReturn('foo');
        $conn->setQueryGrammar($grammar);
        $result = $conn->prepareBindings($bindings);
        $this->assertEquals(['test' => 'bar'], $result);
    }

    public function testLogQueryFiresEventsIfSet()
    {
        $connection = $this->getMockConnection();
        $connection->logQuery('foo', [], time());
        $connection->setEventDispatcher($events = m::mock(Dispatcher::class));
        $events->shouldReceive('dispatch')->once()->with(m::type(QueryExecuted::class));
        $connection->logQuery('foo', [], null);
    }

    public function testPretendOnlyLogsQueries()
    {
        $connection = $this->getMockConnection();
        $queries = $connection->pretend(function ($connection) {
            $connection->select('foo bar', ['baz']);
        });
        $this->assertEquals('foo bar', $queries[0]['query']);
        $this->assertEquals(['baz'], $queries[0]['bindings']);
    }

    public function testSchemaBuilderCanBeCreated()
    {
        $connection = $this->getMockConnection();
        $schema = $connection->getSchemaBuilder();
        $this->assertInstanceOf(Builder::class, $schema);
        $this->assertSame($connection, $schema->getConnection());
    }

    protected function getMockConnection($methods = [], $pdo = null)
    {
        $pdo = $pdo ?: new DatabaseConnectionTestMockPDO;
        $defaults = ['getDefaultQueryGrammar', 'getDefaultPostProcessor', 'getDefaultSchemaGrammar'];
        $connection = $this->getMockBuilder(Connection::class)->setMethods(array_merge($defaults, $methods))->setConstructorArgs([$pdo])->getMock();
        $connection->enableQueryLog();

        return $connection;
    }
}

class DatabaseConnectionTestMockPDO extends PDO
{
    public function __construct()
    {
        //
    }
}
