<?php

namespace Minz\Migration;

use Minz\Errors;
use PHPUnit\Framework\TestCase;

class MigratorTest extends TestCase
{
    public function testAddMigration(): void
    {
        $migrator = new Migrator();

        $migrator->addMigration('foo', function () {
            return true;
        });

        $migrations = $migrator->migrations();
        $this->assertArrayHasKey('foo', $migrations);
        $result = $migrations['foo']['migration']();
        $this->assertTrue($result);
    }

    public function testAddMigrationAcceptsAnOptionalRollbackFunction(): void
    {
        $migrator = new Migrator();

        $migrator->addMigration('foo', function () {
            return 'returned by migration';
        }, function () {
            return 'returned by rollback';
        });

        $migrations = $migrator->migrations();
        $this->assertArrayHasKey('foo', $migrations);
        $result = $migrations['foo']['migration']();
        $this->assertSame('returned by migration', $result);
        /** @var callable $rollback */
        $rollback = $migrations['foo']['rollback'];
        $result = $rollback();
        $this->assertSame('returned by rollback', $result);
    }

    public function testMigrationsIsSorted(): void
    {
        $migrator = new Migrator();
        $migrator->addMigration('2_foo', function () {
            return true;
        });
        $migrator->addMigration('10_foo', function () {
            return true;
        });
        $migrator->addMigration('1_foo', function () {
            return true;
        });
        $expected_names = ['1_foo', '2_foo', '10_foo'];

        $migrations = $migrator->migrations();

        $this->assertSame($expected_names, array_keys($migrations));
    }

    public function testSetVersion(): void
    {
        $migrator = new Migrator();
        $migrator->addMigration('foo', function () {
            return true;
        });

        $migrator->setVersion('foo');

        $this->assertSame('foo', $migrator->version());
    }

    public function testSetVersionTrimArgument(): void
    {
        $migrator = new Migrator();
        $migrator->addMigration('foo', function () {
            return true;
        });

        $migrator->setVersion("foo\n");

        $this->assertSame('foo', $migrator->version());
    }

    public function testSetVersionFailsIfMigrationDoesNotExist(): void
    {
        $this->expectException(Errors\MigrationError::class);
        $this->expectExceptionMessage('foo migration does not exist.');

        $migrator = new Migrator();

        $migrator->setVersion('foo');
    }

    public function testMigrate(): void
    {
        $migrator = new Migrator();
        $spy = false;
        $migrator->addMigration('foo', function () use (&$spy) {
            $spy = true;
            return true;
        });
        $this->assertNull($migrator->version());

        $result = $migrator->migrate();

        $this->assertTrue($spy);
        $this->assertSame('foo', $migrator->version());
        $this->assertSame([
            'foo' => true,
        ], $result);
    }

    public function testMigrateCallsMigrationsInSortedOrder(): void
    {
        $migrator = new Migrator();
        $migrator->addMigration('2_foo', function () {
            return true;
        });
        $migrator->addMigration('1_foo', function () {
            return true;
        });

        $result = $migrator->migrate();

        $this->assertSame('2_foo', $migrator->version());
        $this->assertSame([
            '1_foo' => true,
            '2_foo' => true,
        ], $result);
    }

    public function testMigrateDoesNotCallAppliedMigrations(): void
    {
        $migrator = new Migrator();
        $spy = false;
        $migrator->addMigration('1_foo', function () use (&$spy) {
            $spy = true;
            return true;
        });
        $migrator->setVersion('1_foo');

        $result = $migrator->migrate();

        $this->assertFalse($spy);
        $this->assertSame([], $result);
    }

    public function testMigrateWithMigrationReturningFalseDoesNotChangeVersion(): void
    {
        $migrator = new Migrator();
        $migrator->addMigration('1_foo', function () {
            return true;
        });
        $migrator->addMigration('2_foo', function () {
            return false;
        });

        $result = $migrator->migrate();

        $this->assertSame('1_foo', $migrator->version());
        $this->assertSame([
            '1_foo' => true,
            '2_foo' => false,
        ], $result);
    }

    public function testMigrateWithMigrationReturningFalseDoesNotExecuteNextMigrations(): void
    {
        $migrator = new Migrator();
        $migrator->addMigration('1_foo', function () {
            return false;
        });
        $spy = false;
        $migrator->addMigration('2_foo', function () use (&$spy) {
            $spy = true;
            return true;
        });

        $result = $migrator->migrate();

        $this->assertNull($migrator->version());
        $this->assertFalse($spy);
        $this->assertSame([
            '1_foo' => false,
        ], $result);
    }

    public function testMigrateWithFailingMigration(): void
    {
        $migrator = new Migrator();
        $migrator->addMigration('foo', function () {
            throw new \Exception('Oops, it failed.');
        });

        $result = $migrator->migrate();

        $this->assertNull($migrator->version());
        $this->assertSame([
            'foo' => 'Oops, it failed.',
        ], $result);
    }

    public function testRollback(): void
    {
        $migrator = new Migrator();
        $spy = false;
        $migrator->addMigration('foo', function () {
            return true;
        }, function () use (&$spy) {
            $spy = true;
            return true;
        });
        $migrator->migrate();

        $this->assertSame('foo', $migrator->version());

        $result = $migrator->rollback(1);

        $this->assertTrue($spy);
        $this->assertNull($migrator->version());
        $this->assertSame([
            'foo' => true,
        ], $result);
    }

    public function testRollbackStopsAfterMaxSteps(): void
    {
        $migrator = new Migrator();
        $spy = '';
        $migrator->addMigration('foo1', function () {
            return true;
        }, function () use (&$spy) {
            $spy = 'foo1';
            return true;
        });
        $migrator->addMigration('foo2', function () {
            return true;
        }, function () use (&$spy) {
            $spy = 'foo2';
            return true;
        });
        $migrator->addMigration('foo3', function () {
            return true;
        }, function () use (&$spy) {
            $spy = 'foo3';
            return true;
        });
        $migrator->migrate();

        $result = $migrator->rollback(2);

        $this->assertSame('foo2', $spy);
        $this->assertSame('foo1', $migrator->version());
        $this->assertSame([
            'foo3' => true,
            'foo2' => true,
        ], $result);
    }

    public function testRollbackStartsFromCurrentVersion(): void
    {
        $migrator = new Migrator();
        $spy = false;
        $migrator->addMigration('foo1', function () {
            return true;
        }, function () use (&$spy) {
            $spy = 'foo1';
            return true;
        });
        $migrator->addMigration('foo2', function () {
            return true;
        }, function () use (&$spy) {
            $spy = 'foo2';
            return true;
        });
        $migrator->setVersion('foo1');

        $result = $migrator->rollback(1);

        $this->assertSame('foo1', $spy);
        $this->assertNull($migrator->version());
        $this->assertSame([
            'foo1' => true,
        ], $result);
    }

    public function testRollbackDoesNothingIfVersionIsNull(): void
    {
        $migrator = new Migrator();
        $spy = false;
        $migrator->addMigration('foo', function () {
            return true;
        }, function () use (&$spy) {
            $spy = true;
            return true;
        });

        $this->assertNull($migrator->version());

        $result = $migrator->rollback(1);

        $this->assertFalse($spy);
        $this->assertNull($migrator->version());
        $this->assertSame([], $result);
    }

    public function testRollbackWithCallbackReturningFalseDoesNotExecuteNextRollback(): void
    {
        $migrator = new Migrator();
        $migrator->addMigration('foo1', function () {
            return true;
        }, function () use (&$spy) {
            $spy = 'foo1';
            return true;
        });
        $migrator->addMigration('foo2', function () {
            return true;
        }, function () use (&$spy) {
            $spy = 'foo2';
            return false;
        });
        $migrator->migrate();

        $result = $migrator->rollback(2);

        $this->assertSame('foo2', $spy);
        $this->assertSame('foo2', $migrator->version());
        $this->assertSame([
            'foo2' => false,
        ], $result);
    }

    public function testRollbackWithFailingCallback(): void
    {
        $migrator = new Migrator();
        $migrator->addMigration('foo', function () {
            return true;
        }, function () {
            throw new \Exception('Oops, it failed.');
        });
        $migrator->migrate();

        $result = $migrator->rollback(1);

        $this->assertSame('foo', $migrator->version());
        $this->assertSame([
            'foo' => 'Oops, it failed.',
        ], $result);
    }

    public function testRollbackWithNoCallback(): void
    {
        $migrator = new Migrator();
        $migrator->addMigration('foo', function () {
            return true;
        });
        $migrator->migrate();

        $result = $migrator->rollback(1);

        $this->assertSame('foo', $migrator->version());
        $this->assertSame([
            'foo' => 'No rollback!',
        ], $result);
    }

    public function testUpToDate(): void
    {
        $migrator = new Migrator();
        $migrator->addMigration('foo', function () {
            return true;
        });
        $migrator->setVersion('foo');

        $upToDate = $migrator->upToDate();

        $this->assertTrue($upToDate);
    }

    public function testUpToDateRespectsOrder(): void
    {
        $migrator = new Migrator();
        $migrator->addMigration('2_foo', function () {
            return true;
        });
        $migrator->addMigration('1_foo', function () {
            return true;
        });
        $migrator->setVersion('2_foo');

        $upToDate = $migrator->upToDate();

        $this->assertTrue($upToDate);
    }

    public function testUpToDateIfRemainingMigration(): void
    {
        $migrator = new Migrator();
        $migrator->addMigration('1_foo', function () {
            return true;
        });
        $migrator->addMigration('2_foo', function () {
            return true;
        });
        $migrator->setVersion('1_foo');

        $upToDate = $migrator->upToDate();

        $this->assertFalse($upToDate);
    }

    public function testUpToDateIfNoMigrations(): void
    {
        $migrator = new Migrator();

        $upToDate = $migrator->upToDate();

        $this->assertTrue($upToDate);
    }

    public function testLastVersion(): void
    {
        $migrator = new Migrator();
        $migrator->addMigration('foo', function () {
            return true;
        });

        $version = $migrator->lastVersion();

        $this->assertSame('foo', $version);
    }

    public function testLastVersionRespectsOrder(): void
    {
        $migrator = new Migrator();
        $migrator->addMigration('2_foo', function () {
            return true;
        });
        $migrator->addMigration('1_foo', function () {
            return true;
        });

        $version = $migrator->lastVersion();

        $this->assertSame('2_foo', $version);
    }

    public function testLastVersionIfNoMigrations(): void
    {
        $migrator = new Migrator();

        $version = $migrator->lastVersion();

        $this->assertNull($version);
    }

    public function testConstructorLoadsDirectory(): void
    {
        $migrations_path = \Minz\Configuration::$app_path . '/src/migrations';
        $migrator = new Migrator($migrations_path);
        $expected_names = ['Migration201912220001Foo', 'Migration201912220002Bar'];

        $migrations = $migrator->migrations();

        $this->assertSame($expected_names, array_keys($migrations));
    }
}
